-- RSGrup Course Management System
-- Schema MySQL 5.7 — todas las tablas con prefijo rsgrup_
-- Ejecutar en: dbapprsgrup

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- rsgrup_users
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `rsgrup_users` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(100)    NOT NULL,
  `surnames`      VARCHAR(150)    NOT NULL DEFAULT '',
  `email`         VARCHAR(191)    NOT NULL,
  `password`      VARCHAR(255)    NOT NULL,
  `phone`         VARCHAR(30)     DEFAULT NULL,
  `address`       VARCHAR(255)    DEFAULT NULL,
  `postal_code`   VARCHAR(10)     DEFAULT NULL,
  `city`          VARCHAR(100)    DEFAULT NULL,
  `province`      VARCHAR(100)    DEFAULT NULL,
  `instagram`     VARCHAR(100)    DEFAULT NULL,
  `tiktok`        VARCHAR(100)    DEFAULT NULL,
  `avatar`        VARCHAR(255)    DEFAULT NULL,
  `role`          ENUM('alumno','admin') NOT NULL DEFAULT 'alumno',
  `email_verified_at` DATETIME    DEFAULT NULL,
  `remember_token`    VARCHAR(100) DEFAULT NULL,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- rsgrup_courses
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `rsgrup_courses` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(255)  NOT NULL,
  `slug`        VARCHAR(255)  NOT NULL,
  `description` LONGTEXT      DEFAULT NULL,
  `active`      TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_courses_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- rsgrup_exams
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `rsgrup_exams` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(255)  NOT NULL,
  `description` LONGTEXT      DEFAULT NULL,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- rsgrup_deliveries
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `rsgrup_deliveries` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `course_id`       INT UNSIGNED    NOT NULL,
  `exam_id`         INT UNSIGNED    DEFAULT NULL,
  `title`           VARCHAR(255)    NOT NULL,
  `slug`            VARCHAR(255)    NOT NULL,
  `description`     LONGTEXT        DEFAULT NULL,
  `type`            ENUM('matricula','entrega','practica') NOT NULL DEFAULT 'entrega',
  `payment_type`    ENUM('online','presencial') NOT NULL DEFAULT 'online',
  `price`           DECIMAL(8,2)    NOT NULL DEFAULT 0.00,
  `pdf_file`        VARCHAR(255)    DEFAULT NULL,
  `sort_order`      SMALLINT        NOT NULL DEFAULT 0,
  `notify_email`    TINYINT(1)      NOT NULL DEFAULT 0,
  `notify_whatsapp` TINYINT(1)      NOT NULL DEFAULT 0,
  `active`          TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_deliveries_slug` (`slug`),
  KEY `fk_deliveries_course` (`course_id`),
  KEY `fk_deliveries_exam`   (`exam_id`),
  CONSTRAINT `fk_deliveries_course` FOREIGN KEY (`course_id`) REFERENCES `rsgrup_courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_deliveries_exam`   FOREIGN KEY (`exam_id`)   REFERENCES `rsgrup_exams`   (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- rsgrup_exam_questions
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `rsgrup_exam_questions` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `exam_id`       INT UNSIGNED  NOT NULL,
  `title`         VARCHAR(500)  NOT NULL,
  `description`   LONGTEXT      DEFAULT NULL,
  `answer_type`   ENUM('radio','checkbox') NOT NULL DEFAULT 'radio',
  `sort_order`    SMALLINT      NOT NULL DEFAULT 0,
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_questions_exam` (`exam_id`),
  CONSTRAINT `fk_questions_exam` FOREIGN KEY (`exam_id`) REFERENCES `rsgrup_exams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- rsgrup_exam_answers
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `rsgrup_exam_answers` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `question_id` INT UNSIGNED  NOT NULL,
  `text`        VARCHAR(1000) NOT NULL,
  `is_correct`  TINYINT(1)    NOT NULL DEFAULT 0,
  `sort_order`  SMALLINT      NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_answers_question` (`question_id`),
  CONSTRAINT `fk_answers_question` FOREIGN KEY (`question_id`) REFERENCES `rsgrup_exam_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- rsgrup_enrollments
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `rsgrup_enrollments` (
  `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`           INT UNSIGNED    NOT NULL,
  `delivery_id`       INT UNSIGNED    NOT NULL,
  `status`            ENUM('pending','active','cancelled') NOT NULL DEFAULT 'pending',
  `payment_type`      ENUM('online','presencial') NOT NULL DEFAULT 'online',
  `paypal_order_id`   VARCHAR(100)    DEFAULT NULL,
  `paypal_capture_id` VARCHAR(100)    DEFAULT NULL,
  `amount_paid`       DECIMAL(8,2)    DEFAULT NULL,
  `paid_at`           DATETIME        DEFAULT NULL,
  `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_enrollment` (`user_id`,`delivery_id`),
  KEY `fk_enrollments_user`     (`user_id`),
  KEY `fk_enrollments_delivery` (`delivery_id`),
  CONSTRAINT `fk_enrollments_user`     FOREIGN KEY (`user_id`)     REFERENCES `rsgrup_users`      (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_enrollments_delivery` FOREIGN KEY (`delivery_id`) REFERENCES `rsgrup_deliveries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- rsgrup_exam_attempts
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `rsgrup_exam_attempts` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `user_id`       INT UNSIGNED    NOT NULL,
  `exam_id`       INT UNSIGNED    NOT NULL,
  `enrollment_id` INT UNSIGNED    NOT NULL,
  `score`         DECIMAL(5,2)    DEFAULT NULL COMMENT 'Nota sobre 10',
  `total_q`       SMALLINT        NOT NULL DEFAULT 0,
  `correct_q`     SMALLINT        NOT NULL DEFAULT 0,
  `submitted_at`  DATETIME        DEFAULT NULL,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_attempts_user`       (`user_id`),
  KEY `fk_attempts_exam`       (`exam_id`),
  KEY `fk_attempts_enrollment` (`enrollment_id`),
  CONSTRAINT `fk_attempts_user`       FOREIGN KEY (`user_id`)       REFERENCES `rsgrup_users`        (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attempts_exam`       FOREIGN KEY (`exam_id`)       REFERENCES `rsgrup_exams`        (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attempts_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `rsgrup_enrollments`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- rsgrup_exam_attempt_answers
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `rsgrup_exam_attempt_answers` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `attempt_id`    INT UNSIGNED  NOT NULL,
  `question_id`   INT UNSIGNED  NOT NULL,
  `answer_id`     INT UNSIGNED  NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_atanswers_attempt`  (`attempt_id`),
  KEY `fk_atanswers_question` (`question_id`),
  KEY `fk_atanswers_answer`   (`answer_id`),
  CONSTRAINT `fk_atanswers_attempt`  FOREIGN KEY (`attempt_id`)  REFERENCES `rsgrup_exam_attempts`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_atanswers_question` FOREIGN KEY (`question_id`) REFERENCES `rsgrup_exam_questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_atanswers_answer`   FOREIGN KEY (`answer_id`)   REFERENCES `rsgrup_exam_answers`   (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- rsgrup_settings
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `rsgrup_settings` (
  `id`      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `key`     VARCHAR(100)  NOT NULL,
  `value`   LONGTEXT      DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valores por defecto de settings
INSERT IGNORE INTO `rsgrup_settings` (`key`, `value`) VALUES
  ('paypal_mode',               'sandbox'),
  ('paypal_client_id',          ''),
  ('paypal_client_secret',      ''),
  ('smtp_host',                 'smtp.gmail.com'),
  ('smtp_port',                 '587'),
  ('smtp_user',                 ''),
  ('smtp_pass',                 ''),
  ('smtp_from_name',            'RSGrup'),
  ('evolution_api_url',         ''),
  ('evolution_api_token',       ''),
  ('whatsapp_contact_number',   ''),
  ('email_template',            '<p>Hola {{nombre}},</p><p>Te confirmamos la inscripción a <strong>{{entrega}}</strong> del curso <strong>{{curso}}</strong>.</p><p>Fecha: {{fecha}}</p><p>Importe abonado: {{precio}} €</p><p>Gracias por confiar en RSGrup.</p>'),
  ('whatsapp_template',         'Hola {{nombre}}, confirmamos tu inscripción a {{entrega}} ({{fecha}}). ¡Bienvenido!'),
  ('certificate_bg',            ''),
  ('certificate_name_x',        '400'),
  ('certificate_name_y',        '300'),
  ('certificate_name_font_size','48');

-- -----------------------------------------------------
-- rsgrup_api_tokens
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `rsgrup_api_tokens` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `label`         VARCHAR(100)  NOT NULL,
  `token`         VARCHAR(64)   NOT NULL,
  `last_used_at`  DATETIME      DEFAULT NULL,
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_api_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- rsgrup_activity_log
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `rsgrup_activity_log` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED  DEFAULT NULL,
  `action`      VARCHAR(100)  NOT NULL COMMENT 'login_ok, login_fail, register, enroll, exam_submit, etc.',
  `description` VARCHAR(500)  DEFAULT NULL,
  `ip_address`  VARCHAR(45)   DEFAULT NULL,
  `user_agent`  VARCHAR(300)  DEFAULT NULL,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_log_user`  (`user_id`),
  KEY `idx_log_action` (`action`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `rsgrup_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================
-- INSTRUCCIONES DE INSTALACIÓN
-- ==========================================================
-- 1. Conectar: mysql -h 82.223.107.197 -P 3306 -u adminrsgrup -p dbapprsgrup
-- 2. Ejecutar: SOURCE /ruta/al/proyecto/sql/schema.sql;
-- 3. Crear primer admin manualmente:
--    INSERT INTO rsgrup_users (name,surnames,email,password,role)
--    VALUES ('Admin','RSGrup','admin@rsgrup.com', SHA2('TuPassword123',256) ,'admin');
--    NOTA: La app usa password_hash/password_verify de PHP, usar desde registro web.
