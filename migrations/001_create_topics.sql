-- ============================================================
-- Migración 001: Crear tabla rsgrup_topics
-- Ejecutar una sola vez contra la base de datos de producción.
-- ============================================================

CREATE TABLE IF NOT EXISTS `rsgrup_topics` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `delivery_id` INT UNSIGNED NOT NULL,
  `exam_id`     INT UNSIGNED          DEFAULT NULL,
  `title`       VARCHAR(255)  NOT NULL,
  `description` TEXT                  DEFAULT NULL,
  `pdf_file`    VARCHAR(255)          DEFAULT NULL,
  `sort_order`  SMALLINT     NOT NULL DEFAULT 0,
  `active`      TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_topics_delivery` (`delivery_id`),
  KEY `idx_topics_exam`     (`exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Migración 002: Crear tabla rsgrup_settings (si no existe)
-- ============================================================

CREATE TABLE IF NOT EXISTS `rsgrup_settings` (
  `key`   VARCHAR(100) NOT NULL,
  `value` TEXT                  DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Migración 003: Crear tabla rsgrup_exam_attempts (si no existe)
-- ============================================================

CREATE TABLE IF NOT EXISTS `rsgrup_exam_attempts` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`       INT UNSIGNED NOT NULL,
  `exam_id`       INT UNSIGNED NOT NULL,
  `enrollment_id` INT UNSIGNED          DEFAULT NULL,
  `answers`       JSON                  DEFAULT NULL,
  `score`         DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attempts_user_exam` (`user_id`, `exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
