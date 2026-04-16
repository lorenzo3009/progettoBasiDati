CREATE DATABASE IF NOT EXISTS esg_balance;
USE esg_balance;

CREATE TABLE utente (
    username         VARCHAR(50)  NOT NULL,
    password         VARCHAR(255) NOT NULL,
    codice_fiscale   CHAR(16)     NOT NULL UNIQUE,
    data_nascita     DATE         NOT NULL,
    luogo_nascita    VARCHAR(100) NOT NULL,
    tipo             VARCHAR(20)  NOT NULL,

    PRIMARY KEY (username),
    CHECK (tipo IN ('revisore', 'responsabile', 'amministratore'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE revisore_esg (
    username           VARCHAR(50)   NOT NULL,
    num_revisioni      INT           NOT NULL DEFAULT 0,
    indice_affidabilita DECIMAL(3,2) NOT NULL DEFAULT 0.00,

    PRIMARY KEY (username),
    FOREIGN KEY (username) REFERENCES utente(username)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CHECK (num_revisioni >= 0),
    CHECK (indice_affidabilita BETWEEN 0 AND 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE responsabile_aziendale (
    username  VARCHAR(50)  NOT NULL,
    cv_pdf    VARCHAR(255),

    PRIMARY KEY (username),
    FOREIGN KEY (username) REFERENCES utente(username)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE amministratore (
    username  VARCHAR(50)  NOT NULL,

    PRIMARY KEY (username),
    FOREIGN KEY (username) REFERENCES utente(username)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE email (
    email     VARCHAR(150) NOT NULL,
    username  VARCHAR(50)  NOT NULL,

    PRIMARY KEY (email),
    FOREIGN KEY (username) REFERENCES utente(username)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE azienda (
    ragione_sociale   VARCHAR(100) NOT NULL,
    nome              VARCHAR(100) NOT NULL,
    partita_iva       CHAR(11)     NOT NULL UNIQUE,
    settore           VARCHAR(80)  NOT NULL,
    num_dipendenti    INT          NOT NULL,
    logo              VARCHAR(255),
    nr_bilanci        INT          NOT NULL DEFAULT 0,
    username_responsabile VARCHAR(50) NOT NULL,

    PRIMARY KEY (ragione_sociale),
    FOREIGN KEY (username_responsabile) REFERENCES responsabile_aziendale(username)
        ON UPDATE CASCADE,
    CHECK (num_dipendenti >= 0),
    CHECK (nr_bilanci >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bilancio (
    id_bilancio      INT          NOT NULL AUTO_INCREMENT,
    data_creazione   DATE         NOT NULL DEFAULT (CURRENT_DATE),
    stato            VARCHAR(20)  NOT NULL DEFAULT 'bozza',
    ragione_sociale  VARCHAR(100) NOT NULL,

    PRIMARY KEY (id_bilancio),
    FOREIGN KEY (ragione_sociale) REFERENCES azienda(ragione_sociale)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CHECK (stato IN ('bozza', 'in_revisione', 'approvato', 'respinto'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE voce_contabile (
    nome         VARCHAR(100) NOT NULL,
    descrizione  TEXT         NOT NULL,

    PRIMARY KEY (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE indicatore_esg (
    nome       VARCHAR(100) NOT NULL,
    immagine   VARCHAR(255),
    rilevanza  TINYINT      NOT NULL,

    PRIMARY KEY (nome),
    CHECK (rilevanza BETWEEN 0 AND 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ind_ambientale (
    nome              VARCHAR(100) NOT NULL,
    codice_normativa  VARCHAR(50)  NOT NULL,

    PRIMARY KEY (nome),
    FOREIGN KEY (nome) REFERENCES indicatore_esg(nome)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ind_sociale (
    nome       VARCHAR(100) NOT NULL,
    ambito     VARCHAR(100) NOT NULL,
    frequenza  VARCHAR(50)  NOT NULL,

    PRIMARY KEY (nome),
    FOREIGN KEY (nome) REFERENCES indicatore_esg(nome)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE competenza (
    nome  VARCHAR(100) NOT NULL,

    PRIMARY KEY (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE valore_bilancio (
    id_bilancio  INT            NOT NULL,
    nome_voce    VARCHAR(100)   NOT NULL,
    valore       DECIMAL(15,2)  NOT NULL,

    PRIMARY KEY (id_bilancio, nome_voce),
    FOREIGN KEY (id_bilancio) REFERENCES bilancio(id_bilancio)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (nome_voce) REFERENCES voce_contabile(nome)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE voce_indicatore (
    id_bilancio      INT            NOT NULL,
    nome_voce        VARCHAR(100)   NOT NULL,
    nome_indicatore  VARCHAR(100)   NOT NULL,
    valore           DECIMAL(15,2)  NOT NULL,
    fonte            VARCHAR(255)   NOT NULL,
    data_rilevazione DATE           NOT NULL,

    PRIMARY KEY (id_bilancio, nome_voce, nome_indicatore),
    FOREIGN KEY (id_bilancio, nome_voce)
        REFERENCES valore_bilancio(id_bilancio, nome_voce)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (nome_indicatore)
        REFERENCES indicatore_esg(nome)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE possesso_competenza (
    username         VARCHAR(50)  NOT NULL,
    nome_competenza  VARCHAR(100) NOT NULL,
    livello          TINYINT      NOT NULL,

    PRIMARY KEY (username, nome_competenza),
    FOREIGN KEY (username) REFERENCES revisore_esg(username)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (nome_competenza) REFERENCES competenza(nome)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CHECK (livello BETWEEN 0 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE revisione (
    username_revisore  VARCHAR(50)  NOT NULL,
    id_bilancio        INT          NOT NULL,

    PRIMARY KEY (username_revisore, id_bilancio),
    FOREIGN KEY (username_revisore) REFERENCES revisore_esg(username)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_bilancio) REFERENCES bilancio(id_bilancio)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE giudizio (
    username_revisore  VARCHAR(50)  NOT NULL,
    id_bilancio        INT          NOT NULL,
    esito              VARCHAR(30)  NOT NULL,
    data_giudizio      DATE         NOT NULL,
    rilievi            TEXT,

    PRIMARY KEY (username_revisore, id_bilancio),
    FOREIGN KEY (username_revisore, id_bilancio)
        REFERENCES revisione(username_revisore, id_bilancio)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CHECK (esito IN ('approvazione', 'approvazione_con_rilievi', 'respingimento'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE nota (
    username_revisore  VARCHAR(50)  NOT NULL,
    id_bilancio        INT          NOT NULL,
    nome_voce          VARCHAR(100) NOT NULL,
    data               DATE         NOT NULL,
    testo              TEXT         NOT NULL,

    PRIMARY KEY (username_revisore, id_bilancio, nome_voce),
    FOREIGN KEY (username_revisore) REFERENCES revisore_esg(username)
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_bilancio, nome_voce)
        REFERENCES valore_bilancio(id_bilancio, nome_voce)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
