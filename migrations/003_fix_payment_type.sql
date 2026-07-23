-- Migración 003: sanear registros con payment_type NULL o vacío
-- Las entregas con precio = 0 pasan a 'gratis'; el resto a 'online'
UPDATE rsgrup_deliveries
SET    payment_type = CASE
           WHEN price = 0 THEN 'gratis'
           ELSE 'online'
       END
WHERE  payment_type IS NULL
    OR payment_type = '';
