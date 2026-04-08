-- Migration: Aliados verificacion + last_seen usuarios
-- Ejecutar una vez en BD

ALTER TABLE aliados 
  ADD COLUMN direccion VARCHAR(300) NULL AFTER nombre_local,
  ADD COLUMN google_maps_url VARCHAR(600) NULL AFTER direccion,
  ADD COLUMN fotos_verificacion TEXT NULL AFTER google_maps_url,
  ADD COLUMN pendiente_verificacion TINYINT(1) NOT NULL DEFAULT 0 AFTER activo;

ALTER TABLE usuarios
  ADD COLUMN ultimo_login DATETIME NULL;
