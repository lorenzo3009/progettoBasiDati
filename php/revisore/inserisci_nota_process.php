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
$nome_voce   = trim($_POST['nome_voce']    ?? '');
$testo       = trim($_POST['testo']        ?? '');

if ($id_bilancio <= 0 || $nome_voce === '' || $testo === '') {
    $_SESSION['error'] = 'Compila tutti i campi.';
    header('Location: bilancio.php?id=' . $id_bilancio); exit;
}

// Il check "revisore assegnato al bilancio" lo fa internamente la procedura
// (restituisce p_successo = 0 con messaggio se non e' assegnato).
// Quindi qui non serve la SELECT preventiva su 'revisione'.

try {
    $stmt = $pdo->prepare("
        CALL sp_inserisci_nota(?, ?, ?, CURDATE(), ?, @successo, @messaggio)
    ");
    $stmt->execute([$me, $id_bilancio, $nome_voce, $testo]);
    $stmt->closeCursor();

    // Leggo i due OUT param tramite SELECT delle @variabili MySQL
    $result = $pdo->query("SELECT @successo AS successo, @messaggio AS messaggio")->fetch();

} catch (PDOException $e) {
    // Errori SQL "duri" (es. duplicato PK su nota: PRIMARY KEY composta
    // (revisore, bilancio, voce) -> 23000)
    if ($e->getCode() === '23000') {
        $_SESSION['error'] = 'Hai già scritto una nota per questa voce in questo bilancio.';
    } else {
        $_SESSION['error'] = 'Errore: ' . $e->getMessage();
    }
    header('Location: bilancio.php?id=' . $id_bilancio); exit;
}

// Lettura dell'esito "morbido" comunicato dalla procedura tramite OUT
if ((int)$result['successo'] === 1) {
    $_SESSION['success'] = $result['messaggio'];
    logEvento('nota_inserita',
    "Nota aggiunta da $me sul bilancio #$id_bilancio (voce \"$nome_voce\")",
    ['id_bilancio' => $id_bilancio, 'voce' => $nome_voce, 'revisore' => $me]
);
} else {
    $_SESSION['error']   = $result['messaggio'];
}

header('Location: bilancio.php?id=' . $id_bilancio);
exit;
?>