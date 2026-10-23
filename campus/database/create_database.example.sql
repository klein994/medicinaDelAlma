-- Opcional: muchos hostings requieren crear base y usuario desde su panel.
-- Cambia todos los valores de ejemplo antes de ejecutar.
CREATE DATABASE medicina_alma_campus
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER 'medicina_campus_user'@'localhost'
    IDENTIFIED BY 'CAMBIA_ESTA_CONTRASENA_LARGA_Y_UNICA';

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES
    ON medicina_alma_campus.* TO 'medicina_campus_user'@'localhost';

FLUSH PRIVILEGES;
