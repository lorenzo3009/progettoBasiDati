<?php
// =====================================================================
// auth.php :: funzioni di supporto per autenticazione e autorizzazione
// =====================================================================

// session_start() deve essere chiamato all'inizio di ogni script
// che usa $_SESSION. Lo metto qui cosi e' centralizzato.
// session_status() == PHP_SESSION_NONE evita di chiamarlo due volte.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ritorna true se l'utente e' loggato (esiste il dato in sessione).
function isLogged() {
    return isset($_SESSION['username']);
}

// Ritorna il record dell'utente loggato, o null se non loggato.
function getUser() {
    if (!isLogged()) return null;
    return [
        'username' => $_SESSION['username'],
        'tipo'     => $_SESSION['tipo']
    ];
}

// Forza l'accesso: se non sei loggato, sei rispedito al login.
// Da chiamare all'inizio di ogni pagina riservata.
function requireLogin() {
    if (!isLogged()) {
        header('Location: /progettoBasiDati/php/auth/login.php');
        exit;   // exit fondamentale dopo header(): ferma l'esecuzione
    }
}

// Forza un ruolo specifico. Da chiamare ad inizio pagina.
// Es.: requireRole('amministratore') in cima alle pagine admin.
function requireRole($tipo_richiesto) {
    requireLogin();
    if ($_SESSION['tipo'] !== $tipo_richiesto) {
        die('Accesso negato: serve il ruolo ' . $tipo_richiesto);
    }
}
?>