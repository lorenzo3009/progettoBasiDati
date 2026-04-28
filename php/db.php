<?php
// =====================================================================
// db.php :: connessione al database via PDO
// ---------------------------------------------------------------------
// Viene incluso da OGNI script PHP che deve parlare col DB.
// Espone una variabile globale $pdo gia' connessa.
// =====================================================================

// Parametri di connessione: in produzione andrebbero in un file .env
// fuori dalla document root. Per il progetto d'esame li teniamo qui.
$DB_HOST = 'localhost';
$DB_NAME = 'esg_balance';
$DB_USER = 'root';
$DB_PASS = '';   // XAMPP di default ha password vuota per root

// DSN = "Data Source Name": stringa che descrive la connessione.
// charset=utf8mb4 garantisce supporto completo Unicode (emoji, ecc.).
$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";

// Opzioni PDO importanti:
$options = [
    // 1) Eccezioni invece di errori silenziosi: se una query fallisce,
    //    PHP lancia un'exception che possiamo intercettare con try/catch.
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

    // 2) I risultati delle query arrivano come array associativi
    //    (es. $row['nome']) anziche' duplicati come array numerici.
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

    // 3) Disabilita l'emulazione delle prepared statement: usa quelle
    //    "vere" del database. Migliore protezione contro SQL injection.
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Crea l'oggetto $pdo. Da qui in poi lo uso per tutte le query.
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    // Se la connessione fallisce, blocco l'app con un messaggio chiaro.
    // In produzione NON mostreresti $e->getMessage() all'utente
    // (potrebbe rivelare dettagli sensibili), ma per sviluppo va bene.
    die('Errore di connessione al database: ' . $e->getMessage());
}
?>