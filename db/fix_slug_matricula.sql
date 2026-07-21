-- Corregir slug de la matrícula: matrcula -> matricula
-- Ejecutar UNA sola vez en la base de datos
UPDATE rsgrup_deliveries
   SET slug = 'matricula'
 WHERE slug = 'matrcula'
   AND type = 'matricula'
 LIMIT 1;
