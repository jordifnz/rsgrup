-- Migración 005: insertar capa «Temas» entre Entregas y Exámenes
-- Ejecutar en: dbapprsgrup

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------
-- 1. Nueva tabla rsgrup_topics
--    Cada tema pertenece a una entrega y puede tener
--    un PDF y un examen vinculado.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rsgrup_topics` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `delivery_id`     INT UNSIGNED  NOT NULL,
  `exam_id`         INT UNSIGNED  DEFAULT NULL,
  `title`           VARCHAR(255)  NOT NULL,
  `description`     LONGTEXT      DEFAULT NULL,
  `pdf_file`        VARCHAR(255)  DEFAULT NULL,
  `sort_order`      SMALLINT      NOT NULL DEFAULT 0,
  `active`          TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_topics_delivery` (`delivery_id`),
  KEY `fk_topics_exam`     (`exam_id`),
  CONSTRAINT `fk_topics_delivery` FOREIGN KEY (`delivery_id`) REFERENCES `rsgrup_deliveries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_topics_exam`     FOREIGN KEY (`exam_id`)     REFERENCES `rsgrup_exams`      (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- 2. Migrar los datos existentes de deliveries → topics
--    Por cada entrega que tenga exam_id o pdf_file,
--    creamos un tema automático con esos datos.
-- -------------------------------------------------------
INSERT INTO `rsgrup_topics` (delivery_id, exam_id, title, pdf_file, sort_order, active, created_at, updated_at)
SELECT
  id,
  exam_id,
  CONCAT('Tema 1 — ', title) AS title,
  pdf_file,
  0,
  1,
  NOW(),
  NOW()
FROM `rsgrup_deliveries`
WHERE exam_id IS NOT NULL OR pdf_file IS NOT NULL;

-- -------------------------------------------------------
-- 3. Actualizar rsgrup_exam_attempts:
--    Añadir columna topic_id (nullable, retrocompatible)
-- -------------------------------------------------------
ALTER TABLE `rsgrup_exam_attempts`
  ADD COLUMN IF NOT EXISTS `topic_id` INT UNSIGNED DEFAULT NULL AFTER `exam_id`,
  ADD KEY IF NOT EXISTS `fk_attempts_topic` (`topic_id`);

-- Intentar añadir la FK solo si no existe
ALTER TABLE `rsgrup_exam_attempts`
  ADD CONSTRAINT `fk_attempts_topic`
    FOREIGN KEY (`topic_id`) REFERENCES `rsgrup_topics` (`id`) ON DELETE SET NULL;

-- -------------------------------------------------------
-- 4. Eliminar exam_id y pdf_file de rsgrup_deliveries
--    (los datos ya migrados a rsgrup_topics)
-- -------------------------------------------------------
ALTER TABLE `rsgrup_deliveries`
  DROP FOREIGN KEY IF EXISTS `fk_deliveries_exam`;

ALTER TABLE `rsgrup_deliveries`
  DROP KEY IF EXISTS `fk_deliveries_exam`,
  DROP COLUMN IF EXISTS `exam_id`,
  DROP COLUMN IF EXISTS `pdf_file`;

SET FOREIGN_KEY_CHECKS = 1;
