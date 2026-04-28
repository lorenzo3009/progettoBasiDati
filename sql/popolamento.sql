-- =====================================================================
-- ESG-BALANCE  ::  popolamento.sql
-- =====================================================================
-- Dati di test per verificare schema, trigger, viste e procedure.
-- Password in CHIARO (sviluppo): saranno hashate dal PHP via password_hash().
--
-- Ordine FK-safe. I trigger agiranno automaticamente:
--   T1: insert revisione  -> bilancio passa a 'in_revisione'
--   T2: ultimo giudizio   -> bilancio passa a 'approvato' o 'respinto'
--   T3: insert bilancio   -> incremento azienda.nr_bilanci
--
-- Eseguire DOPO schema.sql + triggers.sql.
-- =====================================================================

USE esg_balance;


-- =====================================================================
-- 1. UTENTI
-- =====================================================================
INSERT INTO utente (username, password, codice_fiscale, data_nascita, luogo_nascita, tipo) VALUES
('admin1', 'admin1', 'DMNALN85A01H501A', '1985-01-01', 'Roma',    'amministratore'),
('admin2', 'admin2', 'DMNGRG87B02F205B', '1987-02-02', 'Milano',  'amministratore'),
('rev1',   'rev1',   'RVSANN90C03L219C', '1990-03-03', 'Torino',  'revisore'),
('rev2',   'rev2',   'RVSPLO88D04G273D', '1988-04-04', 'Napoli',  'revisore'),
('rev3',   'rev3',   'RVSCRL92E05H501E', '1992-05-05', 'Roma',    'revisore'),
('rev4',   'rev4',   'RVSFRN89F06A271F', '1989-06-06', 'Bari',    'revisore'),
('rev5',   'rev5',   'RVSGNN91G07F839G', '1991-07-07', 'Padova',  'revisore'),
('resp1',  'resp1',  'RSPMRA86H08L840H', '1986-08-08', 'Bologna', 'responsabile'),
('resp2',  'resp2',  'RSPLRA88I09F205I', '1988-09-09', 'Milano',  'responsabile'),
('resp3',  'resp3',  'RSPGPP87L10H501L', '1987-10-10', 'Roma',    'responsabile'),
('resp4',  'resp4',  'RSPNNA90M11D643M', '1990-11-11', 'Firenze', 'responsabile');

INSERT INTO amministratore (username) VALUES ('admin1'), ('admin2');

INSERT INTO revisore_esg (username) VALUES
('rev1'), ('rev2'), ('rev3'), ('rev4'), ('rev5');

INSERT INTO responsabile_aziendale (username, cv_pdf) VALUES
('resp1', 'cv_resp1.pdf'),
('resp2', 'cv_resp2.pdf'),
('resp3', NULL),
('resp4', 'cv_resp4.pdf');

INSERT INTO email (email, username) VALUES
('admin1@esg.it',          'admin1'),
('admin2@esg.it',          'admin2'),
('anna.rev@esg.it',        'rev1'),
('paolo.rev@esg.it',       'rev2'),
('carlo.rev@esg.it',       'rev3'),
('francesca.rev@esg.it',   'rev4'),
('giovanni.rev@esg.it',    'rev5'),
('mario@ecotech.it',       'resp1'),
('mario.privato@gmail.com','resp1'),
('laura@solar.it',         'resp2'),
('giuseppe@biofood.it',    'resp3'),
('anna@cleanwater.it',     'resp4');


-- =====================================================================
-- 2. VOCI CONTABILI
-- =====================================================================
INSERT INTO voce_contabile (nome, descrizione) VALUES
('Ricavi',          'Ricavi totali da vendite e servizi'),
('Costi_Operativi', 'Spese operative al netto del personale'),
('Costo_Personale', 'Stipendi, contributi e benefit'),
('Utile_Netto',     'Utile dopo imposte'),
('EBITDA',          'Utile prima di interessi, tasse, ammortamenti'),
('Investimenti_RD', 'Spese in ricerca e sviluppo'),
('Imposte',         'Imposte dirette e indirette'),
('Ammortamenti',    'Quote di ammortamento immobilizzazioni');


