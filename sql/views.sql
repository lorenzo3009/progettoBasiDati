-- =====================================================================
-- ESG-BALANCE  ::  views.sql
-- ---------------------------------------------------------------------
-- 4 viste statistiche richieste dal regolamento.
-- Visibili a tutti gli utenti della piattaforma.
--
-- Ordine di caricamento: eseguire DOPO schema.sql e triggers.sql.
-- =====================================================================

USE esg_balance;

DROP VIEW IF EXISTS v_num_aziende;
DROP VIEW IF EXISTS v_num_revisori;
DROP VIEW IF EXISTS v_azienda_piu_affidabile;
DROP VIEW IF EXISTS v_classifica_bilanci_esg;


-- ---------------------------------------------------------------------
-- V1 :: v_num_aziende
-- ---------------------------------------------------------------------
-- Mostra il numero totale di aziende registrate in piattaforma.
-- COUNT(*) conta tutte le righe della tabella azienda.
-- ---------------------------------------------------------------------
CREATE VIEW v_num_aziende AS
SELECT COUNT(*) AS num_aziende
  FROM azienda;


-- ---------------------------------------------------------------------
-- V2 :: v_num_revisori
-- ---------------------------------------------------------------------
-- Mostra il numero totale di revisori ESG registrati in piattaforma.
-- Conto le righe in revisore_esg (non in utente), perche' solo chi
-- ha una riga qui e' effettivamente un revisore.
-- ---------------------------------------------------------------------
CREATE VIEW v_num_revisori AS
SELECT COUNT(*) AS num_revisori
  FROM revisore_esg;


-- ---------------------------------------------------------------------
-- V3 :: v_azienda_piu_affidabile
-- ---------------------------------------------------------------------
-- L'affidabilita' e' definita come: percentuale di bilanci approvati
-- senza rilievi (esito = 'approvazione') rispetto al totale dei
-- bilanci che hanno ricevuto almeno un giudizio.
--
-- Logica:
--   1. Parto dai bilanci di ogni azienda.
--   2. Per ogni bilancio, guardo se TUTTI i giudizi hanno esito
--      'approvazione' (cioe' nessun rilievo). Se si', quel bilancio
--      conta come "approvato senza rilievi".
--   3. Calcolo la percentuale: bilanci puliti / bilanci giudicati.
--   4. Prendo l'azienda col valore piu' alto.
--
-- COALESCE serve nel caso un'azienda non abbia bilanci giudicati:
-- evita divisione per zero restituendo 0.
--
-- Se due aziende hanno la stessa percentuale, le mostra entrambe.
-- ---------------------------------------------------------------------
CREATE VIEW v_azienda_piu_affidabile AS
SELECT a.ragione_sociale,
       a.nome,
       ROUND(
           SUM(
               CASE
                   -- Un bilancio e' "pulito" se ha almeno un giudizio
                   -- E tutti i suoi giudizi sono 'approvazione'
                   WHEN b.id_bilancio IN (
                       SELECT g1.id_bilancio
                         FROM giudizio g1
                        WHERE g1.id_bilancio = b.id_bilancio
                          -- NOT EXISTS: non esiste nessun giudizio su
                          -- questo bilancio che NON sia 'approvazione'
                          AND NOT EXISTS (
                              SELECT 1
                                FROM giudizio g2
                               WHERE g2.id_bilancio = g1.id_bilancio
                                 AND g2.esito <> 'approvazione'
                          )
                   )
                   THEN 1
                   ELSE 0
               END
           ) * 100.0
           /
           NULLIF(
               SUM(
                   CASE
                       WHEN EXISTS (
                           SELECT 1
                             FROM giudizio g3
                            WHERE g3.id_bilancio = b.id_bilancio
                       )
                       THEN 1
                       ELSE 0
                   END
               ),
               0  -- NULLIF(..., 0) trasforma 0 in NULL -> evita divisione per zero
           ),
           2  -- ROUND a 2 decimali
       ) AS percentuale_affidabilita
  FROM azienda a
  JOIN bilancio b ON b.ragione_sociale = a.ragione_sociale
 GROUP BY a.ragione_sociale, a.nome
HAVING percentuale_affidabilita IS NOT NULL
   AND percentuale_affidabilita = (
       -- Sottoselezione identica per trovare il MAX.
       -- Serve perche' in MySQL non puoi fare ORDER BY + LIMIT
       -- in una vista senza wrapping.
       SELECT MAX(perc)
         FROM (
             SELECT SUM(
                        CASE
                            WHEN b2.id_bilancio IN (
                                SELECT g4.id_bilancio
                                  FROM giudizio g4
                                 WHERE g4.id_bilancio = b2.id_bilancio
                                   AND NOT EXISTS (
                                       SELECT 1
                                         FROM giudizio g5
                                        WHERE g5.id_bilancio = g4.id_bilancio
                                          AND g5.esito <> 'approvazione'
                                   )
                            )
                            THEN 1
                            ELSE 0
                        END
                    ) * 100.0
                    /
                    NULLIF(
                        SUM(
                            CASE
                                WHEN EXISTS (
                                    SELECT 1
                                      FROM giudizio g6
                                     WHERE g6.id_bilancio = b2.id_bilancio
                                )
                                THEN 1
                                ELSE 0
                            END
                        ),
                        0
                    ) AS perc
               FROM azienda a2
               JOIN bilancio b2 ON b2.ragione_sociale = a2.ragione_sociale
              GROUP BY a2.ragione_sociale
         ) sub
   );


-- ---------------------------------------------------------------------
-- V4 :: v_classifica_bilanci_esg
-- ---------------------------------------------------------------------
-- Classifica dei bilanci ordinati per numero di indicatori ESG
-- collegati (righe in voce_indicatore).
--
-- LEFT JOIN: includo anche bilanci con 0 indicatori collegati
-- (avranno num_indicatori = 0).
-- COUNT(DISTINCT vi.nome_indicatore): conto gli indicatori unici,
-- non le righe totali (uno stesso indicatore puo' essere collegato
-- a piu' voci dello stesso bilancio).
-- ---------------------------------------------------------------------
CREATE VIEW v_classifica_bilanci_esg AS
SELECT b.id_bilancio,
       b.data_creazione,
       b.stato,
       a.ragione_sociale,
       a.nome AS nome_azienda,
       COUNT(DISTINCT vi.nome_indicatore) AS num_indicatori
  FROM bilancio b
  JOIN azienda a ON a.ragione_sociale = b.ragione_sociale
  LEFT JOIN voce_indicatore vi ON vi.id_bilancio = b.id_bilancio
 GROUP BY b.id_bilancio, b.data_creazione, b.stato,
          a.ragione_sociale, a.nome
 ORDER BY num_indicatori DESC;