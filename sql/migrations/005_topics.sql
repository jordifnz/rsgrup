-- ============================================================
-- Migración 005: introducir "Temas" entre Entregas y Exámenes
--
-- Estructura nueva:
--   Cursos → Entregas (rsgrup_deliveries, sin exam_id)
--            └── Temas (rsgrup_topics, con exam_id y pdf_file)
--
-- Ejecutar UNA SOLA VEZ sobre la base de datos de producción.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Crear tabla rsgrup_topics
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rsgrup_topics` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `delivery_id`     INT UNSIGNED    NOT NULL,
  `exam_id`         INT UNSIGNED    DEFAULT NULL,
  `title`           VARCHAR(255)    NOT NULL,
  `description`     LONGTEXT        DEFAULT NULL,
  `pdf_file`        VARCHAR(255)    DEFAULT NULL,
  `sort_order`      SMALLINT        NOT NULL DEFAULT 0,
  `active`          TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_topics_delivery` (`delivery_id`),
  KEY `fk_topics_exam`     (`exam_id`),
  CONSTRAINT `fk_topics_delivery` FOREIGN KEY (`delivery_id`) REFERENCES `rsgrup_deliveries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_topics_exam`     FOREIGN KEY (`exam_id`)     REFERENCES `rsgrup_exams`       (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. Migrar datos existentes: cada delivery con exam_id y/o
--    pdf_file se convierte en un Tema dentro de esa misma
--    Entrega (misma sort_order, mismo título como tema).
-- ------------------------------------------------------------
INSERT INTO `rsgrup_topics`
    (`delivery_id`, `exam_id`, `title`, `description`, `pdf_file`, `sort_order`, `active`, `created_at`, `updated_at`)
SELECT
    `id`,
    `exam_id`,
    `title`,
    `description`,
    `pdf_file`,
    `sort_order`,
    `active`,
    `created_at`,
    `updated_at`
FROM `rsgrup_deliveries`
WHERE `exam_id` IS NOT NULL
   OR `pdf_file` IS NOT NULL;

-- ------------------------------------------------------------
-- 3. Eliminar columnas que ahora pertenecen a topics.
--    (description se mantiene en deliveries para descripción
--     general de la entrega; exam_id y pdf_file se mueven)
-- ------------------------------------------------------------
ALTER TABLE `rsgrup_deliveries`
    DROP FOREIGN KEY `fk_deliveries_exam`;

ALTER TABLE `rsgrup_deliveries`
    DROP KEY `fk_deliveries_exam`;

ALTER TABLE `rsgrup_deliveries`
    DROP COLUMN `exam_id`,
    DROP COLUMN `pdf_file`;

SET FOREIGN_KEY_CHECKS = 1;
