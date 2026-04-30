<?php
// =====================================================================
// mongo.php :: helper per scrivere eventi nel log MongoDB
// ---------------------------------------------------------------------
// Uso: require_once '../includes/mongo.php'; logEvento('tipo', 'descrizione', [...]);
// =====================================================================

// L'estensione PHP 'mongodb' espone una libreria a basso livello.
// La libreria "alta" (mongodb/mongodb) andrebbe installata via Composer,
// ma per evitare dipendenze esterne uso direttamente l'API a basso livello:
// MongoDB\Driver\Manager (connessione) e MongoDB\Driver\BulkWrite (operazioni).

// =====================================================================
// Connessione al server MongoDB locale.
// La creo come variabile globale UNA sola volta (se questo file è
// incluso piu' volte, l'oggetto $mongoManager non si crea di nuovo).
// =====================================================================
if (!isset($mongoManager)) {
    try {
        $mongoManager = new MongoDB\Driver\Manager('mongodb://localhost:27017');
    } catch (Exception $e) {
        // Se MongoDB non risponde, NON blocchiamo l'app:
        // i log sono "non critici", l'azione utente principale deve sempre passare.
        // Loggo solo a console di Apache (error_log)
        error_log('[MongoDB] connessione fallita: ' . $e->getMessage());
        $mongoManager = null;
    }
}


/**
 * Scrive un evento nella collezione 'eventi' del database 'esg_balance_log'.
 *
 * @param string $tipo         categoria evento (es. 'bilancio_creato', 'giudizio_emesso')
 * @param string $descrizione  testo umano per l'evento (es. "Bilancio #42 creato")
 * @param array  $dati_extra   dati strutturati specifici dell'evento
 * @return bool                true se il log e' andato a buon fine
 */
function logEvento(string $tipo, string $descrizione, array $dati_extra = []): bool
{
    global $mongoManager;

    // Se la connessione era fallita all'inizio, salto silenziosamente.
    if ($mongoManager === null) return false;

    // Costruisco il documento da inserire.
    // Tutto in formato BSON nativo: i tipi MongoDB sono auto-gestiti dalla libreria.
    $documento = [
        'timestamp'   => new MongoDB\BSON\UTCDateTime(),  // data/ora del server in UTC
        'tipo'        => $tipo,
        'descrizione' => $descrizione,
        'utente'      => $_SESSION['username'] ?? 'anonimo',
        'ruolo'       => $_SESSION['tipo']     ?? 'sconosciuto',
        'dati'        => $dati_extra,
    ];

    try {
        // BulkWrite e' il modo "ufficiale" di fare INSERT/UPDATE/DELETE
        // con il driver di basso livello.
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->insert($documento);

        // Sintassi: "<database>.<collezione>"
        $mongoManager->executeBulkWrite('esg_balance_log.eventi', $bulk);
        return true;

    } catch (Exception $e) {
        // Anche qui, fallimento silenzioso: l'app non deve crashare se il log non riesce.
        error_log('[MongoDB] log fallito: ' . $e->getMessage());
        return false;
    }
}
?>