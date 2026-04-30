<?php
require_once '../includes/auth.php';
require_once '../db.php';
require_once __DIR__ . '/../../mongo/mongo.php';
requireRole('responsabile');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$me          = $_SESSION['username'];
$id_bilancio = (int)($_POST['id_bilancio'] ?? 0);
$nome_voce   = trim($_POST['nome_voce']   ?? '');
$valore      = $_POST['valore']           ?? '';

if ($id_bilancio <= 0 || $nome_voce === '' || $valore === '') {
    $_SESSION['error'] = 'Compila tutti i campi.';
    header('Location: bilancio.php?id=' . $id_bilancio); exit;
}

// === CHECK 1: il bilancio appartiene davvero a un'azienda di questo responsabile ===
$stmt = $pdo->prepare("
    SELECT b.stato
    FROM bilancio b
    JOIN azienda a ON a.ragione_sociale = b.ragione_sociale
    WHERE b.id_bilancio = ? AND a.username_responsabile = ?
");
$stmt->execute([$id_bilancio, $me]);
$stato = $stmt->fetchColumn();

if ($stato === false) {
    die('Non sei il responsabile di questo bilancio.');
}

// === CHECK 2: bilancio modificabile? Solo se in 'bozza' ===
if ($stato !== 'bozza') {
    $_SESSION['error'] = 'Bilancio non più in bozza: modifiche non consentite.';
    header('Location: bilancio.php?id=' . $id_bilancio); exit;
}

try {
    // sp_inserisci_valore_voce: ON DUPLICATE KEY UPDATE → fa insert o update
    $stmt = $pdo->prepare("CALL sp_inserisci_valore_voce(?, ?, ?)");
    $stmt->execute([$id_bilancio, $nome_voce, (float)$valore]);
} catch (PDOException $e) {
    $_SESSION['error'] = 'Errore: ' . $e->getMessage();
    header('Location: bilancio.php?id=' . $id_bilancio); exit;
}

$_SESSION['success'] = 'Valore salvato.';
logEvento('valore_inserito',
    "Valore inserito in bilancio #$id_bilancio per voce \"$nome_voce\": $valore",
    ['id_bilancio' => $id_bilancio, 'voce' => $nome_voce, 'valore' => (float)$valore]
);
header('Location: bilancio.php?id=' . $id_bilancio);
exit;
?>