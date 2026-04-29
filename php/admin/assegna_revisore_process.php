<?php
require_once '../includes/auth.php';
require_once '../db.php';
requireRole('amministratore');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: assegna_revisore.php');
    exit;
}

$id_bilancio       = $_POST['id_bilancio']       ?? '';
$username_revisore = $_POST['username_revisore'] ?? '';

if ($id_bilancio === '' || $username_revisore === '') {
    $_SESSION['error'] = 'Seleziona bilancio e revisore.';
    header('Location: assegna_revisore.php'); exit;
}

try {
    $stmt = $pdo->prepare("CALL sp_assegna_revisore(?, ?)");
    $stmt->execute([$username_revisore, (int)$id_bilancio]);
    // Trigger T1: se il bilancio era 'bozza' diventa 'in_revisione' automaticamente.
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $_SESSION['error'] = 'Il revisore è già assegnato a questo bilancio.';
    } elseif ($e->getCode() === '45000') {
        // Errore custom sollevato dalla stored procedure (bilancio vuoto)
        $_SESSION['error'] = $e->getMessage();
    } else {
        $_SESSION['error'] = 'Errore: ' . $e->getMessage();
    }
    header('Location: assegna_revisore.php'); exit;
}

$_SESSION['success'] = 'Revisore "' . $username_revisore . '" assegnato al bilancio #' . $id_bilancio . '.';
header('Location: assegna_revisore.php');
exit;
?>