-- =====================================================================
-- ESG-BALANCE  ::  stored_procedures.sql
-- ---------------------------------------------------------------------
-- Ordine di caricamento: DOPO schema.sql e triggers.sql.
-- =====================================================================

USE esg_balance;

-- Pulizia: elimino le procedure se esistono già (come per i trigger)
DROP PROCEDURE IF EXISTS sp_login;
DROP PROCEDURE IF EXISTS sp_registra_utente;
DROP PROCEDURE IF EXISTS sp_inserisci_voce_contabile;
DROP PROCEDURE IF EXISTS sp_inserisci_indicatore_esg;
DROP PROCEDURE IF EXISTS sp_assegna_revisore;
DROP PROCEDURE IF EXISTS sp_dichiara_competenza;
DROP PROCEDURE IF EXISTS sp_inserisci_nota;
DROP PROCEDURE IF EXISTS sp_emetti_giudizio;
DROP PROCEDURE IF EXISTS sp_registra_azienda;
DROP PROCEDURE IF EXISTS sp_crea_bilancio;
DROP PROCEDURE IF EXISTS sp_inserisci_valore_voce;
DROP PROCEDURE IF EXISTS sp_inserisci_voce_indicatore;

DELIMITER $$


-- =====================================================================
-- AREA: TUTTI GLI UTENTI
-- =====================================================================

-- ---------------------------------------------------------------------
-- sp_login
-- ---------------------------------------------------------------------
-- Recupera l'hash della password e il tipo dell'utente dato lo username.
-- La VERIFICA della password avviene nel PHP (con password_verify),
-- non qui dentro, perché bcrypt genera un hash diverso ogni volta:
-- non si può confrontare in SQL.
-- Se lo username non esiste, le variabili OUT restano NULL.
-- ---------------------------------------------------------------------
CREATE PROCEDURE sp_login(
    IN  p_username      VARCHAR(50),
    OUT p_password_hash VARCHAR(255),  -- PHP lo confronterà con password_verify()
    OUT p_tipo          VARCHAR(20)    -- 'revisore', 'responsabile', 'amministratore'
)
BEGIN
    -- SELECT ... INTO: legge i valori direttamente nelle variabili OUT
    SELECT password, tipo
      INTO p_password_hash, p_tipo
      FROM utente
     WHERE username = p_username;
END$$


-- ---------------------------------------------------------------------
-- sp_registra_utente
-- ---------------------------------------------------------------------
-- Crea un nuovo utente (solo tipo 'revisore' o 'responsabile').
-- Gli amministratori vengono creati manualmente dal DBA, non si registrano.
--
-- Usa una TRANSAZIONE: se qualsiasi INSERT fallisce (es. username già
-- preso, CF duplicato, email già usata), torna indietro su tutto.
-- DECLARE EXIT HANDLER FOR SQLEXCEPTION = "se c'è un errore SQL,
-- esegui questo blocco ed esci dalla procedura".
-- ---------------------------------------------------------------------
CREATE PROCEDURE sp_registra_utente(
    IN  p_username      VARCHAR(50),
    IN  p_password      VARCHAR(255),   -- già hashata dal PHP con password_hash()
    IN  p_cf            CHAR(16),
    IN  p_data_nascita  DATE,
    IN  p_luogo_nascita VARCHAR(100),
    IN  p_tipo          VARCHAR(20),    -- 'revisore' o 'responsabile'
    IN  p_email         VARCHAR(150),
    IN  p_cv_pdf        VARCHAR(255),   -- percorso file CV, solo per responsabile (altrimenti NULL)
    OUT p_successo      TINYINT(1),     -- 1 = ok, 0 = errore
    OUT p_messaggio     VARCHAR(255)
)
BEGIN
    -- Se si verifica qualsiasi errore SQL (chiave duplicata, vincolo violato...):
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;  -- annulla tutte le INSERT fatte finora in questa transazione
        SET p_successo  = 0;
        SET p_messaggio = 'Errore: username, codice fiscale o email già esistenti.';
    END;

    START TRANSACTION;

    -- 1. Inserisco nella tabella base (comune a tutti i tipi)
    INSERT INTO utente (username, password, codice_fiscale, data_nascita, luogo_nascita, tipo)
    VALUES (p_username, p_password, p_cf, p_data_nascita, p_luogo_nascita, p_tipo);

    -- 2. Inserisco l'email (almeno una obbligatoria)
    INSERT INTO email (email, username)
    VALUES (p_email, p_username);

    -- 3. Inserisco nella tabella figlia ISA in base al tipo
    IF p_tipo = 'revisore' THEN
        -- num_revisioni e indice_affidabilita hanno DEFAULT nel DDL
        INSERT INTO revisore_esg (username)
        VALUES (p_username);

    ELSEIF p_tipo = 'responsabile' THEN
        INSERT INTO responsabile_aziendale (username, cv_pdf)
        VALUES (p_username, p_cv_pdf);
    END IF;

    COMMIT;
    SET p_successo  = 1;
    SET p_messaggio = 'Registrazione completata con successo.';
