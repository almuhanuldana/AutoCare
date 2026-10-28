-- AutoCare: бастапқы дерекқор схемасы (келесі PHP/MySQL кезеңдеріне арналған)
CREATE DATABASE IF NOT EXISTS autocare_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE autocare_db;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(25) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('client','admin','mechanic') NOT NULL DEFAULT 'client',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vehicles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  make VARCHAR(80) NOT NULL,
  model VARCHAR(80) NOT NULL,
  production_year SMALLINT UNSIGNED NULL,
  plate_number VARCHAR(20) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_vehicles_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  service_name VARCHAR(150) NOT NULL,
  category ENUM('diagnostic','maintenance','repair') NOT NULL,
  description TEXT,
  base_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  estimated_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS appointments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  vehicle_id INT UNSIGNED NOT NULL,
  service_id INT UNSIGNED NOT NULL,
  appointment_date DATE NOT NULL,
  appointment_time TIME NOT NULL,
  notes TEXT NULL,
  status ENUM('pending','confirmed','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_appointments_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_appointments_vehicle FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_appointments_service FOREIGN KEY (service_id) REFERENCES services(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS service_history (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  appointment_id INT UNSIGNED NOT NULL,
  work_description TEXT NOT NULL,
  final_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  completed_at DATETIME NULL,
  mechanic_notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_history_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id)
    ON DELETE CASCADE ON UPDATE CASCADE
);

INSERT INTO services (service_name, category, description, base_price, estimated_minutes)
SELECT 'Компьютерлік диагностика','diagnostic','Электрондық жүйелерді тексеру',8000,45
WHERE NOT EXISTS (SELECT 1 FROM services WHERE service_name='Компьютерлік диагностика');
INSERT INTO services (service_name, category, description, base_price, estimated_minutes)
SELECT 'Май және сүзгі ауыстыру','maintenance','Қозғалтқыш майы мен сүзгілерді ауыстыру',6000,60
WHERE NOT EXISTS (SELECT 1 FROM services WHERE service_name='Май және сүзгі ауыстыру');
INSERT INTO services (service_name, category, description, base_price, estimated_minutes)
SELECT 'Тежегіш жүйесін жөндеу','repair','Тежегіш жүйесін тексеру және жөндеу',12000,120
WHERE NOT EXISTS (SELECT 1 FROM services WHERE service_name='Тежегіш жүйесін жөндеу');
