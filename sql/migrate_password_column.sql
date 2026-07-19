-- Migración: renombrar password_hash -> password en rsgrup_users
-- Ejecutar UNA sola vez en la BD de producción:
--
--   mysql -u adminrsgrup -p dbapprsgrup < sql/migrate_password_column.sql
--
-- Si la columna ya se llama `password` este script NO hace nada (IF EXISTS).

SET @col_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'rsgrup_users'
      AND COLUMN_NAME  = 'password_hash'
);

-- Solo ejecuta el ALTER si existe password_hash
SET @sql = IF(
    @col_exists > 0,
    'ALTER TABLE rsgrup_users CHANGE `password_hash` `password` VARCHAR(255) NOT NULL',
    'SELECT ''Columna password_hash no existe, nada que migrar'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
