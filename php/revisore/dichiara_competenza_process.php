<?php
require_once '../includes/auth.php';
require_once '../db.php';
requireRole('revisore');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dichiara_competenza.php');
    exit;
}

$me              = $_SESSION['username'];
$nome_competenza = trim($_POST['nome_competenza'] ?? '');
$livello         = $_POST['livello']              ?? '';

if ($nome_competenza === '' || $livello === '') {
    $_SESSION['error'] = 'Compila tutti i campi.';
    header('Location: dichiara_competenza.php'); exit;
}
if ((int)$livello < 0 || (int)$livello > 5) {
    $_SESSION['error'] = 'Il livello deve essere tra 0 e 5.';
    header('Location: dichiara_competenza.php'); exit;
}

try {
    $stmt = $pdo->prepare("CALL sp_dichiara_competenza(?, ?, ?)");
    $stmt->execute([$me, $nome_competenza, (int)$livello]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $_SESSION['error'] = 'Hai già dichiarato questa competenza.';
    } else {
        $_SESSION['error'] = 'Errore: ' . $e->getMessage();
    }
    header('Location: dichiara_competenza.php'); exit;
}

$_SESSION['success'] = 'Competenza "' . $nome_competenza . '" dichiarata (livello ' . (int)$livello . ').';
header('Location: dichiara_competenza.php');
exit;
?>