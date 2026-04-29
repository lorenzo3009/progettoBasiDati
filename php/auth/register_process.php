<?php
require_once '../includes/auth.php';
require_once '../db.php';

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
$cv_pdf         = trim($_POST['cv_pdf']         ?? '');

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
    'cv_pdf'         => $cv_pdf
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

// cv_pdf vuoto -> NULL nel DB (non stringa vuota)
$cv_pdf_db = ($tipo === 'responsabile' && $cv_pdf !== '') ? $cv_pdf : null;

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
        $username, $password_hash, $codice_fiscale, $data_nascita, $luogo_nascita, $tipo, $email, $cv_pdf_db
    ]);
    $stmt->closeCursor();
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
$_SESSION['error'] = 'Registrazione completata! Ora puoi accedere.';
header('Location: login.php'); exit;

// =====================================================================
// REGISTRAZIONE OK: pulisco old_input e mando l'utente al login
// con un messaggio di successo.
// =====================================================================
unset($_SESSION['old_input']);
$_SESSION['error'] = 'Registrazione completata! Ora puoi accedere.';
// Riuso $_SESSION['error'] per un messaggio "informativo": stilisticamente
// non perfetto, ma evita di creare un secondo flash. In login.php
// l'utente lo vedra' come banner rosso, basta cambiare classe se vuoi.
header('Location: login.php');
exit;
?>