-- =====================================================================
-- 3. INDICATORI ESG (+ ISA parziale)
-- =====================================================================
INSERT INTO indicatore_esg (nome, immagine, rilevanza) VALUES
('Emissioni_CO2',       'co2.png',        10),
('Consumo_Acqua',       'acqua.png',       8),
('Energia_Rinnovabile', 'energia.png',     9),
('Rifiuti_Riciclati',   'rifiuti.png',     7),
('Diversita_Genere',    'diversita.png',   8),
('Sicurezza_Lavoro',    'sicurezza.png',   9),
('Formazione',          'formazione.png',  6),
('Soddisfazione_Dip',   'sodd.png',        7),
('Codice_Etico',        'etica.png',       5),
('Trasparenza_Fiscale', 'tax.png',         6),
('Whistleblowing',      'whistle.png',     4),
('Governance',          'gov.png',         8);

INSERT INTO ind_ambientale (nome, codice_normativa) VALUES
('Emissioni_CO2',       'GHG-Protocol-Scope1'),
('Consumo_Acqua',       'ISO-14046'),
('Energia_Rinnovabile', 'EU-Taxonomy-Climate'),
('Rifiuti_Riciclati',   'GRI-306');

INSERT INTO ind_sociale (nome, ambito, frequenza) VALUES
('Diversita_Genere',  'Risorse Umane',      'Annuale'),
('Sicurezza_Lavoro',  'Salute e sicurezza', 'Trimestrale'),
('Formazione',        'Sviluppo personale', 'Annuale'),
('Soddisfazione_Dip', 'Clima aziendale',    'Semestrale');


-- =====================================================================
-- 4. COMPETENZE + POSSESSO
-- =====================================================================
INSERT INTO competenza (nome) VALUES
('Bilanci_Finanziari'),
('Sostenibilita'),
('Normativa_ESG'),
('Audit'),
('Reportistica'),
('Diritto_Ambientale');

INSERT INTO possesso_competenza (username, nome_competenza, livello) VALUES
('rev1', 'Bilanci_Finanziari', 5),
('rev1', 'Audit',              4),
('rev2', 'Sostenibilita',      5),
('rev2', 'Normativa_ESG',      4),
('rev3', 'Diritto_Ambientale', 5),
('rev3', 'Sostenibilita',      3),
('rev4', 'Reportistica',       4),
('rev4', 'Bilanci_Finanziari', 3),
('rev5', 'Audit',              5),
('rev5', 'Normativa_ESG',      3);


-- =====================================================================
-- 5. AZIENDE (nr_bilanci aggiornato dal trigger T3)
-- =====================================================================
INSERT INTO azienda (ragione_sociale, nome, partita_iva, settore, num_dipendenti, logo, username_responsabile) VALUES
('EcoTech S.r.l.',         'EcoTech',      '12345678901', 'Tecnologia',     50,  'ecotech.png', 'resp1'),
('GreenFuture S.p.A.',     'GreenFuture',  '12345678902', 'Energia',        120, 'green.png',   'resp1'),
('CircularEcon S.p.A.',    'CircularEcon', '12345678903', 'Riciclo',        80,  'circ.png',    'resp1'),
('SolarPower Italia',      'SolarPower',   '12345678904', 'Energia',        200, 'solar.png',   'resp2'),
('WindEnergy S.r.l.',      'WindEnergy',   '12345678905', 'Energia',        90,  'wind.png',    'resp2'),
('ZeroWaste Italia',       'ZeroWaste',    '12345678906', 'Riciclo',        45,  'zero.png',    'resp2'),
('BioFood S.p.A.',         'BioFood',      '12345678907', 'Alimentare',     150, 'bio.png',     'resp3'),
('AgriSostenibile S.r.l.', 'AgriSost',     '12345678908', 'Agricoltura',    60,  'agri.png',    'resp3'),
('CleanWater S.r.l.',      'CleanWater',   '12345678909', 'Servizi idrici', 70,  'water.png',   'resp4'),
('SmartCity Solutions',    'SmartCity',    '12345678910', 'Tecnologia',     110, 'smart.png',   'resp4');


