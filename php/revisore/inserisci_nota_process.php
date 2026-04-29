<?php
require_once '../includes/auth.php';
require_once '../db.php';
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

// Doppio check di autorizzazione: solo revisori assegnati possono scrivere note.
// (Non basta requireRole: qualsiasi revisore potrebbe inviare POST con qualsiasi id_bilancio.)
$check = $pdo->prepare("SELECT 1 FROM revisione WHERE username_revisore = ? AND id_bilancio = ?");
$check->execute([$me, $id_bilancio]);
if (!$check->fetch()) {
    die('Non sei autorizzato.');
}

try {
    $stmt = $pdo->prepare("CALL sp_inserisci_nota(?, ?, ?, CURDATE(), ?)");
    $stmt->execute([$me, $id_bilancio, $nome_voce, $testo]);
    // Nota: passo CURDATE() direttamente come parametro: il DB
    // calcola la data di oggi al momento dell'INSERT.
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        // PK composta (revisore, bilancio, voce): hai gia' una nota su questa voce.
        $_SESSION['error'] = 'Hai già scritto una nota per questa voce in questo bilancio.';
    } else {
        $_SESSION['error'] = 'Errore: ' . $e->getMessage();
    }
    header('Location: bilancio.php?id=' . $id_bilancio); exit;
}

$_SESSION['success'] = 'Nota aggiunta.';
header('Location: bilancio.php?id=' . $id_bilancio);
exit;
?>