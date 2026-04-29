<?php
require_once '../includes/auth.php';
require_once '../db.php';
requireRole('responsabile');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$me               = $_SESSION['username'];
$id_bilancio      = (int)($_POST['id_bilancio'] ?? 0);
$nome_voce        = trim($_POST['nome_voce']         ?? '');
$nome_indicatore  = trim($_POST['nome_indicatore']   ?? '');
$valore           = $_POST['valore']                 ?? '';
$fonte            = trim($_POST['fonte']             ?? '');
$data_rilevazione = $_POST['data_rilevazione']       ?? '';

if ($id_bilancio <= 0 || $nome_voce === '' || $nome_indicatore === ''
    || $valore === '' || $fonte === '' || $data_rilevazione === '') {
    $_SESSION['error'] = 'Compila tutti i campi.';
    header('Location: bilancio.php?id=' . $id_bilancio); exit;
}

// Auth + check stato
$stmt = $pdo->prepare("
    SELECT b.stato FROM bilancio b
    JOIN azienda a ON a.ragione_sociale = b.ragione_sociale
    WHERE b.id_bilancio = ? AND a.username_responsabile = ?
");
$stmt->execute([$id_bilancio, $me]);
$stato = $stmt->fetchColumn();

if ($stato === false) die('Non sei il responsabile di questo bilancio.');
if ($stato !== 'bozza') {
    $_SESSION['error'] = 'Bilancio non più in bozza: modifiche non consentite.';
    header('Location: bilancio.php?id=' . $id_bilancio); exit;
}

try {
    // sp_inserisci_voce_indicatore: 6 IN + 2 OUT (successo, messaggio)
    $stmt = $pdo->prepare("
        CALL sp_inserisci_voce_indicatore(?, ?, ?, ?, ?, ?, @successo, @messaggio)
    ");
    $stmt->execute([$id_bilancio, $nome_voce, $nome_indicatore,
                    (float)$valore, $fonte, $data_rilevazione]);
    $stmt->closeCursor();

    $result = $pdo->query("SELECT @successo AS successo, @messaggio AS messaggio")->fetch();
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        // PK composta (id_bilancio, nome_voce, nome_indicatore): gia' collegato
        $_SESSION['error'] = 'Questo indicatore è già collegato a questa voce in questo bilancio.';
    } else {
        $_SESSION['error'] = 'Errore: ' . $e->getMessage();
    }
    header('Location: bilancio.php?id=' . $id_bilancio); exit;
}

if ((int)$result['successo'] !== 1) {
    $_SESSION['error'] = $result['messaggio'];
} else {
    $_SESSION['success'] = $result['messaggio'];
}

header('Location: bilancio.php?id=' . $id_bilancio);
exit;
?>