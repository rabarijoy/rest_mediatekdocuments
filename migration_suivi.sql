-- =============================================================
-- Migration : ajout table suivi + colonne idSuivi
-- À exécuter dans phpMyAdmin sur la base mediatek86
-- =============================================================

-- 1. Créer la table suivi
CREATE TABLE IF NOT EXISTS suivi (
  id varchar(5) NOT NULL,
  libelle varchar(30) DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Insérer les étapes de suivi
INSERT IGNORE INTO suivi (id, libelle) VALUES
('00001', 'En cours'),
('00002', 'Relancée'),
('00003', 'Livrée'),
('00004', 'Réglée');

-- 3. Ajouter la colonne idSuivi à commandedocument
ALTER TABLE commandedocument
  ADD COLUMN IF NOT EXISTS idSuivi varchar(5) NOT NULL DEFAULT '00001';

-- 4. Ajouter la contrainte FK (ignorer si elle existe déjà)
ALTER TABLE commandedocument
  ADD CONSTRAINT commandedocument_ibfk_3 FOREIGN KEY (idSuivi) REFERENCES suivi (id);
