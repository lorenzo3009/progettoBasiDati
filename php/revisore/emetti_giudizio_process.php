<?php
require_once '../includes/auth.php';
require_once '../db.php';
require_once __DIR__ . '/../../mongo/mongo.php';
requireRole('revisore');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$me          = $_SESSION['username'];
$id_bilancio = (int)($_POST['id_bilancio'] ?? 0);
$esito       = $_POST['esito']   ?? '';
$rilievi     = trim($_POST['rilievi'] ?? '');

if ($id_bilancio <= 0 || $esito === '') {
    $_SESSION['error'] = 'Seleziona un esito.';
    header('Location: bilancio.php?id=' . $id_bilancio); exit;
}
if (!in_array($esito, ['approvazione','approvazione_con_rilievi','respingimento'], true)) {
    $_SESSION['error'] = 'Esito non valido.';
    header('Location: bilancio.php?id=' . $id_bilancio); exit;
}

// Check: il revisore e' assegnato a questo bilancio.
$check = $pdo->prepare("SELECT 1 FROM revisione WHERE username_revisore = ? AND id_bilancio = ?");
$check->execute([$me, $id_bilancio]);
if (!$check->fetch()) {
    die('Non sei autorizzato.');
}

$rilievi_db = ($rilievi !== '') ? $rilievi : null;

try {
    $stmt = $pdo->prepare("CALL sp_emetti_giudizio(?, ?, ?, CURDATE(), ?)");
    $stmt->execute([$me, $id_bilancio, $esito, $rilievi_db]);
    // Trigger T2: se questo era l'ultimo giudizio mancante, lo stato del
    // bilancio passa automaticamente a 'approvato' o 'respinto'.
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $_SESSION['error'] = 'Hai già emesso un giudizio per questo bilancio.';
    } else {
        $_SESSION['error'] = 'Errore: ' . $e->getMessage();
    }
    header('Location: bilancio.php?id=' . $id_bilancio); exit;
}

$_SESSION['success'] = 'Giudizio emesso correttamente.';
logEvento('giudizio_emesso',
    "Giudizio \"$esito\" emesso da $me sul bilancio #$id_bilancio",
    ['id_bilancio' => $id_bilancio, 'esito' => $esito, 'revisore' => $me]
);
header('Location: bilancio.php?id=' . $id_bilancio);
exit;
?>