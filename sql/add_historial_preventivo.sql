-- SQL para Historial Preventivo / Seguimiento Diario
CREATE TABLE IF NOT EXISTS seguimientos_diarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mascota_id INT NOT NULL,
  user_id INT NOT NULL,
  fecha DATE NOT NULL,
  datos JSON NOT NULL,
  observaciones TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (mascota_id),
  INDEX (user_id),
  CONSTRAINT fk_seguimiento_mascota FOREIGN KEY (mascota_id) REFERENCES mascotas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS alertas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  seguimiento_id INT NOT NULL,
  tipo VARCHAR(100) NOT NULL,
  sintomas JSON NOT NULL,
  explicacion TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (seguimiento_id),
  CONSTRAINT fk_alerta_seguimiento FOREIGN KEY (seguimiento_id) REFERENCES seguimientos_diarios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS preconsultas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mascota_id INT NOT NULL,
  cita_id INT DEFAULT NULL,
  generado_por VARCHAR(50) DEFAULT 'sistema',
  dias_considerados INT DEFAULT 7,
  resumen TEXT NOT NULL,
  datos JSON DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (mascota_id),
  INDEX (cita_id),
  CONSTRAINT fk_preconsulta_mascota FOREIGN KEY (mascota_id) REFERENCES mascotas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS consultas_veterinarias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mascota_id INT NOT NULL,
  veterinario_id INT NOT NULL,
  user_id INT NOT NULL,
  fecha DATETIME NOT NULL,
  motivo TEXT,
  resumen TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (mascota_id),
  CONSTRAINT fk_consulta_mascota FOREIGN KEY (mascota_id) REFERENCES mascotas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS diagnosticos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  consulta_id INT NOT NULL,
  diagnostico TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_diag_consulta FOREIGN KEY (consulta_id) REFERENCES consultas_veterinarias(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tratamientos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  consulta_id INT NOT NULL,
  tratamiento TEXT NOT NULL,
  medicamento VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_trat_consulta FOREIGN KEY (consulta_id) REFERENCES consultas_veterinarias(id) ON DELETE CASCADE
);

-- Fin esquema historial preventivo