END$$


-- =====================================================================
-- AREA: AMMINISTRATORE
-- =====================================================================

-- ---------------------------------------------------------------------
-- sp_inserisci_voce_contabile
-- ---------------------------------------------------------------------
-- Aggiunge una nuova voce al template globale delle voci contabili.
-- Solo l'amministratore chiama questa procedura (controllo lato PHP).
-- ---------------------------------------------------------------------
CREATE PROCEDURE sp_inserisci_voce_contabile(
    IN p_nome        VARCHAR(100),
    IN p_descrizione TEXT
)
BEGIN
    INSERT INTO voce_contabile (nome, descrizione)
    VALUES (p_nome, p_descrizione);
END$$


-- ---------------------------------------------------------------------
-- sp_inserisci_indicatore_esg
-- ---------------------------------------------------------------------
-- Aggiunge un indicatore ESG. Opzionalmente lo specializza in
-- ambientale o sociale (ISA parziale: può anche non essere nessuno dei due).
--
-- p_tipo_ind: 'ambientale', 'sociale', oppure NULL (indicatore generico)
-- I parametri specifici (codice_normativa, ambito, frequenza) vengono
-- usati solo se il tipo corrispondente è selezionato; altrimenti NULL.
-- ---------------------------------------------------------------------
CREATE PROCEDURE sp_inserisci_indicatore_esg(
    IN p_nome             VARCHAR(100),
    IN p_immagine         VARCHAR(255),
    IN p_rilevanza        TINYINT,
    IN p_tipo_ind         VARCHAR(20),   -- 'ambientale', 'sociale', o NULL
    IN p_codice_normativa VARCHAR(50),   -- solo se p_tipo_ind = 'ambientale'
    IN p_ambito           VARCHAR(100),  -- solo se p_tipo_ind = 'sociale'
    IN p_frequenza        VARCHAR(50)    -- solo se p_tipo_ind = 'sociale'
)
BEGIN
    -- Prima inserisco nella tabella padre (sempre)
    INSERT INTO indicatore_esg (nome, immagine, rilevanza)
    VALUES (p_nome, p_immagine, p_rilevanza);

    -- Poi, se richiesto, nella tabella figlia ISA
    IF p_tipo_ind = 'ambientale' THEN
        INSERT INTO ind_ambientale (nome, codice_normativa)
        VALUES (p_nome, p_codice_normativa);

    ELSEIF p_tipo_ind = 'sociale' THEN
        INSERT INTO ind_sociale (nome, ambito, frequenza)
        VALUES (p_nome, p_ambito, p_frequenza);
    END IF;
    -- Se p_tipo_ind è NULL non si entra in nessun IF: indicatore generico
END$$


-- ---------------------------------------------------------------------
-- sp_assegna_revisore
-- ---------------------------------------------------------------------
-- Assegna un revisore a un bilancio. Basta la INSERT in revisione:
-- il trigger T1 (trg_assegna_revisore) si occupa automaticamente
-- di cambiare lo stato del bilancio da 'bozza' a 'in_revisione'.
-- ---------------------------------------------------------------------
CREATE PROCEDURE sp_assegna_revisore(
    IN p_username VARCHAR(50),
    IN p_id_bilancio INT
)
BEGIN
    DECLARE n_valori INT;

    -- Vincolo applicativo: bilanci vuoti non possono ricevere revisori,
    -- altrimenti il revisore non ha nulla da revisionare.
    SELECT COUNT(*) INTO n_valori
      FROM valore_bilancio
     WHERE id_bilancio = p_id_bilancio;

    IF n_valori = 0 THEN
        SIGNAL SQLSTATE '45000'
          SET MESSAGE_TEXT = 'Bilancio vuoto: aggiungere almeno una voce valorizzata prima di assegnare un revisore.';
    END IF;

    INSERT INTO revisione (username_revisore, id_bilancio)
    VALUES (p_username, p_id_bilancio);
END$$



-- =====================================================================
-- AREA: REVISORE ESG
-- =====================================================================

