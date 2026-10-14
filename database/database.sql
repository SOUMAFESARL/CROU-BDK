CREATE TABLE campus (
                        id_campus INT PRIMARY KEY AUTO_INCREMENT,
                        code_campus VARCHAR(50) UNIQUE NOT NULL,        -- Code unique du campus
                        name VARCHAR(255) NOT NULL,                      -- Nom du campus
                        description TEXT NULL,                               -- Description détaillée
                        adresse TEXT NULL,                                   -- Adresse complète
                        ville_id INT,                             -- Ville
                        region_id INT,                            -- Région
                        countrie_id INT ,     -- Pays
                        code VARCHAR(150) UNIQUE NOT NULL,
                        telephone1 VARCHAR(20) NULL,                          -- Téléphone principal
                        telephone2 VARCHAR(20) NULL,                          -- Téléphone principal
                        telephone3 VARCHAR(20) NULL,                          -- Téléphone principal
                        email1 VARCHAR(255) NULL,                             -- Email de contact
                        email2 VARCHAR(255) NULL,                             -- Email de contact
                        email3 VARCHAR(255) NULL,                             -- Email de contact
                        logo VARCHAR(255) NULL,                             -- Logo de campus
                        slogan VARCHAR(255) NULL,                             -- Slogan de campus
                        devise VARCHAR(255) NULL,                             -- Devis de campus
                        directeur_code VARCHAR(150) NULL,                               -- Référence à l'utilisateur directeur
                        capacite_max INT DEFAULT 0,                               -- Capacité maximale d'étudiants
                        superficie DECIMAL(10,2) DEFAULT 0,                       -- Superficie en m²
                        date_creation DATE,                             -- Date de création
                        statut ENUM('actif', 'inactif', 'en_construction') DEFAULT 'actif',
                        created_by VARCHAR(150) NULL,
                        deleted_by VARCHAR(150) NULL,
                        updated_by VARCHAR(150) NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        deleted_at TIMESTAMP NULL

    -- ,FOREIGN KEY (directeur_id) REFERENCES users(id_user),
    -- INDEX idx_code (code_campus),
    -- INDEX idx_statut (statut)
);

CREATE TABLE cite (
                      id_cite INT PRIMARY KEY AUTO_INCREMENT,
                      code_cite VARCHAR(50) UNIQUE NOT NULL,          -- Code unique de la cité
                      code VARCHAR(150) UNIQUE NOT NULL,          -- Code unique de la cité
                      name VARCHAR(255) NOT NULL,                      -- Nom de la cité
                      type_cite_code VARCHAR(150) NOT NULL,                      -- Type de cité
                      campus_code VARCHAR(150) NOT NULL,                         -- Campus auquel appartient la cité
                      adresse TEXT NULL,                                   -- Adresse dans le campus
                      capacite_max INT DEFAULT 0,                               -- Capacité maximale d'étudiants
                      nombre_batiments INT DEFAULT 0,                 -- Nombre de bâtiments
                      superficie DECIMAL(10,2) DEFAULT 0,                       -- Superficie en m²
                      annee_construction YEAR NULL,                        -- Année de construction
                      responsable_code VARCHAR(150),                             -- Responsable de la cité
                      telephone1 VARCHAR(20) NULL,                          -- Téléphone de la cité
                      telephone2 VARCHAR(20) NULL,                          -- Téléphone de la cité
                      telephone3 VARCHAR(20) NULL,                          -- Téléphone de la cité
                      email1 VARCHAR(255) NULL,                             -- Email de la cité
                      email2 VARCHAR(255) NULL,                             -- Email de la cité
                      longitude VARCHAR(255) NULL,                             -- Longitude de la cité
                      latitude VARCHAR(255) NULL,                             -- Latitude de la cité
                      reglement_interieur_id INT NULL,                       -- Règlement intérieur spécifique
    -- equipements TEXT,                               -- Équipements communs (JSON)
    -- services_id TEXT,                                  -- Services offerts (JSON)
                      statut ENUM('ouvert', 'ferme', 'en_renovation', 'en_construction') DEFAULT 'ouvert',
                      created_by VARCHAR(150) NULL,
                      deleted_by VARCHAR(150) NULL,
                      updated_by VARCHAR(150) NULL,
                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      deleted_at TIMESTAMP NULL

    -- FOREIGN KEY (type_cite_id) REFERENCES type_cite(id_type_cite),
    --  FOREIGN KEY (campus_id) REFERENCES campus(id_campus),
    -- FOREIGN KEY (responsable_id) REFERENCES users(id_user),
    --  INDEX idx_campus (campus_id),
    --  INDEX idx_type (type_cite_id),
    -- INDEX idx_statut (statut)
);

