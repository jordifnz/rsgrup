-- Migración 004: renombrar paypal_secret -> paypal_client_secret en rsgrup_settings
-- Ejecutar una sola vez en producción:
--   mysql -h 82.223.107.197 -P 3306 -u adminrsgrup -p dbapprsgrup < sql/migrations/004_paypal_client_secret.sql

-- 1. Si existe la fila antigua 'paypal_secret', renombrarla a 'paypal_client_secret'
UPDATE `rsgrup_settings`
   SET `key` = 'paypal_client_secret'
 WHERE `key` = 'paypal_secret'
   AND NOT EXISTS (
       SELECT 1 FROM (SELECT `key` FROM `rsgrup_settings`) AS t WHERE t.`key` = 'paypal_client_secret'
   );

-- 2. Si existe la fila antigua 'smtp_password', renombrarla a 'smtp_pass'
UPDATE `rsgrup_settings`
   SET `key` = 'smtp_pass'
 WHERE `key` = 'smtp_password'
   AND NOT EXISTS (
       SELECT 1 FROM (SELECT `key` FROM `rsgrup_settings`) AS t WHERE t.`key` = 'smtp_pass'
   );

-- 3. Asegurar que existen las filas de PayPal con claves correctas (INSERT IGNORE = no sobreescribe si ya tiene valor)
INSERT IGNORE INTO `rsgrup_settings` (`key`, `value`) VALUES
  ('paypal_client_id',     ''),
  ('paypal_client_secret', ''),
  ('paypal_mode',          'sandbox');

-- 4. Ver resultado
SELECT `key`, IF(`value`='','(vacío)',CONCAT(LEFT(`value`,6),'...')) AS valor
FROM `rsgrup_settings`
WHERE `key` IN ('paypal_client_id','paypal_client_secret','paypal_mode');