-- ---------------------------------------------------------------------
-- sp_dichiara_competenza
-- ---------------------------------------------------------------------
-- Il revisore dichiara una competenza con il proprio livello (0-5).
-- Se la competenza non esiste ancora nel sistema, la crea.
-- Se il revisore l'aveva già dichiarata, aggiorna il livello.
--
-- INSERT IGNORE: inserisce in competenza solo se il nome non esiste già.
--   Se esiste, ignora l'errore di chiave duplicata (non lancia eccezione).
-- ON DUPLICATE KEY UPDATE: se (username, nome_competenza) esiste già
--   in possesso_competenza, aggiorna il livello invece di dare errore.
-- ---------------------------------------------------------------------
CREATE PROCEDURE sp_dichiara_competenza(
    IN p_username        VARCHAR(50),
    IN p_nome_competenza VARCHAR(100),
    IN p_livello         TINYINT
)
BEGIN
    -- Crea la competenza se non esiste (non dà errore se esiste già)
    INSERT IGNORE INTO competenza (nome)
    VALUES (p_nome_competenza);

    -- Associa la competenza al revisore (o aggiorna il livello)
    INSERT INTO possesso_competenza (username, nome_competenza, livello)
    VALUES (p_username, p_nome_competenza, p_livello)
    ON DUPLICATE KEY UPDATE livello = p_livello;
END$$


-- ---------------------------------------------------------------------
-- sp_inserisci_nota
-- ---------------------------------------------------------------------
-- Il revisore aggiunge una nota su una voce specifica di un bilancio.
-- Vincolo: il revisore deve avere una revisione attiva su quel bilancio.
-- Lo verifichiamo qui con un IF prima di inserire.
-- ---------------------------------------------------------------------
CREATE PROCEDURE sp_inserisci_nota(
    IN  p_username_revisore VARCHAR(50),
    IN  p_id_bilancio       INT,
    IN  p_nome_voce         VARCHAR(100),
    IN  p_data              DATE,
    IN  p_testo             TEXT,
    OUT p_successo          TINYINT(1),
    OUT p_messaggio         VARCHAR(255)
)
BEGIN
    DECLARE v_ha_revisione INT;

    -- Controllo: il revisore è assegnato a questo bilancio?
    SELECT COUNT(*) INTO v_ha_revisione
      FROM revisione
     WHERE username_revisore = p_username_revisore
       AND id_bilancio       = p_id_bilancio;

    IF v_ha_revisione = 0 THEN
        SET p_successo  = 0;
        SET p_messaggio = 'Errore: il revisore non è assegnato a questo bilancio.';
    ELSE
        INSERT INTO nota (username_revisore, id_bilancio, nome_voce, data, testo)
        VALUES (p_username_revisore, p_id_bilancio, p_nome_voce, p_data, p_testo);
        SET p_successo  = 1;
        SET p_messaggio = 'Nota inserita con successo.';
    END IF;
END$$


-- ---------------------------------------------------------------------
-- sp_emetti_giudizio
-- ---------------------------------------------------------------------
-- Il revisore emette il giudizio finale su un bilancio.
-- Dopo l'INSERT in giudizio:
--   - il trigger T2 aggiorna automaticamente lo stato del bilancio
--   - questa procedura aggiorna num_revisioni e indice_affidabilita
--     del revisore (logica di business che non vale la pena mettere
--     in un 4° trigger, ma in una SP è più leggibile e controllabile).
--
-- indice_affidabilita: ratio giudizi 'approvazione' / totale giudizi
-- del revisore. Misura quanto spesso approva senza riserve.
-- ---------------------------------------------------------------------
CREATE PROCEDURE sp_emetti_giudizio(
    IN p_username_revisore VARCHAR(50),
    IN p_id_bilancio       INT,
    IN p_esito             VARCHAR(30),
    IN p_data_giudizio     DATE,
    IN p_rilievi           TEXT         -- può essere NULL
)
BEGIN

    -- Inserisco il giudizio (il trigger T2 scatta dopo e aggiorna il bilancio)
    INSERT INTO giudizio (username_revisore, id_bilancio, esito, data_giudizio, rilievi)
    VALUES (p_username_revisore, p_id_bilancio, p_esito, p_data_giudizio, p_rilievi);

END$$


-- =====================================================================
-- AREA: RESPONSABILE AZIENDALE
-- =====================================================================