CREATE TABLE reglement_interieur (
                                     id_reglement INT PRIMARY KEY AUTO_INCREMENT,
                                     code_reglement VARCHAR(50) UNIQUE NOT NULL,     -- Code du règlement
                                     titre VARCHAR(255) NOT NULL,                    -- Titre du règlement
                                     version VARCHAR(20) NOT NULL,                   -- Version (ex: "1.0", "2024-v2")
                                     type_entite ENUM('campus', 'cite', 'batiment', 'chambre') NOT NULL,
                                     entite_id INT NOT NULL,                         -- ID de l'entité concernée
                                     contenu TEXT NOT NULL,                          -- Contenu détaillé du règlement
                                     date_application DATE NOT NULL,                 -- Date d'application
                                     date_expiration DATE,                           -- Date d'expiration (si applicable)
                                     statut ENUM('actif', 'inactif', 'en_revision') DEFAULT 'actif',
                                     created_by VARCHAR(150) NULL,                                 -- Créé par
                                     deleted_by VARCHAR(150) NULL,
                                     updated_by VARCHAR(150) NULL,
                                     approved_by VARCHAR(150) NULL,                                -- Approuvé par
                                     date_approbation DATE,
                                     fichier_url VARCHAR(500),                       -- URL du fichier PDF
                                     signature_requise BOOLEAN DEFAULT TRUE,         -- Si signature obligatoire
                                     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                     deleted_at TIMESTAMP NULL
                                    -- FOREIGN KEY (created_by) REFERENCES users(id_user),
                                    -- FOREIGN KEY (approved_by) REFERENCES users(id_user),
                                    -- INDEX idx_entite (type_entite, entite_id),
                                   --  INDEX idx_statut (statut),
                                   --  INDEX idx_date_app (date_application)
);

CREATE TABLE type_cite (
                           id_type_cite INT PRIMARY KEY AUTO_INCREMENT,
                           code_type VARCHAR(50) UNIQUE NOT NULL,          -- Code du type
                           name VARCHAR(250) NOT NULL,                      -- Nom du type
                           description TEXT NULL,                               -- Description
                           categorie ENUM('universitaire', 'privee', 'mixte', 'residence') NOT NULL,
                           public_cible ENUM('etudiant', 'enseignant', 'personnel', 'tous') DEFAULT 'etudiant',
                           standard_equipements TEXT NULL,                      -- Équipements standards (JSON)
                           services_inclus TEXT NULL,                           -- Services inclus (JSON)
                           conditions_acces TEXT NULL,                          -- Conditions d'accès
                           duree_min_sejour INT NULL,                           -- Durée minimale de séjour (jours)
                           duree_max_sejour INT NULL,                           -- Durée maximale de séjour (jours)
                           age_min INT DEFAULT 0,                                    -- Âge minimum
                           age_max INT DEFAULT 0,                                    -- Âge maximum
                           is_active BOOLEAN DEFAULT TRUE,
                           created_by VARCHAR(150) NULL,                                 -- Créé par
                           updated_by VARCHAR(150) NULL,
                           created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                           updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

                      --     INDEX idx_categorie (categorie),
                      --     INDEX idx_actif (actif)
);

CREATE TABLE type_equipement (
                                 id_type_equipement INT PRIMARY KEY AUTO_INCREMENT,
                                 code_type VARCHAR(150) UNIQUE NOT NULL,          -- Code du type d'équipement
                                 name VARCHAR(100) NOT NULL,                      -- Nom de l'équipement
                                 categorie ENUM('mobilier', 'electronique', 'cuisine', 'sdb', 'autre') NOT NULL,
                                 description TEXT NULL,                               -- Description
                                 marque VARCHAR(100) NULL,                            -- Marque standard
                                 modele VARCHAR(100) NULL,                            -- Modèle standard
                                 duree_vie_estimee INT,                          -- Durée de vie estimée (mois)
                                 cout_moyen DECIMAL(10,2) DEFAULT 0,                       -- Coût moyen d'achat
                                 cout_maintenance DECIMAL(10,2) DEFAULT 0,                 -- Coût moyen de maintenance annuel
                                 est_consommable BOOLEAN DEFAULT FALSE,          -- Si c'est un consommable
                                 unite_mesure VARCHAR(20) NULL,                       -- Unité de mesure (pièce, litre, etc.)
                                 specifications_techniques TEXT,                 -- Spécifications techniques (JSON)
                                 is_active BOOLEAN DEFAULT TRUE,
                                 created_by VARCHAR(150) NULL,                                 -- Créé par
                                 updated_by VARCHAR(150) NULL,
                                 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

                             --    INDEX idx_categorie (categorie),
                              --   INDEX idx_actif (actif)
);

CREATE TABLE affectation_equipement_type_equipement (
                                                        id_affectation INT PRIMARY KEY AUTO_INCREMENT,
                                                        equipement_id INT NOT NULL,                     -- Référence à l'équipement
                                                        type_equipement_id INT NOT NULL,               -- Référence au type d'équipement
                                                        quantite INT DEFAULT 1,                         -- Quantité affectée
                                                        date_affectation DATE NOT NULL,                -- Date d'affectation
                                                        date_retrait DATE,                             -- Date de retrait (si applicable)
                                                        etat ENUM('neuf', 'bon', 'moyen', 'mauvais', 'hors_service') DEFAULT 'neuf',
                                                        numero_serie VARCHAR(100) NULL,                      -- Numéro de série
                                                        garantie_jusque DATE,                           -- Date de fin de garantie
                                                        cout_acquisition DECIMAL(10,2) DEFAULT 0,                 -- Coût d'acquisition
                                                        localisation VARCHAR(255) NULL,                      -- Localisation précise
                                                        observations TEXT NULL,                              -- Observations
                                                        created_by VARCHAR(150) NULL,                                 -- Créé par
                                                        updated_by VARCHAR(150) NULL,                                 -- Modifié par
                                                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

                                                     --   FOREIGN KEY (equipement_id) REFERENCES equipement(id_equipement),
                                                     --   FOREIGN KEY (type_equipement_id) REFERENCES type_equipement(id_type_equipement),
                                                     --   FOREIGN KEY (created_by) REFERENCES users(id_user),
                                                     --   FOREIGN KEY (updated_by) REFERENCES users(id_user),
                                                     --   UNIQUE KEY uk_equipement_type (equipement_id, type_equipement_id, numero_serie),
                                                     --   INDEX idx_etat (etat),
                                                      --  INDEX idx_equipement (equipement_id),
                                                     --   INDEX idx_type (type_equipement_id)
);

