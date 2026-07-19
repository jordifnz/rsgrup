-- Migration 002: Eliminar updated_at de rsgrup_settings si existe
-- Ejecutar sólo una vez en el servidor

-- Elimina la columna updated_at si la tabla la tiene
ALTER TABLE `rsgrup_settings`
    DROP COLUMN IF EXISTS `updated_at`;

-- Asegura que la tabla tiene la estructura correcta (sólo key/value)
-- Si la tabla no existe aún, la crea:
CREATE TABLE IF NOT EXISTS `rsgrup_settings` (
    `key`   VARCHAR(100)   NOT NULL,
    `value` MEDIUMTEXT,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