-- =====================================================================
-- 6. BILANCI (trigger T3 incrementa nr_bilanci)
-- =====================================================================
INSERT INTO bilancio (id_bilancio, data_creazione, ragione_sociale) VALUES
(1,  '2025-01-15', 'EcoTech S.r.l.'),
(2,  '2025-04-20', 'EcoTech S.r.l.'),
(3,  '2025-09-10', 'EcoTech S.r.l.'),
(4,  '2025-02-10', 'GreenFuture S.p.A.'),
(5,  '2025-06-15', 'GreenFuture S.p.A.'),
(6,  '2025-11-01', 'GreenFuture S.p.A.'),
(7,  '2025-03-12', 'CircularEcon S.p.A.'),
(8,  '2025-07-22', 'CircularEcon S.p.A.'),
(9,  '2025-10-05', 'CircularEcon S.p.A.'),
(10, '2025-01-30', 'SolarPower Italia'),
(11, '2025-05-18', 'SolarPower Italia'),
(12, '2025-09-25', 'SolarPower Italia'),
(13, '2025-02-22', 'WindEnergy S.r.l.'),
(14, '2025-06-30', 'WindEnergy S.r.l.'),
(15, '2025-11-15', 'WindEnergy S.r.l.'),
(16, '2025-03-05', 'ZeroWaste Italia'),
(17, '2025-07-10', 'ZeroWaste Italia'),
(18, '2025-12-01', 'ZeroWaste Italia'),
(19, '2025-01-08', 'BioFood S.p.A.'),
(20, '2025-05-25', 'BioFood S.p.A.'),
(21, '2025-10-18', 'BioFood S.p.A.'),
(22, '2025-04-02', 'AgriSostenibile S.r.l.'),
(23, '2025-08-12', 'AgriSostenibile S.r.l.'),
(24, '2025-11-28', 'AgriSostenibile S.r.l.'),
(25, '2025-02-18', 'CleanWater S.r.l.'),
(26, '2025-06-08', 'CleanWater S.r.l.'),
(27, '2025-10-22', 'CleanWater S.r.l.'),
(28, '2025-03-20', 'SmartCity Solutions'),
(29, '2025-08-05', 'SmartCity Solutions'),
(30, '2025-12-10', 'SmartCity Solutions');


-- =====================================================================
-- 7. VALORE_BILANCIO
-- (incluse le 4 righe extra per Costo_Personale e Investimenti_RD
--  necessarie come "ancora" alle righe in voce_indicatore)
-- =====================================================================
INSERT INTO valore_bilancio (id_bilancio, nome_voce, valore) VALUES
-- EcoTech bilancio 1
(1, 'Ricavi',          1500000.00), (1, 'Costi_Operativi', 800000.00),
(1, 'Utile_Netto',     250000.00),  (1, 'Investimenti_RD', 120000.00),
(1, 'Costo_Personale', 400000.00),
-- EcoTech bilancio 2
(2, 'Ricavi',          1750000.00), (2, 'Costi_Operativi', 900000.00),
(2, 'Utile_Netto',     310000.00),  (2, 'Investimenti_RD', 150000.00),
(2, 'Costo_Personale', 450000.00),
-- EcoTech bilancio 3
(3, 'Ricavi',          1900000.00), (3, 'Costi_Operativi', 950000.00),
(3, 'Utile_Netto',     350000.00),  (3, 'EBITDA',          480000.00),
(3, 'Costo_Personale', 480000.00),  (3, 'Investimenti_RD', 180000.00),
-- GreenFuture bilancio 5
(5, 'Ricavi',          3200000.00), (5, 'Costi_Operativi', 2100000.00),
(5, 'Utile_Netto',     450000.00),  (5, 'EBITDA',          720000.00),
-- GreenFuture bilancio 6
(6, 'Ricavi',          2800000.00), (6, 'Costi_Operativi', 2500000.00),
(6, 'Utile_Netto',      90000.00),  (6, 'EBITDA',          280000.00),
-- CircularEcon
(8, 'Ricavi',          1200000.00), (8, 'Utile_Netto',     180000.00),
(9, 'Ricavi',          1350000.00), (9, 'Utile_Netto',     200000.00),
-- SolarPower
(11,'Ricavi',          5500000.00), (11,'Utile_Netto',     820000.00),
(12,'Ricavi',          4900000.00), (12,'Utile_Netto',     150000.00),
-- WindEnergy
(13,'Ricavi',          2100000.00), (13,'Utile_Netto',      50000.00),
(14,'Ricavi',          1800000.00), (14,'Utile_Netto',     -30000.00),
(15,'Ricavi',          2400000.00), (15,'Utile_Netto',     180000.00),
-- ZeroWaste
(18,'Ricavi',           950000.00), (18,'Utile_Netto',     120000.00),
-- BioFood
(20,'Ricavi',          4200000.00), (20,'Utile_Netto',     580000.00),
(21,'Ricavi',          3900000.00), (21,'Utile_Netto',     200000.00),
-- AgriSost
(23,'Ricavi',          1600000.00), (23,'Utile_Netto',     220000.00),
(24,'Ricavi',          1750000.00), (24,'Utile_Netto',     250000.00),
-- CleanWater
(26,'Ricavi',          2200000.00), (26,'Utile_Netto',     320000.00),
(27,'Ricavi',          2400000.00), (27,'Utile_Netto',     350000.00),
-- SmartCity
(30,'Ricavi',          3100000.00), (30,'Utile_Netto',     420000.00);