CREATE TABLE batiment (
                          id_batiment INT PRIMARY KEY AUTO_INCREMENT,
                          code_batiment VARCHAR(50) UNIQUE NOT NULL,      -- Code du bâtiment
                          name VARCHAR(255) NOT NULL,                      -- Nom du bâtiment
                          cite_code VARCHAR(150) NOT NULL,                           -- Cité à laquelle il appartient
                          numero VARCHAR(20) NULL,                             -- Numéro du bâtiment
                          adresse TEXT NULL,                                   -- Adresse dans la cité
                          nombre_etages INT DEFAULT 1,                    -- Nombre d'étages
                          annee_construction YEAR,                        -- Année de construction
                          surface_totale DECIMAL(10,2) DEFAULT 0,                   -- Surface totale en m²
                          type_construction ENUM('beton', 'bois', 'mixte', 'traditionnel') DEFAULT 'beton',
                          acces_handicape BOOLEAN DEFAULT FALSE,          -- Accessibilité handicapés
                          ascenseur BOOLEAN DEFAULT FALSE,                -- Présence d'ascenseur
                          gardiennage BOOLEAN DEFAULT FALSE,             -- Service de gardiennage

                          climatisation_centrale BOOLEAN DEFAULT FALSE,  -- Climatisation centrale
                          responsable_code INT,                             -- Responsable du bâtiment
                          telephone1 VARCHAR(20),                          -- Téléphone du bâtiment
                          telephone2 VARCHAR(20),                          -- Téléphone du bâtiment
                          capacite_max INT DEFAULT 0,                               -- Capacité maximale
                          chambres_total INT DEFAULT 0,                   -- Nombre total de chambres
                          chambres_disponibles INT DEFAULT 0,             -- Chambres disponibles
                          statut ENUM('actif', 'inactif', 'renovation', 'demolition') DEFAULT 'actif',
                          created_by VARCHAR(150) NULL,                                 -- Créé par
                          deleted_by VARCHAR(150) NULL,
                          updated_by VARCHAR(150) NULL,
                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                          deleted_at TIMESTAMP NULL
                        --  FOREIGN KEY (cite_id) REFERENCES cite(id_cite),
                        --  FOREIGN KEY (responsable_id) REFERENCES users(id_user),
                        --  INDEX idx_cite (cite_id),
                        --  INDEX idx_statut (statut),
                       --   INDEX idx_code (code_batiment)
);

CREATE TABLE type_chambre (
                              id_type_chambre INT PRIMARY KEY AUTO_INCREMENT,
                              code_type VARCHAR(50) UNIQUE NOT NULL,          -- Code du type
                              name VARCHAR(100) NOT NULL,                      -- Nom du type
                              description TEXT NULL,                               -- Description
                              capacite_max INT NOT NULL,                      -- Capacité maximale
                              surface DECIMAL(6,2) DEFAULT 0,                           -- Surface en m²
                              sanitaire_prive BOOLEAN DEFAULT FALSE,          -- Sanitaire privé
                              cuisine_privee BOOLEAN DEFAULT FALSE,           -- Cuisine privée
                              balcon BOOLEAN DEFAULT FALSE,                   -- Présence balcon
                              equipements_standards TEXT NULL,                     -- Équipements standards (JSON)
                              tarif_base DECIMAL(10,2) NOT NULL,              -- Tarif de base
                              caution_mois INT DEFAULT 1,                     -- Caution en mois
                              avance_mois INT DEFAULT 1,                     -- Caution en mois
                              duree_min_sejour INT DEFAULT 30,                -- Durée minimale (jours)
                              duree_max_sejour INT DEFAULT 365,               -- Durée maximale (jours)
                              conditions_speciales TEXT NULL,                      -- Conditions spéciales
                              is_active BOOLEAN DEFAULT TRUE,
                              created_by VARCHAR(150) NULL,                                 -- Créé par
                              updated_by VARCHAR(150) NULL,
                              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                              updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

                           --   INDEX idx_capacite (capacite_max),
                           --   INDEX idx_tarif (tarif_base),
                           --   INDEX idx_actif (actif)
);

