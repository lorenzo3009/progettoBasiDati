<?php
require_once '../includes/auth.php';
require_once '../db.php';

// Sicurezza: questo file accetta SOLO POST (i form lo richiedono cosi).
// Se qualcuno lo apre via GET, lo rimando al login.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// Recupero i dati dal form. trim() rimuove spazi accidentali.
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validazione minima.
if ($username === '' || $password === '') {
    $_SESSION['error'] = 'Inserisci username e password.';
    header('Location: login.php');
    exit;
}

// =====================================================================
// Chiamo la stored procedure sp_login.
// La procedura usa OUT params, gestione tipica per MySQL+PDO:
//   1. CALL la stored procedure passando @variabili come segnaposto OUT
//   2. SELECT delle @variabili per leggerne il valore
// =====================================================================
try {
    $stmt = $pdo->prepare("CALL sp_login(?, @hash, @tipo)");
    $stmt->execute([$username]);
    $stmt->closeCursor();   // libera il cursore prima della SELECT successiva

    $result = $pdo->query("SELECT @hash AS hash, @tipo AS tipo")->fetch();
} catch (PDOException $e) {
    $_SESSION['error'] = 'Errore database: ' . $e->getMessage();
    header('Location: login.php');
    exit;
}

$stored_password = $result['hash'] ?? null;
$tipo            = $result['tipo'] ?? null;

// Username inesistente: $stored_password e' NULL.
if ($stored_password === null) {
    $_SESSION['error'] = 'Username o password non corretti.';
    header('Location: login.php');
    exit;
}

// =====================================================================
// Verifica password.
// Doppio check temporaneo per supportare:
//   1. Utenti registrati via PHP -> password hashata (password_verify)
//   2. Utenti seed di popolamento.sql -> password in chiaro (===)
// In produzione resterebbe solo password_verify.
// =====================================================================
$ok = password_verify($password, $stored_password)
   || $password === $stored_password;

if (!$ok) {
    $_SESSION['error'] = 'Username o password non corretti.';
    header('Location: login.php');
    exit;
}

// =====================================================================
// LOGIN RIUSCITO: salvo i dati in sessione e redirigo alla dashboard
// corretta in base al ruolo.
// =====================================================================
$_SESSION['username'] = $username;
$_SESSION['tipo']     = $tipo;

// Rigenera l'ID di sessione: best practice di sicurezza dopo il login,
// previene "session fixation" (attacco in cui un malintenzionato
// pre-imposta l'ID e aspetta che la vittima si autentichi).
session_regenerate_id(true);

// Redirect alla dashboard giusta.
switch ($tipo) {
    case 'amministratore':
        header('Location: ../admin/dashboard.php');
        break;
    case 'revisore':
        header('Location: ../revisore/dashboard.php');
        break;
    case 'responsabile':
        header('Location: ../responsabile/dashboard.php');
        break;
    default:
        header('Location: ../../index.php');
}
exit;
?>