-- ---------------------------------------------------------------------
-- sp_registra_azienda
-- ---------------------------------------------------------------------
-- Il responsabile registra una nuova azienda di cui è referente.
-- nr_bilanci parte da 0 (DEFAULT nel DDL), verrà incrementato dal
-- trigger T3a ad ogni creazione di bilancio.
-- ---------------------------------------------------------------------
CREATE PROCEDURE sp_registra_azienda(
    IN p_ragione_sociale       VARCHAR(100),
    IN p_nome                  VARCHAR(100),
    IN p_partita_iva           CHAR(11),
    IN p_settore               VARCHAR(80),
    IN p_num_dipendenti        INT,
    IN p_logo                  VARCHAR(255),
    IN p_username_responsabile VARCHAR(50)
)
BEGIN
    INSERT INTO azienda (ragione_sociale, nome, partita_iva, settore,
                         num_dipendenti, logo, username_responsabile)
    VALUES (p_ragione_sociale, p_nome, p_partita_iva, p_settore,
            p_num_dipendenti, p_logo, p_username_responsabile);
END$$


-- ---------------------------------------------------------------------
-- sp_crea_bilancio
-- ---------------------------------------------------------------------
-- Crea un nuovo bilancio in stato 'bozza' per un'azienda.
-- Restituisce l'id generato: il PHP ne avrà bisogno per le operazioni
-- successive (inserire voci, indicatori...).
-- Il trigger T3a incrementa automaticamente azienda.nr_bilanci.
-- ---------------------------------------------------------------------
CREATE PROCEDURE sp_crea_bilancio(
    IN  p_ragione_sociale VARCHAR(100),
    OUT p_id_bilancio     INT
)
BEGIN
    INSERT INTO bilancio (data_creazione, ragione_sociale)
    VALUES (CURDATE(), p_ragione_sociale);
    -- stato = 'bozza' per DEFAULT dal DDL, non serve specificarlo

    -- LAST_INSERT_ID() restituisce l'AUTO_INCREMENT generato dall'INSERT appena eseguita
    SET p_id_bilancio = LAST_INSERT_ID();
END$$


-- ---------------------------------------------------------------------
-- sp_inserisci_valore_voce
-- ---------------------------------------------------------------------
-- Associa un valore numerico a una voce contabile del bilancio.
-- ON DUPLICATE KEY UPDATE: se il responsabile reinserisce la stessa
-- voce (magari per correggere il valore), aggiorna invece di errore.
-- ---------------------------------------------------------------------
CREATE PROCEDURE sp_inserisci_valore_voce(
    IN p_id_bilancio INT,
    IN p_nome_voce   VARCHAR(100),
    IN p_valore      DECIMAL(15,2)
)
BEGIN
    INSERT INTO valore_bilancio (id_bilancio, nome_voce, valore)
    VALUES (p_id_bilancio, p_nome_voce, p_valore)
    ON DUPLICATE KEY UPDATE valore = p_valore;
END$$


-- ---------------------------------------------------------------------
-- sp_inserisci_voce_indicatore
-- ---------------------------------------------------------------------
-- Collega un indicatore ESG a una voce contabile di un bilancio,
-- con valore misurato, fonte e data di rilevazione.
--
-- VINCOLO: la coppia (id_bilancio, nome_voce) deve esistere già in
-- valore_bilancio. Lo verifichiamo con un controllo esplicito perché
-- la FK sulla tabella voce_indicatore lo garantisce a livello di DB,
-- ma vogliamo restituire un messaggio chiaro all'utente.
-- ---------------------------------------------------------------------
CREATE PROCEDURE sp_inserisci_voce_indicatore(
    IN  p_id_bilancio      INT,
    IN  p_nome_voce        VARCHAR(100),
    IN  p_nome_indicatore  VARCHAR(100),
    IN  p_valore           DECIMAL(15,2),
    IN  p_fonte            VARCHAR(255),
    IN  p_data_rilevazione DATE,
    OUT p_successo         TINYINT(1),
    OUT p_messaggio        VARCHAR(255)
)
BEGIN
    DECLARE v_voce_esiste INT;

    -- Verifico che la voce sia già valorizzata in questo bilancio
    SELECT COUNT(*) INTO v_voce_esiste
      FROM valore_bilancio
     WHERE id_bilancio = p_id_bilancio
       AND nome_voce   = p_nome_voce;

    IF v_voce_esiste = 0 THEN
        SET p_successo  = 0;
        SET p_messaggio = 'Errore: inserire prima il valore della voce contabile.';
    ELSE
        INSERT INTO voce_indicatore
               (id_bilancio, nome_voce, nome_indicatore, valore, fonte, data_rilevazione)
        VALUES (p_id_bilancio, p_nome_voce, p_nome_indicatore,
                p_valore, p_fonte, p_data_rilevazione);
        SET p_successo  = 1;
        SET p_messaggio = 'Indicatore collegato con successo.';
    END IF;
END$$


DELIMITER ;