-- ============================================================
-- Migración 002: Crear tabla rsgrup_topic_attachments
-- Adjuntos adicionales por tema (múltiples archivos + descripción)
-- Ejecutar una sola vez contra la base de datos de producción.
-- ============================================================

CREATE TABLE IF NOT EXISTS `rsgrup_topic_attachments` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `topic_id`      INT UNSIGNED  NOT NULL,
  `filename`      VARCHAR(255)  NOT NULL,
  `original_name` VARCHAR(255)  NOT NULL,
  `description`   VARCHAR(1000)          DEFAULT NULL,
  `sort_order`    SMALLINT      NOT NULL DEFAULT 0,
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_topic_attachments_topic` (`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- También añadir la ruta de descarga en el router si no existe (ver EnrollmentController)