-- =====================================================================
-- 8. VOCE_INDICATORE
-- =====================================================================
INSERT INTO voce_indicatore (id_bilancio, nome_voce, nome_indicatore, valore, fonte, data_rilevazione) VALUES
(1,'Costi_Operativi','Emissioni_CO2',       45.5, 'Report interno',  '2025-01-10'),
(1,'Costi_Operativi','Energia_Rinnovabile', 78.0, 'Bolletta GSE',    '2025-01-10'),
(1,'Costo_Personale','Diversita_Genere',    52.0, 'HR survey',       '2025-01-12'),
(1,'Costo_Personale','Formazione',          80.0, 'HR report',       '2025-01-12'),
(1,'Investimenti_RD','Governance',          90.0, 'Audit interno',   '2025-01-14'),
(2,'Costi_Operativi','Emissioni_CO2',       40.0, 'Report interno',  '2025-04-15'),
(2,'Costi_Operativi','Consumo_Acqua',       1200, 'Misuratore',      '2025-04-15'),
(2,'Costo_Personale','Sicurezza_Lavoro',    98.0, 'Verbale RSPP',    '2025-04-18'),
(2,'Investimenti_RD','Codice_Etico',        85.0, 'Audit interno',   '2025-04-18'),
(3,'Costi_Operativi','Emissioni_CO2',       35.0, 'Report interno',  '2025-09-05'),
(3,'Costi_Operativi','Energia_Rinnovabile', 92.0, 'Bolletta GSE',    '2025-09-05'),
(3,'Costi_Operativi','Rifiuti_Riciclati',   85.0, 'Rapporto rifiuti','2025-09-05'),
(3,'Costo_Personale','Diversita_Genere',    55.0, 'HR survey',       '2025-09-08'),
(3,'Costo_Personale','Soddisfazione_Dip',   78.0, 'Sondaggio',       '2025-09-08'),
(3,'Investimenti_RD','Whistleblowing',      95.0, 'Audit',           '2025-09-09'),
(5,'Ricavi','Energia_Rinnovabile', 65.0, 'GSE',          '2025-06-10'),
(5,'EBITDA','Governance',          75.0, 'Audit',        '2025-06-10'),
(6,'Ricavi','Emissioni_CO2',       80.0, 'Report',       '2025-10-25'),
(8,'Ricavi','Rifiuti_Riciclati',   70.0, 'Report',       '2025-07-15'),
(9,'Ricavi','Rifiuti_Riciclati',   72.0, 'Report',       '2025-09-30'),
(9,'Ricavi','Codice_Etico',        80.0, 'Audit',        '2025-09-30'),
(11,'Ricavi','Energia_Rinnovabile',95.0, 'GSE',          '2025-05-12'),
(11,'Ricavi','Emissioni_CO2',      20.0, 'Report',       '2025-05-12'),
(12,'Ricavi','Energia_Rinnovabile',88.0, 'GSE',          '2025-09-20'),
(13,'Ricavi','Energia_Rinnovabile',85.0, 'GSE',          '2025-02-18'),
(15,'Ricavi','Sicurezza_Lavoro',   88.0, 'RSPP',         '2025-11-10'),
(18,'Ricavi','Rifiuti_Riciclati',  90.0, 'Report',       '2025-11-25'),
(20,'Ricavi','Diversita_Genere',   48.0, 'HR',           '2025-05-20'),
(20,'Ricavi','Codice_Etico',       82.0, 'Audit',        '2025-05-20'),
(23,'Ricavi','Consumo_Acqua',      850, 'Misuratore',    '2025-08-05'),
(24,'Ricavi','Consumo_Acqua',      820, 'Misuratore',    '2025-11-20'),
(26,'Ricavi','Consumo_Acqua',      450, 'Misuratore',    '2025-06-01'),
(27,'Ricavi','Consumo_Acqua',      430, 'Misuratore',    '2025-10-15'),
(30,'Ricavi','Governance',         88.0, 'Audit',        '2025-12-05');


