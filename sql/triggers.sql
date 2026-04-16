USE esg_balance;

DROP TRIGGER IF EXISTS trg_assegna_revisore;
DROP TRIGGER IF EXISTS trg_inserisci_giudizio;
DROP TRIGGER IF EXISTS trg_bilancio_after_insert;
DROP TRIGGER IF EXISTS trg_bilancio_after_delete;

DELIMITER $$

CREATE TRIGGER trg_assegna_revisore
AFTER INSERT ON revisione
FOR EACH ROW
BEGIN
    UPDATE bilancio
       SET stato = 'in_revisione'
     WHERE id_bilancio = NEW.id_bilancio
       AND stato = 'bozza';
END$$



CREATE TRIGGER trg_inserisci_giudizio
AFTER INSERT ON giudizio
FOR EACH ROW
BEGIN
    DECLARE n_rev  INT;  
    DECLARE n_giu  INT;   
    DECLARE n_resp INT;   

    SELECT COUNT(*) INTO n_rev
      FROM revisione
     WHERE id_bilancio = NEW.id_bilancio;


    SELECT COUNT(*) INTO n_giu
      FROM giudizio
     WHERE id_bilancio = NEW.id_bilancio;

    IF n_rev = n_giu THEN

        SELECT COUNT(*) INTO n_resp
          FROM giudizio
         WHERE id_bilancio = NEW.id_bilancio
           AND esito = 'respingimento';

        IF n_resp > 0 THEN
            UPDATE bilancio
               SET stato = 'respinto'
             WHERE id_bilancio = NEW.id_bilancio;
        ELSE
            UPDATE bilancio
               SET stato = 'approvato'
             WHERE id_bilancio = NEW.id_bilancio;
        END IF;

    END IF;
END$$



CREATE TRIGGER trg_bilancio_after_insert
AFTER INSERT ON bilancio
FOR EACH ROW
BEGIN
    UPDATE azienda
       SET nr_bilanci = nr_bilanci + 1
     WHERE ragione_sociale = NEW.ragione_sociale;
END$$



CREATE TRIGGER trg_bilancio_after_delete
AFTER DELETE ON bilancio
FOR EACH ROW
BEGIN
    UPDATE azienda
       SET nr_bilanci = nr_bilanci - 1
     WHERE ragione_sociale = OLD.ragione_sociale;
END$$

DELIMITER ;