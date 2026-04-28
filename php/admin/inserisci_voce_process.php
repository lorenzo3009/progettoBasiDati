<?php
require_once '../includes/auth.php';
require_once '../db.php';
requireRole('amministratore');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inserisci_voce.php');
    exit;
}

$nome        = trim($_POST['nome']        ?? '');
$descrizione = trim($_POST['descrizione'] ?? '');

// Conserva input per ripopolare il form in caso di errore
$_SESSION['old_input'] = ['nome' => $nome, 'descrizione' => $descrizione];

if ($nome === '' || $descrizione === '') {
    $_SESSION['error'] = 'Compila tutti i campi.';
    header('Location: inserisci_voce.php');
    exit;
}

try {
    $stmt = $pdo->prepare("CALL sp_inserisci_voce_contabile(?, ?)");
    $stmt->execute([$nome, $descrizione]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $_SESSION['error'] = 'Una voce con questo nome esiste già.';
    } else {
        $_SESSION['error'] = 'Errore: ' . $e->getMessage();
    }
    header('Location: inserisci_voce.php');
    exit;
}

// Successo: pulisco old_input, lascio messaggio di conferma
unset($_SESSION['old_input']);
$_SESSION['success'] = 'Voce "' . $nome . '" aggiunta con successo.';
header('Location: inserisci_voce.php');
exit;
?>