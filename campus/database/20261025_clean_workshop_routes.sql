-- Reemplaza rutas antiguas por URLs limpias basadas en slugs estables.
-- Ejecutar una vez después de 20261023_workshop_pages.sql.

UPDATE campus_public_pages
SET slug = CASE slug
    WHEN 'reikiUnitario' THEN 'reiki-unitario'
    WHEN 'numerologiaEvolutiva' THEN 'numerologia-evolutiva'
    WHEN 'penduloHebreo' THEN 'pendulo-hebreo'
    WHEN 'sanacionConLosAngeles' THEN 'sanacion-con-los-angeles'
    WHEN 'constelacionesFamiliares' THEN 'constelaciones-familiares'
    WHEN 'registrosAkashicos' THEN 'registros-akashicos'
    ELSE slug
END
WHERE slug IN ('reikiUnitario', 'numerologiaEvolutiva', 'penduloHebreo',
    'sanacionConLosAngeles', 'constelacionesFamiliares', 'registrosAkashicos');

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

SELECT slug, title, status FROM campus_public_pages ORDER BY sort_order, id;
SELECT title, target_url FROM campus_home_items WHERE category = 'workshops' ORDER BY sort_order, id;
