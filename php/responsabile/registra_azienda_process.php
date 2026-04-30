<?php
require_once '../includes/auth.php';
require_once '../db.php';
require_once __DIR__ . '/../../mongo/mongo.php';
requireRole('responsabile');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registra_azienda.php');
    exit;
}

$me              = $_SESSION['username'];
$ragione_sociale = trim($_POST['ragione_sociale'] ?? '');
$nome            = trim($_POST['nome']            ?? '');
$partita_iva     = trim($_POST['partita_iva']     ?? '');
$settore         = trim($_POST['settore']         ?? '');
$num_dipendenti  = $_POST['num_dipendenti']       ?? '';
$logo            = trim($_POST['logo']            ?? '');

$_SESSION['old_input'] = compact('ragione_sociale','nome','partita_iva','settore','num_dipendenti','logo');

// Validazione
if ($ragione_sociale === '' || $nome === '' || $partita_iva === '' || $settore === '' || $num_dipendenti === '') {
    $_SESSION['error'] = 'Compila tutti i campi obbligatori.';
    header('Location: registra_azienda.php'); exit;
}
if (!preg_match('/^[0-9]{11}$/', $partita_iva)) {
    $_SESSION['error'] = 'La partita IVA deve essere di 11 cifre.';
    header('Location: registra_azienda.php'); exit;
}

$logo_db = ($logo !== '') ? $logo : null;

try {
    $stmt = $pdo->prepare("CALL sp_registra_azienda(?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$ragione_sociale, $nome, $partita_iva, $settore,
                    (int)$num_dipendenti, $logo_db, $me]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $_SESSION['error'] = 'Ragione sociale o partita IVA già registrate.';
    } else {
        $_SESSION['error'] = 'Errore: ' . $e->getMessage();
    }
    header('Location: registra_azienda.php'); exit;
}

unset($_SESSION['old_input']);
$_SESSION['success'] = 'Azienda "' . $nome . '" registrata.';
header('Location: dashboard.php');
exit;
?>