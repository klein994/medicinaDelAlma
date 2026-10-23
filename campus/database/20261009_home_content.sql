-- Contenido inicial migrado desde las tarjetas actuales de la home.
-- Ejecutar una vez luego de schema.sql; INSERT IGNORE conserva los cambios posteriores.
INSERT IGNORE INTO campus_home_items
(category, title, description, image_url, target_url, link_label, sort_order, status) VALUES
('workshops', 'Formación en Reiki Unitario', 'Aprende los principios del Reiki Unitario y prácticas para acompañar procesos de armonización y bienestar.', 'images/features/coach.jpg', 'reikiUnitario/', 'Saber más', 1, 'published'),
('workshops', 'Taller de Numerología Evolutiva 4 niveles', 'Explora los significados de los números y aprende a utilizarlos como una guía de reflexión personal.', 'images/features/numerologia.jpg', 'numerologiaEvolutiva/', 'Saber más', 2, 'published'),
('workshops', 'Formación en Péndulo Hebreo', 'Conoce los fundamentos del péndulo hebreo y su uso en prácticas de armonización energética.', 'images/features/pendulo.jpg', 'penduloHebreo/', 'Saber más', 3, 'published'),
('workshops', 'Taller de Sanación con los Ángeles', 'Una formación para explorar prácticas espirituales inspiradas en la guía y la simbología angelical.', 'images/features/sanacion.jpg', 'sanacionConLosAngeles/', 'Saber más', 4, 'published'),
('workshops', 'Taller de constelaciones familiares', 'Acércate a la mirada sistémica y explora las dinámicas familiares desde una perspectiva de reflexión y autoconocimiento.', 'images/features/grupo.jpg', 'constelacionesFamiliares/', 'Saber más', 5, 'published'),
('workshops', 'Formación en Registros Akáshicos', 'Aprende una práctica espiritual de introspección y conexión con los Registros Akáshicos.', 'images/features/registros.jpg', 'registrosAkashicos/', 'Saber más', 6, 'published'),
('therapies', 'Limpiezas cuánticas y energéticas de espacios.', 'Limpieza energética para armonizar el ambiente de tu hogar o espacio de trabajo.', NULL, '#contact', 'Consultar sesión', 7, 'published'),
('therapies', 'Limpiezas cuánticas y energéticas personal.', 'Una sesión enfocada en armonización y bienestar personal desde una mirada energética.', NULL, '#contact', 'Consultar sesión', 8, 'published'),
('therapies', 'Lectura de Registros Akáshicos', 'Un espacio de introspección para explorar preguntas personales y comprender mejor tu historia.', NULL, '#contact', 'Consultar sesión', 9, 'published'),
('therapies', 'Sanación angelical con Metatrón', 'Acompañamiento espiritual inspirado en la simbología y guía del Arcángel Metatrón.', NULL, '#contact', 'Consultar sesión', 10, 'published'),
('therapies', 'Tapping con PNL', 'Una práctica que combina tapping y herramientas de PNL para trabajar emociones y pensamientos.', NULL, '#contact', 'Consultar sesión', 11, 'published'),
('therapies', 'Reiki Usui, angelical y con sonido', 'Sesión de Reiki Usui, angelical o con sonido, según tus intereses y necesidades.', NULL, '#contact', 'Consultar sesión', 12, 'published'),
('therapies', 'Acompañamiento con los 72 nombres de Dios', 'Una práctica espiritual basada en la meditación y la reflexión a partir de los 72 nombres de Dios.', NULL, '#contact', 'Consultar sesión', 13, 'published'),
('therapies', 'Tarot evolutivo y terapéutico', 'Lectura para reflexionar sobre tu presente, reconocer patrones y considerar nuevas perspectivas.', NULL, '#contact', 'Consultar sesión', 14, 'published'),
('therapies', 'Acompañamiento sistémico familiar', 'Un espacio individual o grupal para observar vínculos y dinámicas familiares.', NULL, '#contact', 'Consultar sesión', 15, 'published'),
('therapies', 'Gemoterapia', 'Práctica complementaria que incorpora cristales como apoyo para la relajación y la introspección.', NULL, '#contact', 'Consultar sesión', 16, 'published'),
('therapies', 'Constelaciones familiares con tarot', 'Una mirada simbólica a los vínculos familiares mediante arquetipos del tarot.', NULL, '#contact', 'Consultar sesión', 17, 'published'),
('therapies', 'Numerología evolutiva', 'Informe numerológico para explorar aprendizajes, talentos y aspectos de tu camino personal.', NULL, '#contact', 'Consultar sesión', 18, 'published'),
('therapies', 'ThetaHealing', 'Sesión de meditación e introspección para explorar creencias y emociones desde la perspectiva de ThetaHealing.', NULL, '#contact', 'Consultar sesión', 19, 'published'),
('therapies', 'Reestructuración holística', 'Acompañamiento de bienestar centrado en la relajación y la atención al cuerpo.', NULL, '#contact', 'Consultar sesión', 20, 'published'),
('therapies', 'Péndulo hebreo', 'Sesión con péndulo hebreo desde un enfoque espiritual y de armonización energética.', NULL, '#contact', 'Consultar sesión', 21, 'published'),
('therapies', 'Coaching espiritual', 'Acompañamiento para explorar valores, propósito y crecimiento personal desde una perspectiva espiritual.', NULL, '#contact', 'Consultar sesión', 22, 'published'),
('therapies', 'Acompañamiento tanatológico', 'Un espacio de escucha y acompañamiento durante procesos de duelo, pérdida o cierre de ciclo.', NULL, '#contact', 'Consultar sesión', 23, 'published'),
('therapies', 'Psicomagia', 'Práctica simbólica orientada a explorar emociones y narrativas personales.', NULL, '#contact', 'Consultar sesión', 24, 'published'),
('therapies', 'Acompañamiento transgeneracional', 'Acompañamiento para reflexionar sobre patrones familiares y experiencias tempranas.', NULL, '#contact', 'Consultar sesión', 25, 'published'),
('therapies', 'Sanación del niño interior', 'Un espacio de introspección para conectar con experiencias de la infancia y explorar nuevas formas de cuidarte.', NULL, '#contact', 'Consultar sesión', 26, 'published'),
('objects', 'Palo santo', 'Consulta el precio y la disponibilidad vigentes.', 'images/shop/1.jpg', 'https://articulo.mercadolibre.com.ve/MLV-567726118-palo-santo-incienso-natural-100-puro-_JM', 'Ver publicación', 27, 'published'),
('objects', 'Velas aromáticas', 'Consulta el precio y la disponibilidad vigentes.', 'images/shop/2.jpg', 'https://articulo.mercadolibre.com.ve/MLV-567726237-velas-artesanales-_JM', 'Ver publicación', 28, 'published'),
('objects', 'Péndulo hebreo', 'Consulta el precio y la disponibilidad vigentes.', 'images/shop/3.jpg', 'https://articulo.mercadolibre.com.ve/MLV-567726926-pendulo-hebreo-_JM', 'Ver publicación', 29, 'published'),
('objects', 'Aceites aromáticos', 'Consulta el precio y la disponibilidad vigentes.', 'images/shop/4.jpg', 'https://articulo.mercadolibre.com.ve/MLV-567728639-aceites-aromaticos-_JM', 'Ver publicación', 30, 'published'),
('objects', 'Ropa con logo Medicina del Alma', 'Consulta variantes, precio y disponibilidad vigentes.', 'images/shop/camiseta2.jpg', 'https://articulo.mercadolibre.com.ve/MLV-567727638-franelas-con-logos-subliminados-_JM', 'Ver publicación', 31, 'published');

INSERT INTO campus_settings (setting_key, setting_value)
VALUES ('home_content_ready', '1')
ON DUPLICATE KEY UPDATE setting_value = '1';
