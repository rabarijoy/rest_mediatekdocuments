-- ============================================================
-- Migration : standardiser commandedocument en utf8mb4_0900_ai_ci
-- Coller dans phpMyAdmin > SQL > Exécuter
-- ============================================================

-- 1. Supprimer la FK idSuivi (pour pouvoir modifier la colonne)
ALTER TABLE commandedocument DROP FOREIGN KEY commandedocument_ibfk_3;

-- 2. Standardiser toutes les colonnes texte de commandedocument
ALTER TABLE commandedocument
  MODIFY COLUMN id         CHAR(5)      CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  MODIFY COLUMN idLivreDvd VARCHAR(10)  CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  MODIFY COLUMN idSuivi    VARCHAR(5)   CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '00001';

-- 3. Remettre la FK idSuivi
ALTER TABLE commandedocument
  ADD CONSTRAINT commandedocument_ibfk_3
  FOREIGN KEY (idSuivi) REFERENCES suivi(id);
