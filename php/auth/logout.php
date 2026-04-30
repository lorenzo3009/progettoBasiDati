<?php
// =====================================================================
// logout.php :: distrugge la sessione e rimanda alla home.
// =====================================================================

// session_start() necessario PRIMA di poter manipolare la sessione.
session_start();

require_once __DIR__ . '/../../mongo/mongo.php';

// Loggo PRIMA di distruggere la sessione (mi serve username)
if (isset($_SESSION['username'])) {
    logEvento('logout',
        "Logout di " . $_SESSION['username'],
        ['username' => $_SESSION['username']]
    );
}

// 1) Svuoto l'array di sessione lato server.
$_SESSION = [];

// 2) Cancello il cookie PHPSESSID dal browser (non strettamente
//    necessario ma e' una buona pratica: forza il browser a non
//    riusare quel session ID).
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,                    // tempo passato -> cookie scade
        $params['path'],   $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// 3) Distruggo definitivamente la sessione lato server.
session_destroy();

// 4) Redirect alla homepage.
header('Location: ../../index.php');
exit;
?>