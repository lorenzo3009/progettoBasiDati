<?php
require_once '../includes/auth.php';
require_once '../db.php';
requireRole('responsabile');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: crea_bilancio.php');
    exit;
}

$me              = $_SESSION['username'];
$ragione_sociale = $_POST['ragione_sociale'] ?? '';

if ($ragione_sociale === '') {
    $_SESSION['error'] = 'Seleziona un\'azienda.';
    header('Location: crea_bilancio.php'); exit;
}

// SICUREZZA: verifico che l'azienda appartenga davvero a questo responsabile
$check = $pdo->prepare("SELECT 1 FROM azienda WHERE ragione_sociale = ? AND username_responsabile = ?");
$check->execute([$ragione_sociale, $me]);
if (!$check->fetch()) {
    die('Non sei il responsabile di questa azienda.');
}

try {
    // sp_crea_bilancio ha 1 IN + 1 OUT (id_bilancio generato)
    $stmt = $pdo->prepare("CALL sp_crea_bilancio(?, @new_id)");
    $stmt->execute([$ragione_sociale]);
    $stmt->closeCursor();

    $row = $pdo->query("SELECT @new_id AS new_id")->fetch();
    $new_id = (int)$row['new_id'];
    // Trigger T3a: azienda.nr_bilanci automaticamente +1
} catch (PDOException $e) {
    $_SESSION['error'] = 'Errore: ' . $e->getMessage();
    header('Location: crea_bilancio.php'); exit;
}

$_SESSION['success'] = "Bilancio #$new_id creato. Aggiungi ora voci e indicatori.";
header('Location: bilancio.php?id=' . $new_id);
exit;
?>