CREATE TABLE niveau (
                        id_niveau INT PRIMARY KEY AUTO_INCREMENT,
                        batiment_code VARCHAR(150) NOT NULL,                       -- Bâtiment parent
                        numero_etage VARCHAR(150) NOT NULL,                      -- Numéro d'étage
                        code_niveau VARCHAR(50),                        -- Code de l'étage (ex: "RDC", "1er")
                        name VARCHAR(100),                               -- Nom de l'étage
                        surface DECIMAL(10,2) DEFAULT 0,                          -- Surface de l'étage
                        nombre_chambres INT DEFAULT 0,                  -- Nombre de chambres
                        chambres_disponibles INT DEFAULT 0,             -- Chambres disponibles
                        acces_ascenseur BOOLEAN DEFAULT FALSE,          -- Accès par ascenseur
                        acces_escalier BOOLEAN DEFAULT TRUE,            -- Accès par escalier
                        equipements_communs TEXT NULL,                       -- Équipements communs (JSON)
                        plan_url VARCHAR(500) NULL,                          -- URL du plan de l'étage
                        responsable_etage_code VARCHAR(150) NULL,                          -- Responsable d'étage
                        observations TEXT NULL,
                        created_by VARCHAR(150) NULL,                                 -- Créé par
                        updated_by VARCHAR(150) NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

                    --    FOREIGN KEY (batiment_id) REFERENCES batiment(id_batiment),
                    --    FOREIGN KEY (responsable_etage) REFERENCES users(id_user),
                    --    UNIQUE KEY uk_batiment_etage (batiment_id, numero_etage),
                    --    INDEX idx_batiment (batiment_id),
                    --    INDEX idx_numero (numero_etage)
);

CREATE TABLE lit (
                     id_lit INT PRIMARY KEY AUTO_INCREMENT,
                     code_lit VARCHAR(50) UNIQUE NOT NULL,           -- Code du lit
                     chambre_code VARCHAR(150) NOT NULL,                        -- Chambre à laquelle il appartient
                     numero_lit INT NOT NULL,                        -- Numéro du lit dans la chambre
                     type_lit ENUM('simple', 'superpose_haut', 'superpose_bas', 'mezzanine') DEFAULT 'simple',
                     dimensions VARCHAR(50) NULL,                         -- Dimensions (ex: "90x190")
                     materiel ENUM('bois', 'metal', 'mixte') DEFAULT 'metal',
                     matelas_type VARCHAR(100) NULL,                      -- Type de matelas
                     etat ENUM('excellent', 'bon', 'moyen', 'mauvais', 'hors_service') DEFAULT 'bon',
                     date_achat DATE,                                -- Date d'achat
                     garantie_jusque DATE,                           -- Fin de garantie
                     cout_achat DECIMAL(10,2) DEFAULT 0,                       -- Coût d'achat
                     numero_serie VARCHAR(100) NULL,                      -- Numéro de série
                     observations TEXT NULL,                              -- Observations
                     statut ENUM('disponible', 'occupe', 'maintenance', 'reserve', 'hors_service') DEFAULT 'disponible',
                     created_by VARCHAR(150) NULL,                                 -- Créé par
                     updated_by VARCHAR(150) NULL,
                     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

                    -- FOREIGN KEY (chambre_id) REFERENCES chambre(id_chambre),
                    -- UNIQUE KEY uk_chambre_numero (chambre_id, numero_lit),
                  --   INDEX idx_chambre (chambre_id),
                  --   INDEX idx_statut (statut),
                  --   INDEX idx_etat (etat)
);

CREATE TABLE chambre (
                         id_chambre INT PRIMARY KEY AUTO_INCREMENT,
                         code_chambre VARCHAR(50) UNIQUE NOT NULL,       -- Code unique de la chambre
                         batiment_code VARCHAR(150) NOT NULL,                       -- Bâtiment parent
                         niveau_code VARCHAR(150) NOT NULL,                         -- Étage/Niveau
                         numero_porte VARCHAR(20) NOT NULL,              -- Numéro de porte
                         type_chambre_code VARCHAR(150) NOT NULL,                   -- Type de chambre
                         superficie DECIMAL(6,2) DEFAULT 0,                        -- Superficie en m²
                         orientation ENUM('nord', 'sud', 'est', 'ouest') DEFAULT 'sud',
                         exposition VARCHAR(100) NULL,                        -- Exposition (vue)
                         capacite_max INT NOT NULL,                      -- Capacité maximale
                         lits_total INT DEFAULT 0,                       -- Nombre total de lits
                         lits_disponibles INT DEFAULT 0,                 -- Lits disponibles
                         equipements TEXT NULL,                               -- Équipements (JSON)
                         sanitaire_prive BOOLEAN DEFAULT FALSE,          -- Sanitaire privé
                         cuisine_privee BOOLEAN DEFAULT FALSE,           -- Cuisine privée
                         balcon BOOLEAN DEFAULT FALSE,                   -- Présence balcon
                         climatisation BOOLEAN DEFAULT FALSE,           -- Climatisation
                         chauffage BOOLEAN DEFAULT FALSE,                -- Chauffage
                         wifi BOOLEAN DEFAULT FALSE,                     -- Accès WiFi
                         telephone BOOLEAN DEFAULT FALSE,               -- Téléphone fixe
                         tv BOOLEAN DEFAULT FALSE,                      -- Télévision
                         frigo BOOLEAN DEFAULT FALSE,                   -- Réfrigérateur
                         prix_base DECIMAL(10,2) NOT NULL,               -- Prix de base
                         prix_actuel DECIMAL(10,2) DEFAULT 0,                      -- Prix actuel (peut varier)
                         statut ENUM('disponible', 'occupee', 'maintenance', 'reservee', 'fermee') DEFAULT 'disponible',
                         date_disponibilite DATE NULL,                        -- Date de disponibilité
                         dernier_nettoyage DATE NULL,                         -- Date du dernier nettoyage
                         prochain_nettoyage DATE NULL,                        -- Date du prochain nettoyage
                         observations TEXT NULL,                              -- Observations
                         plan_url VARCHAR(500) NULL,                          -- URL du plan de la chambre
                         photos TEXT NULL,                                    -- URLs des photos (JSON)
                         created_by VARCHAR(150) NULL,                                 -- Créé par
                         updated_by VARCHAR(150) NULL,
                         deleted_by VARCHAR(150) NULL,
                         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                         updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                         deleted_at TIMESTAMP NULL

                   --      FOREIGN KEY (batiment_id) REFERENCES batiment(id_batiment),
                    --     FOREIGN KEY (niveau_id) REFERENCES niveau(id_niveau),
                    --     FOREIGN KEY (type_chambre_id) REFERENCES type_chambre(id_type_chambre),
                     --    UNIQUE KEY uk_batiment_porte (batiment_id, numero_porte),
                   --      INDEX idx_statut (statut),
                    --     INDEX idx_type (type_chambre_id),
                   --      INDEX idx_batiment (batiment_id),
                  --       INDEX idx_niveau (niveau_id),
                  --       INDEX idx_prix (prix_actuel),
                  --       INDEX idx_disponibilite (date_disponibilite)
);

