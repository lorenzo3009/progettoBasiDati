<?php
require_once '../includes/auth.php';
require_once '../db.php';
requireRole('amministratore');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inserisci_indicatore.php');
    exit;
}

$nome             = trim($_POST['nome']             ?? '');
$immagine         = trim($_POST['immagine']         ?? '');
$rilevanza        = $_POST['rilevanza']             ?? '';
$categoria        = $_POST['categoria']             ?? '';
$codice_normativa = trim($_POST['codice_normativa'] ?? '');
$ambito           = trim($_POST['ambito']           ?? '');
$frequenza        = trim($_POST['frequenza']        ?? '');

$_SESSION['old_input'] = compact('nome','immagine','rilevanza','categoria','codice_normativa','ambito','frequenza');

// Validazione base
if ($nome === '' || $rilevanza === '' || $categoria === '') {
    $_SESSION['error'] = 'Compila i campi obbligatori.';
    header('Location: inserisci_indicatore.php'); exit;
}
if (!in_array($categoria, ['ambientale','sociale','nessuna'], true)) {
    $_SESSION['error'] = 'Categoria non valida.';
    header('Location: inserisci_indicatore.php'); exit;
}
if ($categoria === 'ambientale' && $codice_normativa === '') {
    $_SESSION['error'] = 'Per categoria "ambientale" serve il codice normativa.';
    header('Location: inserisci_indicatore.php'); exit;
}
if ($categoria === 'sociale' && ($ambito === '' || $frequenza === '')) {
    $_SESSION['error'] = 'Per categoria "sociale" servono ambito e frequenza.';
    header('Location: inserisci_indicatore.php'); exit;
}

// Campi non rilevanti li passo come NULL alla procedura.
$immagine_db         = ($immagine !== '') ? $immagine : null;
$codice_normativa_db = ($categoria === 'ambientale') ? $codice_normativa : null;
$ambito_db           = ($categoria === 'sociale')    ? $ambito           : null;
$frequenza_db        = ($categoria === 'sociale')    ? $frequenza        : null;

try {
    $stmt = $pdo->prepare("CALL sp_inserisci_indicatore_esg(?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nome, $immagine_db, (int)$rilevanza, $categoria,
                    $codice_normativa_db, $ambito_db, $frequenza_db]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $_SESSION['error'] = 'Un indicatore con questo nome esiste già.';
    } else {
        $_SESSION['error'] = 'Errore: ' . $e->getMessage();
    }
    header('Location: inserisci_indicatore.php'); exit;
}

unset($_SESSION['old_input']);
$_SESSION['success'] = 'Indicatore "' . $nome . '" aggiunto con successo.';
header('Location: inserisci_indicatore.php');
exit;
?>