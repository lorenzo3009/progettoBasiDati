USE esg_balance;

DROP TRIGGER IF EXISTS trg_assegna_revisore;
DROP TRIGGER IF EXISTS trg_inserisci_giudizio;
DROP TRIGGER IF EXISTS trg_bilancio_after_insert;
DROP TRIGGER IF EXISTS trg_bilancio_after_delete;
DROP TRIGGER IF EXISTS trg_giudizio_incrementa_num_revisioni;
DROP TRIGGER IF EXISTS trg_bilancio_aggiorna_affidabilita;

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



CREATE TRIGGER trg_giudizio_incrementa_num_revisioni
AFTER INSERT ON giudizio
FOR EACH ROW
BEGIN
    UPDATE revisore_esg
       SET num_revisioni = num_revisioni + 1
     WHERE username = NEW.username_revisore;
END$$



CREATE TRIGGER trg_bilancio_aggiorna_affidabilita
AFTER UPDATE ON bilancio
FOR EACH ROW
BEGIN
    IF NEW.stato IN ('approvato','respinto') AND OLD.stato <> NEW.stato THEN
        UPDATE revisore_esg r
           SET r.indice_affidabilita = (
               SELECT ROUND(
                   SUM(CASE
                       WHEN b2.stato = 'approvato' AND g2.esito IN ('approvazione','approvazione_con_rilievi') THEN 1
                       WHEN b2.stato = 'respinto'  AND g2.esito = 'respingimento' THEN 1
                       ELSE 0
                   END) / NULLIF(COUNT(*), 0),
                   2
               )
                 FROM giudizio g2
                 JOIN bilancio b2 ON b2.id_bilancio = g2.id_bilancio
                WHERE g2.username_revisore = r.username
                  AND b2.stato IN ('approvato','respinto')
           )
         WHERE r.username IN (
               SELECT username_revisore FROM giudizio WHERE id_bilancio = NEW.id_bilancio
         );
    END IF;
END$$


DELIMITER ;