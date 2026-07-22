-- ============================================================
-- Migration 005: Introducir entidad Temas y reestructurar
-- Entregas para que sean el nivel de inscripción.
--
-- ANTES:  Cursos → Entregas (con exam_id, pdf_file…)
-- DESPUÉS: Cursos → Entregas → Temas (con exam_id, pdf_file…)
--
-- Estrategia no destructiva:
--   1. Renombrar rsgrup_deliveries a rsgrup_topics
--   2. Crear nueva rsgrup_deliveries (nivel inscripción)
--   3. Crear tabla pivot rsgrup_delivery_topics
--   4. Migrar datos: cada entrega antigua → 1 entrega nueva + 1 tema
--   5. Migrar rsgrup_enrollments.delivery_id → nueva entrega
--   6. Migrar rsgrup_exam_attempts.enrollment_id (sigue igual, FK cascada)
--   7. Ajustar FK de rsgrup_exams (delivery_id ya no existe → topic_id)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── 1. Renombrar la tabla actual de entregas a temas ─────────
RENAME TABLE rsgrup_deliveries TO rsgrup_topics;

-- Quitar FK hacia cursos (se mantiene columna course_id en topics
-- por compatibilidad; la nueva entrega también tendrá course_id)
-- Las FK internas (exam_id, etc.) se quedan.

-- ── 2. Nueva tabla rsgrup_deliveries (nivel inscripción) ─────
CREATE TABLE IF NOT EXISTS `rsgrup_deliveries` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `course_id`       INT UNSIGNED    NOT NULL,
  `title`           VARCHAR(255)    NOT NULL,
  `slug`            VARCHAR(255)    NOT NULL,
  `description`     LONGTEXT        DEFAULT NULL,
  `type`            ENUM('matricula','entrega','practica') NOT NULL DEFAULT 'entrega',
  `payment_type`    ENUM('online','presencial') NOT NULL DEFAULT 'online',
  `price`           DECIMAL(8,2)    NOT NULL DEFAULT 0.00,
  `sort_order`      SMALLINT        NOT NULL DEFAULT 0,
  `notify_email`    TINYINT(1)      NOT NULL DEFAULT 0,
  `notify_whatsapp` TINYINT(1)      NOT NULL DEFAULT 0,
  `active`          TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_deliveries_slug` (`slug`),
  KEY `fk_deliveries_course` (`course_id`),
  CONSTRAINT `fk_deliveries_course` FOREIGN KEY (`course_id`)
    REFERENCES `rsgrup_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Tabla pivot Entrega ↔ Temas ───────────────────────────
CREATE TABLE IF NOT EXISTS `rsgrup_delivery_topics` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `delivery_id` INT UNSIGNED  NOT NULL,
  `topic_id`    INT UNSIGNED  NOT NULL,
  `sort_order`  SMALLINT      NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_delivery_topic` (`delivery_id`,`topic_id`),
  KEY `fk_dt_delivery` (`delivery_id`),
  KEY `fk_dt_topic`    (`topic_id`),
  CONSTRAINT `fk_dt_delivery` FOREIGN KEY (`delivery_id`)
    REFERENCES `rsgrup_deliveries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dt_topic`    FOREIGN KEY (`topic_id`)
    REFERENCES `rsgrup_topics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. Migrar datos: por cada topic antiguo crear 1 entrega nueva
--    y enlazarlos en la tabla pivot.
--    El slug de la entrega nueva = slug del topic (ya era único).
-- ────────────────────────────────────────────────────────────────
INSERT INTO rsgrup_deliveries
  (id, course_id, title, slug, description, type, payment_type, price,
   sort_order, notify_email, notify_whatsapp, active, created_at, updated_at)
SELECT
  id, course_id, title, slug, description, type, payment_type, price,
  sort_order, notify_email, notify_whatsapp, active, created_at, updated_at
FROM rsgrup_topics;

-- Tabla pivot: cada entrega nueva ↔ su topic correspondiente (1:1)
INSERT INTO rsgrup_delivery_topics (delivery_id, topic_id, sort_order)
SELECT id, id, 0 FROM rsgrup_topics;

-- ── 5. La tabla rsgrup_enrollments ya tiene delivery_id que apunta
--    a los IDs que acabamos de replicar en rsgrup_deliveries, así
--    que no hace falta UPDATE de datos; solo añadir la FK nueva.

-- Quitar FK antigua (apuntaba a rsgrup_topics que antes era deliveries)
ALTER TABLE rsgrup_enrollments
  DROP FOREIGN KEY fk_enrollments_delivery;

-- Añadir FK nueva hacia rsgrup_deliveries
ALTER TABLE rsgrup_enrollments
  ADD CONSTRAINT `fk_enrollments_delivery`
    FOREIGN KEY (`delivery_id`) REFERENCES `rsgrup_deliveries` (`id`)
    ON DELETE CASCADE;

-- ── 6. rsgrup_exam_attempts: la FK enrollment_id no cambia (sigue
--    apuntando a rsgrup_enrollments.id), nada que hacer.

-- ── 7. La columna delivery_id de rsgrup_topics (renombrada) ya no
--    se usa (el vínculo ahora es exam_id directo en el topic).
--    No la borramos para no perder datos; simplemente queda obsoleta.

SET FOREIGN_KEY_CHECKS = 1;
