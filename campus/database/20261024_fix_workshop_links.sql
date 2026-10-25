-- Corrige los destinos de las seis tarjetas de talleres ya existentes.
-- Ejecutar una vez sobre la base que ya tiene campus_home_items.
-- No cambia títulos, descripciones, imágenes ni el orden de las tarjetas.

UPDATE campus_home_items
SET target_url = CASE
    WHEN title LIKE '%Reiki Unitario%' THEN 'talleres/reiki-unitario/'
    WHEN title LIKE '%Numerolog%' THEN 'talleres/numerologia-evolutiva/'
    WHEN title LIKE '%Péndulo Hebreo%' OR title LIKE '%Pendulo Hebreo%' THEN 'talleres/pendulo-hebreo/'
    WHEN title LIKE '%Sanación%' OR title LIKE '%Sanacion%' OR title LIKE '%Ángeles%' OR title LIKE '%Angeles%' THEN 'talleres/sanacion-con-los-angeles/'
    WHEN title LIKE '%Constelacion%' THEN 'talleres/constelaciones-familiares/'
    WHEN title LIKE '%Registros Akáshicos%' OR title LIKE '%Registros Akashicos%' THEN 'talleres/registros-akashicos/'
    ELSE target_url
END
WHERE category = 'workshops'
  AND (title LIKE '%Reiki Unitario%' OR title LIKE '%Numerolog%'
    OR title LIKE '%Péndulo Hebreo%' OR title LIKE '%Pendulo Hebreo%'
    OR title LIKE '%Sanación%' OR title LIKE '%Sanacion%' OR title LIKE '%Ángeles%' OR title LIKE '%Angeles%'
    OR title LIKE '%Constelacion%'
    OR title LIKE '%Registros Akáshicos%' OR title LIKE '%Registros Akashicos%');

SELECT title, target_url
FROM campus_home_items
WHERE category = 'workshops'
ORDER BY sort_order, id;
