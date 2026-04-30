<?php
require_once '../includes/auth.php';
require_once '../db.php';
require_once __DIR__ . '/../../mongo/mongo.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

// Recupero e pulizia input.
$username       = trim($_POST['username']       ?? '');
$password       = $_POST['password']            ?? '';
$codice_fiscale = strtoupper(trim($_POST['codice_fiscale'] ?? ''));
$data_nascita   = $_POST['data_nascita']        ?? '';
$luogo_nascita  = trim($_POST['luogo_nascita']  ?? '');
$tipo           = $_POST['tipo']                ?? '';
$email          = trim($_POST['email']          ?? '');

// Conservo gli input nel caso di errore (li ripopolo nel form).
// La password NON la conservo per principio: se ricarico il form, l'utente
// la digiterà di nuovo (e' un dato sensibile, non gira piu' del necessario).
$_SESSION['old_input'] = [
    'username'       => $username,
    'codice_fiscale' => $codice_fiscale,
    'data_nascita'   => $data_nascita,
    'luogo_nascita'  => $luogo_nascita,
    'tipo'           => $tipo,
    'email'          => $email,
];

// =====================================================================
// Validazione lato server.
// IMPORTANTE: anche se ho validazione HTML5 nel form, NON BASTA.
// Un attaccante puo' bypassare il browser e inviare richieste a mano.
// La validazione lato server e' l'unica reale.
// =====================================================================
if ($username === '' || $password === '' || $codice_fiscale === ''
    || $data_nascita === '' || $luogo_nascita === '' || $tipo === '' || $email === '') {
    $_SESSION['error'] = 'Compila tutti i campi obbligatori.';
    header('Location: register.php');
    exit;
}

if (!in_array($tipo, ['revisore', 'responsabile'], true)) {
    $_SESSION['error'] = 'Tipo utente non valido.';
    header('Location: register.php');
    exit;
}

if (strlen($codice_fiscale) !== 16) {
    $_SESSION['error'] = 'Il codice fiscale deve essere di 16 caratteri.';
    header('Location: register.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Email non valida.';
    header('Location: register.php');
    exit;
}

// =====================================================================
// Hashing password con bcrypt.
// password_hash() include automaticamente un salt casuale.
// PASSWORD_DEFAULT: PHP sceglie l'algoritmo migliore (oggi bcrypt).
// =====================================================================
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// =====================================================================
// Gestione upload PDF del CV (solo per responsabile, opzionale)
// =====================================================================
$cv_pdf_db = null;

if ($tipo === 'responsabile'
    && isset($_FILES['cv_pdf'])
    && $_FILES['cv_pdf']['error'] === UPLOAD_ERR_OK) {

    // Controllo MIME reale (non solo l'estensione: un attaccante
    // potrebbe rinominare script.php in script.pdf)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $_FILES['cv_pdf']['tmp_name']);
    finfo_close($finfo);

    if ($mime !== 'application/pdf') {
        $_SESSION['error'] = 'Il file caricato non è un PDF valido.';
        header('Location: register.php'); exit;
    }
    if ($_FILES['cv_pdf']['size'] > 2 * 1024 * 1024) {
        $_SESSION['error'] = 'Il PDF supera i 2 MB.';
        header('Location: register.php'); exit;
    }

    // Nome file derivato dall'username (sanitizzato)
    $upload_dir  = __DIR__ . '/../../assets/uploads/';
    $filename    = 'cv_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $username) . '.pdf';
    $destination = $upload_dir . $filename;

    // Crea la cartella uploads se non esiste
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0775, true);
    }

    if (!move_uploaded_file($_FILES['cv_pdf']['tmp_name'], $destination)) {
        $_SESSION['error'] = 'Impossibile salvare il file sul server.';
        header('Location: register.php'); exit;
    }

    // Nel DB salvo il percorso relativo dal documento root
    $cv_pdf_db = 'assets/uploads/' . $filename;
}

// =====================================================================
// Chiamo la stored procedure sp_registra_utente.
// La procedura gestisce internamente la TRANSACTION con EXIT HANDLER:
// se uno degli INSERT fallisce, fa ROLLBACK e rilancia l'errore qui,
// che cattura il catch sotto.
// =====================================================================
try {
    $stmt = $pdo->prepare("
        CALL sp_registra_utente(?, ?, ?, ?, ?, ?, ?, ?, @successo, @messaggio)
    ");
    $stmt->execute([
        $username,
        $password_hash,
        $codice_fiscale,
        $data_nascita,
        $luogo_nascita,
        $tipo,
        $email,
        $cv_pdf_db
    ]);
    $stmt->closeCursor();

    // Leggo gli OUT param (la procedura usa EXIT HANDLER e li valorizza
    // anche in caso di errore SQL come duplicato).
    $result = $pdo->query("SELECT @successo AS successo, @messaggio AS messaggio")->fetch();
} catch (PDOException $e) {
    $_SESSION['error'] = 'Errore database: ' . $e->getMessage();
    header('Location: register.php'); exit;
}

if ((int)$result['successo'] !== 1) {
    $_SESSION['error'] = $result['messaggio'];
    header('Location: register.php'); exit;
}

unset($_SESSION['old_input']);
$_SESSION['success'] = 'Registrazione completata! Ora puoi accedere.';
logEvento('utente_registrato',
    "Nuovo utente registrato: \"$username\" (tipo: $tipo)",
    ['username' => $username, 'tipo' => $tipo]
);
header('Location: login.php');
exit;
?>