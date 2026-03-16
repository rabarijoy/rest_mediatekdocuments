-- ============================================================
-- DIAGNOSTIC COMPLET DE LA BASE mediatek86
-- Coller dans phpMyAdmin > SQL > Exécuter
-- Puis copier TOUT le résultat et le coller dans le chat
-- ============================================================

-- 1. Colonnes de toutes les tables (type, charset, collation, nullable, default)
SELECT
    TABLE_NAME,
    COLUMN_NAME,
    ORDINAL_POSITION,
    COLUMN_TYPE,
    CHARACTER_SET_NAME,
    COLLATION_NAME,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_KEY,
    EXTRA
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'mediatek86'
ORDER BY TABLE_NAME, ORDINAL_POSITION;

-- 2. Toutes les contraintes de clé étrangère
SELECT
    kcu.CONSTRAINT_NAME,
    kcu.TABLE_NAME,
    kcu.COLUMN_NAME,
    kcu.REFERENCED_TABLE_NAME,
    kcu.REFERENCED_COLUMN_NAME,
    rc.UPDATE_RULE,
    rc.DELETE_RULE
FROM information_schema.KEY_COLUMN_USAGE kcu
JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
    ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
    AND rc.CONSTRAINT_SCHEMA = kcu.TABLE_SCHEMA
WHERE kcu.TABLE_SCHEMA = 'mediatek86'
ORDER BY kcu.TABLE_NAME, kcu.CONSTRAINT_NAME;

-- 3. Caractéristiques globales des tables (moteur, charset, collation)
SELECT
    TABLE_NAME,
    ENGINE,
    TABLE_COLLATION,
    TABLE_COMMENT
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'mediatek86'
ORDER BY TABLE_NAME;