CREATE TABLE equipement (
                            id_equipement INT PRIMARY KEY AUTO_INCREMENT,
                            code_equipement VARCHAR(50) UNIQUE NOT NULL,    -- Code unique de l'équipement
                            name VARCHAR(255) NOT NULL,                      -- Nom de l'équipement
                            categorie ENUM('mobilier', 'electronique', 'cuisine', 'salle_bain', 'autre') NOT NULL,
                            marque VARCHAR(100),                            -- Marque
                            modele VARCHAR(100),                            -- Modèle
                            numero_serie VARCHAR(100) UNIQUE,               -- Numéro de série
                            date_achat DATE,                                -- Date d'achat
                            cout_achat DECIMAL(10,2),                       -- Coût d'achat
                            garantie_jusque DATE NULL,                           -- Fin de garantie
                            duree_vie_estimee INT NULL,                          -- Durée de vie estimée (mois)
                            etat ENUM('neuf', 'bon', 'moyen', 'mauvais', 'hors_service') DEFAULT 'neuf',
                            localisation_type ENUM('chambre', 'commun', 'bureau', 'entrepot') NOT NULL,
                            localisation_id VARCHAR(150) NULL,                            -- ID de la localisation
                            responsable_code VARCHAR(150) NULL,                             -- Responsable de l'équipement
                            fournisseur VARCHAR(255) NULL,                       -- Fournisseur
                            contact_fournisseur VARCHAR(255) NULL,               -- Contact fournisseur
                            specifications TEXT NULL,                            -- Spécifications techniques (JSON)
                            manuel_url VARCHAR(500) NULL,                        -- URL du manuel
                            photos TEXT NULL,                                    -- URLs des photos (JSON)
                            statut ENUM('en_service', 'maintenance', 'hors_service', 'retire', 'perdu') DEFAULT 'en_service',
                            date_derniere_maintenance DATE NULL,                 -- Date dernière maintenance
                            prochaine_maintenance DATE NULL,                     -- Date prochaine maintenance
                            observations TEXT NULL,                              -- Observations
                            created_by VARCHAR(150) NULL,                                 -- Créé par
                            updated_by VARCHAR(150) NULL,
                            deleted_by VARCHAR(150) NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            deleted_at TIMESTAMP NULL
                        --    FOREIGN KEY (responsable_id) REFERENCES users(id_user),
                        --    FOREIGN KEY (created_by) REFERENCES users(id_user),
                        --    FOREIGN KEY (updated_by) REFERENCES users(id_user),
                        --    INDEX idx_categorie (categorie),
                        --    INDEX idx_statut (statut),
                        --    INDEX idx_etat (etat),
                        --    INDEX idx_localisation (localisation_type, localisation_id),
                        --    INDEX idx_code (code_equipement)
);
CREATE TABLE genre(
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    name VARCHAR(100) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
CREATE TABLE type_utilisateur(
                      id INT PRIMARY KEY AUTO_INCREMENT,
                      name VARCHAR(100) NOT NULL,
                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)

    INSERT INTO TABLE Genre (name) VALUE('Homme'),('Femme')

INSERT INTO type_utilisateur (name) VALUES ('enseignant'),('administratif'),('technique'), ('securite');


CREATE TABLE users (
                       id_user INT PRIMARY KEY AUTO_INCREMENT,
                       matricule VARCHAR(50) UNIQUE  NULL,          -- Matricule unique
                       code VARCHAR(150) UNIQUE NOT NULL,          -- Code unique
                       name VARCHAR(100) NOT NULL,                      -- Nom
                       genre_id INT NOT NULL,                   -- Sexe
                       date_naissance DATE  NULL,                   -- Date de naissance
                       lieu_naissance VARCHAR(255),                    -- Lieu de naissance
                       nationalite VARCHAR(100) DEFAULT 'Ivoirienne',  -- Nationalité
                       cin VARCHAR(50) UNIQUE,                         -- Numéro CIN/CNI
                       date_delivrance_cin DATE,                       -- Date délivrance CIN
                       telephone_principal VARCHAR(20) NOT NULL,       -- Téléphone principal
                       telephone_secondaire VARCHAR(20),               -- Téléphone secondaire
                       email VARCHAR(255) UNIQUE NOT NULL,             -- Email principal
                       email_secondaire VARCHAR(255),                  -- Email secondaire
                       adresse varchar(255) NULL,                                   -- Adresse complète
                       ville_id INT,                             -- Ville
                       region_id INT,                             -- Region
                       photo VARCHAR(500),                             -- URL photo
                       type_utilisateur_id INT,
                       role_id INT NOT NULL,                           -- Rôle dans le système
                       campus_code VARCHAR(150),                           -- Établissement de rattachement
                       date_inscription DATE NOT NULL,                 -- Date d'inscription
                       password VARCHAR(255)  NULL,        -- Hash du mot de passe
                       statut ENUM(
                                    'actif',
                                    'inactif',
                                    'suspendu',
                                    'banni',
                                    'en_attente'
                                ) DEFAULT 'en_attente',
                       raison_suspension TEXT NULL,                         -- Raison de suspension

                       created_by VARCHAR(150) NULL,                                 -- Créé par
                       updated_by VARCHAR(150) NULL,                                 -- Modifié par
                       deleted_by VARCHAR(150) NULL,                                 -- Supprimé par

                       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                       updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                       deleted_at TIMESTAMP NULL
                    --   FOREIGN KEY (role_id) REFERENCES roles(id_role),
                    --   FOREIGN KEY (etablissement_id) REFERENCES Etablissement(id_etablissement),
                    --   FOREIGN KEY (created_by) REFERENCES users(id_user),
                    --   FOREIGN KEY (updated_by) REFERENCES users(id_user),

                    --  INDEX idx_matricule (matricule),
                   --    INDEX idx_email (email),
                    --   INDEX idx_type (type_utilisateur),
                   --    INDEX idx_statut (statut),
                     --  INDEX idx_etablissement (etablissement_id),
                    --   INDEX idx_role (role_id),
                     --  INDEX idx_date_naissance (date_naissance),
                   --    INDEX idx_nom_prenom (nom, prenom)
);

CREATE TABLE filiere (
                         id_filiere INT PRIMARY KEY AUTO_INCREMENT,
                         code_filiere VARCHAR(50) UNIQUE NOT NULL,       -- Code unique de la filière
                        name VARCHAR(150) ,
                        description VARCHAR(255) NULL,
                        departement_code VARCHAR(150) NULL,
                         created_by VARCHAR(150) NULL,                                 -- Créé par
                         updated_by VARCHAR(150) NULL,
                         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                         updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE niveau_etude (
                              id_niveau_etude INT PRIMARY KEY AUTO_INCREMENT,
                              code_niveau VARCHAR(20) UNIQUE NOT NULL,        -- Ex: "L1", "M2", "D1"
                              name VARCHAR(100) NOT NULL,                      -- Ex: "Licence 1"
                              abreviation VARCHAR(50) NULL,                          -- Ex: "L1"
                              description TEXT NULL,                               -- Description
                              created_by VARCHAR(150) NULL,                                 -- Créé par
                              updated_by VARCHAR(150) NULL,
                              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                              updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                            --  FOREIGN KEY (etablissement_id) REFERENCES Etablissement(id_etablissement),
                            --  FOREIGN KEY (responsable_niveau) REFERENCES users(id_user),
                            --  FOREIGN KEY (created_by) REFERENCES users(id_user),
                            --  FOREIGN KEY (updated_by) REFERENCES users(id_user),

                            --  INDEX idx_code (code_niveau),
                            --  INDEX idx_cycle (cycle),
                            --  INDEX idx_ordre (ordre_affiche),
                           --   INDEX idx_etablissement (etablissement_id),
                           --   UNIQUE KEY uk_cycle_annee (cycle, annee_cycle)
);


CREATE TABLE etudiant (
                          id_etudiant INT PRIMARY KEY AUTO_INCREMENT,

                          numero_inscription VARCHAR(50) UNIQUE NOT NULL, -- Numéro d'inscription
                          filiere_code VARCHAR(150) NOT NULL,                        -- Filière principale
                          niveau_etude_code VARCHAR(150) NOT NULL,                   -- Niveau actuel
                          campus_code VARCHAR(150) NOT NULL,                  -- Établissement

                          annee_academique VARCHAR(9) NOT NULL,           -- Format: 2024-2025
                          annee_entree YEAR NOT NULL,                     -- Année d'entrée
                          annee_sortie_prevu YEAR,                        -- Année de sortie prévue
                          boursier BOOLEAN DEFAULT FALSE,                 -- Bénéficiaire de bourse

                          situation_familiale ENUM(
                                'celibataire',
                                'marie',
                                'divorce',
                                'veuf'
                            ) DEFAULT 'celibataire',
                          nombre_enfants INT DEFAULT 0,                   -- Nombre d'enfants
                          profession_pere VARCHAR(255),                   -- Profession du père
                          profession_mere VARCHAR(255),                   -- Profession de la mère
                          contact_urgence_name VARCHAR(255),               -- Nom contact urgence
                          contact_urgence_telephone VARCHAR(20),          -- Téléphone urgence

                          etablissement_origine VARCHAR(250),             -- Établissement d'origine
                          diplome_obtenu VARCHAR(500),                    -- Dernier diplôme obtenu
                          moyenne_generale DECIMAL(4,2),                  -- Moyenne générale
                          credits_obtenus INT DEFAULT 0,                  -- Crédits obtenus
                          credits_restants INT,                           -- Crédits restants


                          assurance_maladie BOOLEAN DEFAULT FALSE,                 -- Assurance maladie
                          groupe_sanguin VARCHAR(5) NULL,                      -- Groupe sanguin
                          allergies TEXT NULL,                                  -- Allergies (JSON)
                          handicaps TEXT NULL,                                  -- Handicaps (JSON)
                          besoins_specifiques TEXT,                        -- Besoins spécifiques (JSON)

                          logement_actuel VARCHAR(255) NULL,                   -- Logement actuel
                          transport_utilise VARCHAR(100) NULL,                 -- Moyen de transport

                          observations TEXT NULL,                               -- Observations générales

                          created_by VARCHAR(150) NULL,                                 -- Créé par
                          updated_by VARCHAR(150) NULL,                                 -- Modifié par
                          deleted_by VARCHAR(150) NULL,                                 -- Supprimé par

                          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                          deleted_at TIMESTAMP NULL
                       --   FOREIGN KEY (user_id) REFERENCES users(id_user) ON DELETE CASCADE,
                       --   FOREIGN KEY (filiere_id) REFERENCES filiere(id_filiere),
                      --    FOREIGN KEY (niveau_etude_id) REFERENCES niveau_etude(id_niveau_etude),
                      --    FOREIGN KEY (etablissement_id) REFERENCES Etablissement(id_etablissement),
                       --   FOREIGN KEY (created_by) REFERENCES users(id_user),
                     --     FOREIGN KEY (updated_by) REFERENCES users(id_user),

                     --     INDEX idx_num_inscription (numero_inscription),
                      --    INDEX idx_filiere (filiere_id),
                     --     INDEX idx_niveau (niveau_etude_id),
                     --     INDEX idx_etablissement (etablissement_id),
                     --     INDEX idx_statut_acad (statut_academique),
                     --     INDEX idx_statut_fin (statut_financier),
                     --     INDEX idx_boursier (boursier),
                     --     INDEX idx_annee_entree (annee_entree)
);

CREATE TABLE departement (
                             id_departement INT PRIMARY KEY AUTO_INCREMENT,
                             code_departement VARCHAR(50) UNIQUE NOT NULL,   -- Code unique
                             name VARCHAR(255) NOT NULL,                      -- Nom complet
                             abreviation VARCHAR(100) NULL,                         -- Nom abrégé
                             description TEXT NULL,                               -- Description
                             campus_code VARCHAR(150) NOT NULL,                  -- Établissement parent
                             directeur_code VARCHAR(150) NULL,                               -- Directeur du département
                             directeur_adjoint_code VARCHAR(150) NULL,                       -- Directeur adjoint

                             adresse TEXT NULL,                                   -- Adresse du département
                             telephone VARCHAR(20) NULL,                          -- Téléphone
                             email VARCHAR(255) NULL,                             -- Email
                             site_web VARCHAR(500) NULL,                          -- Site web
                             annee_creation YEAR NULL,                            -- Année de création
                             nombre_filieres INT DEFAULT 0,                  -- Nombre de filières
                             nombre_enseignants INT DEFAULT 0,               -- Nombre d'enseignants
                             nombre_etudiants INT DEFAULT 0,                 -- Nombre d'étudiants
                             superficie DECIMAL(8,2) NULL,                        -- Superficie en m²
                             laboratoires TEXT NULL,                              -- Laboratoires (JSON)
                             equipements_principaux TEXT NULL,                    -- Équipements (JSON)
                             partenariats TEXT NULL,                              -- Partenariats (JSON)
                             budget_annuel DECIMAL(14,2) NULL,                    -- Budget annuel
                             logo VARCHAR(500) NULL,                              -- Logo du département
                             couleur_hex VARCHAR(7) NULL,                         -- Couleur représentative
                             statut ENUM('actif', 'inactif', 'fusionne', 'dissous') DEFAULT 'actif',
                             created_by VARCHAR(150) NULL,
                             updated_by VARCHAR(150) NULL,
                             deleted_by VARCHAR(150) NULL,
                             created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                             updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            deleted_at TIMESTAMP NULL
                          --   FOREIGN KEY (etablissement_id) REFERENCES Etablissement(id_etablissement),
                          --   FOREIGN KEY (directeur_id) REFERENCES users(id_user),
                          --   FOREIGN KEY (directeur_adjoint_id) REFERENCES users(id_user),
                          --   FOREIGN KEY (created_by) REFERENCES users(id_user),
                          --   FOREIGN KEY (updated_by) REFERENCES users(id_user),

                          --   INDEX idx_code (code_departement),
                          --   INDEX idx_etablissement (etablissement_id),
                          --   INDEX idx_domaine (domaine_principal),
                          --   INDEX idx_statut (statut),
                          --   INDEX idx_nom (nom)
);

CREATE TABLE document (
                               id_type_document INT PRIMARY KEY AUTO_INCREMENT,
                               code_type VARCHAR(50) UNIQUE NOT NULL,          -- Code unique
                               name VARCHAR(100) NOT NULL,                      -- Nom du type
                               description TEXT NULL,                               -- Description
                                code  VARCHAR(150) NULL,
                               obligatoire BOOLEAN DEFAULT FALSE,              -- Document obligatoire
                               delai_validite_mois INT NULL,                        -- Délai de validité
                               format_accepte VARCHAR(255) NULL,                    -- Formats acceptés (JSON)
                               taille_max_mo INT DEFAULT 10,                   -- Taille max en Mo
                               champs_requis TEXT NULL,                             -- Champs requis (JSON)
                               modele_url VARCHAR(500) NULL,                        -- URL du modèle
                               instructions TEXT NULL,                              -- Instructions de remplissage
                               etablissement_id VARCHAR(150) NULL,                           -- Établissement (si spécifique)
                               service_responsable VARCHAR(255) NULL,               -- Service responsable
                               workflow_validation TEXT NULL,                       -- Workflow de validation (JSON)
                               notifications TEXT NULL,                             -- Notifications (JSON)
                               statut ENUM('actif', 'inactif', 'archive') DEFAULT 'actif',
                               ordre_affiche INT DEFAULT 0,                              -- Ordre d'affichage
                               created_by VARCHAR(150) NULL,
                               updated_by VARCHAR(150) NULL,
                               created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                               updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

                        --       FOREIGN KEY (etablissement_id) REFERENCES Etablissement(id_etablissement),
                        --       FOREIGN KEY (created_by) REFERENCES users(id_user),
                        --       FOREIGN KEY (updated_by) REFERENCES users(id_user),

                        --       INDEX idx_code (code_type),
                       --        INDEX idx_categorie (categorie),
                        --       INDEX idx_obligatoire (obligatoire),
                        --       INDEX idx_etablissement (etablissement_id),
                        --       INDEX idx_statut (statut),
                       --        UNIQUE KEY uk_nom_etablissement (nom, etablissement_id)
);

CREATE TABLE document_etudiant (
                                   id_document INT PRIMARY KEY AUTO_INCREMENT,
                                   reference_document VARCHAR(100) UNIQUE NOT NULL, -- Référence unique
                                   document_code VARCHAR(150) NOT NULL,                  -- Type de document
                                   etudiant_code VARCHAR(150) NOT NULL,                       -- Étudiant concerné
                                   campus_code NULL,                  -- Établissement

                                   chemin_fichier VARCHAR(500) NOT NULL,           -- Chemin de stockage
                                   extension VARCHAR(10) NOT NULL,                 -- Extension (pdf, jpg, etc.)
                                   taille_octets INT NOT NULL,                     -- Taille en octets
                                   mime_type VARCHAR(100) NULL,                         -- Type MIME
                                   date_document DATE NULL,                             -- Date du document
                                   date_expiration DATE NULL,                           -- Date d'expiration
                                   date_depot TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Date de dépôt
                                   date_validation DATE NULL,                           -- Date de validation
                                   valide_par VARCHAR(150)  NULL,                                 -- Validé par
                                   statut ENUM(
                                        'depose',
                                        'en_verification',
                                        'valide',
                                        'rejete',
                                        'expire',
                                        'archive'
                                    ) DEFAULT 'depose',
                                   motif_rejet TEXT NULL,                               -- Motif de rejet
                                   score_qualite INT NULL,                              -- Score de qualité (0-100)
                                   metadata_document JSON NULL,                         -- Métadonnées (JSON)
                                   checksum VARCHAR(64) NULL,                           -- Checksum du fichier
                                   version INT DEFAULT 1 ,                          -- Version du document
                                   document_precedent_id INT,                      -- Document précédent
                                   observations TEXT NULL,                              -- Observations


                                   created_by VARCHAR(150) NULL,                                 -- Déposé par
                                   updated_by VARCHAR(150) NULL,                                -- Modifié par
                                   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                   updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                                 --  FOREIGN KEY (type_document_id) REFERENCES Type_Document(id_type_document),
                                 --  FOREIGN KEY (etudiant_id) REFERENCES etudiant(id_etudiant),
                                 --  FOREIGN KEY (etablissement_id) REFERENCES Etablissement(id_etablissement),
                                 --  FOREIGN KEY (valide_par) REFERENCES users(id_user),
                                 --  FOREIGN KEY (document_precedent_id) REFERENCES document_etudiant(id_document),
                                 --  FOREIGN KEY (created_by) REFERENCES users(id_user),
                                 --  FOREIGN KEY (updated_by) REFERENCES users(id_user),

                                --   INDEX idx_reference (reference_document),
                                --   INDEX idx_etudiant (etudiant_id),
                                --   INDEX idx_type (type_document_id),
                                --   INDEX idx_statut (statut),
                                --   INDEX idx_date_depot (date_depot),
                                --   INDEX idx_date_expiration (date_expiration),
                                --   INDEX idx_etablissement (etablissement_id),
                                --   INDEX idx_valide_par (valide_par),
                                --   INDEX idx_created_by (created_by),

    -- Index pour les recherches fréquentes
                                --   INDEX idx_recherche (etudiant_id, type_document_id, statut),
                                --   INDEX idx_dates (date_document, date_expiration)
);