-- =====================================================================
-- 9. REVISIONI (trigger T1: bozza -> in_revisione)
-- =====================================================================
INSERT INTO revisione (username_revisore, id_bilancio) VALUES
('rev1', 1), ('rev2', 1),
('rev1', 2), ('rev3', 2),
('rev2', 3),
('rev2', 5), ('rev4', 5),
('rev1', 6), ('rev5', 6),
('rev3', 8),
('rev2', 9), ('rev5', 9),
('rev1', 10),
('rev1', 11), ('rev4', 11),
('rev2', 12),
('rev1', 13),
('rev3', 14), ('rev5', 14),
('rev4', 15),
('rev2', 17),
('rev2', 18),
('rev1', 20), ('rev2', 20),
('rev3', 21), ('rev4', 21),
('rev5', 23),
('rev1', 24), ('rev3', 24),
('rev2', 26), ('rev4', 26),
('rev1', 27),
('rev3', 29),
('rev3', 30), ('rev5', 30);


-- =====================================================================
-- 10. NOTE
-- =====================================================================
INSERT INTO nota (username_revisore, id_bilancio, nome_voce, data, testo) VALUES
('rev2', 5,  'Costi_Operativi', '2025-06-25', 'Costi operativi cresciuti oltre il previsto.'),
('rev4', 5,  'EBITDA',          '2025-06-26', 'EBITDA in linea con il settore.'),
('rev5', 6,  'Costi_Operativi', '2025-11-10', 'Margini insufficienti, sostenibilita finanziaria a rischio.'),
('rev2', 9,  'Utile_Netto',     '2025-10-15', 'Utile in lieve crescita.'),
('rev3', 14, 'Utile_Netto',     '2025-07-15', 'Risultato negativo, situazione critica.'),
('rev1', 24, 'Ricavi',          '2025-12-10', 'Aumento ricavi positivo.'),
('rev4', 27, 'Utile_Netto',     '2025-11-05', 'Buon risultato di esercizio.');


-- =====================================================================
-- 11. GIUDIZI (trigger T2: ultimo giudizio -> approvato/respinto)
-- =====================================================================
INSERT INTO giudizio (username_revisore, id_bilancio, esito, data_giudizio, rilievi) VALUES
('rev1', 1, 'approvazione', '2025-02-10', NULL),
('rev2', 1, 'approvazione', '2025-02-12', NULL),
('rev1', 2, 'approvazione', '2025-05-15', NULL),
('rev3', 2, 'approvazione', '2025-05-16', NULL),
('rev2', 3, 'approvazione', '2025-10-01', NULL),
('rev2', 5, 'approvazione',             '2025-07-01', NULL),
('rev4', 5, 'approvazione_con_rilievi', '2025-07-02', 'Costi da contenere.'),
('rev1', 6, 'approvazione',             '2025-11-20', NULL),
('rev5', 6, 'respingimento',            '2025-11-22', 'Sostenibilita compromessa.'),
('rev3', 8, 'approvazione',             '2025-08-15', NULL),
('rev2', 9, 'approvazione_con_rilievi', '2025-10-25', 'Margini bassi.'),
('rev5', 9, 'approvazione',             '2025-10-26', NULL),
('rev1', 11,'approvazione',             '2025-06-20', NULL),
('rev4', 11,'approvazione',             '2025-06-21', NULL),
('rev2', 12,'respingimento',            '2025-10-15', 'Spese eccessive.'),
('rev1', 13,'respingimento',            '2025-03-30', 'Investimenti insufficienti.'),
('rev3', 14,'respingimento',            '2025-08-01', 'Risultato negativo.'),
('rev5', 14,'respingimento',            '2025-08-02', 'Quadro deteriorato.'),
('rev4', 15,'approvazione_con_rilievi', '2025-12-01', 'Migliorato ma fragile.'),
('rev2', 18,'approvazione_con_rilievi', '2025-12-20', 'Crescita modesta.'),
('rev1', 20,'approvazione',             '2025-06-30', NULL),
('rev2', 20,'approvazione',             '2025-07-02', NULL),
('rev3', 21,'respingimento',            '2025-11-25', 'Costi fuori controllo.'),
('rev4', 21,'approvazione',             '2025-11-26', NULL),
('rev5', 23,'approvazione',             '2025-09-20', NULL),
('rev1', 24,'approvazione',             '2025-12-15', NULL),
('rev3', 24,'approvazione_con_rilievi', '2025-12-16', 'Verifiche tecniche raccomandate.'),
('rev2', 26,'approvazione',             '2025-07-15', NULL),
('rev4', 26,'approvazione',             '2025-07-16', NULL),
('rev1', 27,'approvazione_con_rilievi', '2025-11-10', 'Adeguamenti normativi necessari.'),
('rev3', 30,'approvazione',             '2026-01-10', NULL),
('rev5', 30,'approvazione_con_rilievi', '2026-01-11', 'Trasparenza migliorabile.');