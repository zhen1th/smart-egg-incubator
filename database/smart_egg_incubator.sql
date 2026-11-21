-- ============================================================================
-- Database Schema: Smart Egg Incubator IoT System
-- Target DBMS: MySQL 8.0+ / MariaDB 10.4+
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `smart_egg_incubator`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `smart_egg_incubator`;

-- ----------------------------------------------------------------------------
-- Table: devices
-- Stores registered incubator devices, telemetry state, stepper mode, and relay decisions
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `devices` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `device_id` VARCHAR(50) NOT NULL UNIQUE,
  `device_name` VARCHAR(100) NOT NULL,
  `api_key` VARCHAR(128) NOT NULL,
  `is_online` TINYINT(1) NOT NULL DEFAULT 0,
  `last_seen` DATETIME NULL,
  `last_temperature` DECIMAL(4,1) NULL,
  `last_humidity` DECIMAL(4,1) NULL,
  `last_heater_command` TINYINT(1) NOT NULL DEFAULT 0,
  `last_fan_command` TINYINT(1) NOT NULL DEFAULT 0,
  `actual_lamp_status` TINYINT(1) NOT NULL DEFAULT 0,
  `actual_fan_status` TINYINT(1) NOT NULL DEFAULT 0,
  `control_mode` ENUM('AUTO', 'MANUAL') NOT NULL DEFAULT 'AUTO',
  `stepper_mode` TINYINT NOT NULL DEFAULT 1,
  `status` ENUM('ACTIVE', 'INACTIVE', 'MAINTENANCE') NOT NULL DEFAULT 'ACTIVE',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_device_lookup` (`device_id`, `status`),
  INDEX `idx_last_seen` (`last_seen`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: sensor_logs
-- Historical telemetry data from DHT11 sensors
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sensor_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `device_id` VARCHAR(50) NOT NULL,
  `temperature` DECIMAL(4,1) NOT NULL,
  `humidity` DECIMAL(4,1) NOT NULL,
  `sensor_valid` TINYINT(1) NOT NULL DEFAULT 1,
  `recorded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sensor_device_time` (`device_id`, `recorded_at`),
  INDEX `idx_sensor_recorded_at` (`recorded_at`),
  CONSTRAINT `fk_sensor_device`
    FOREIGN KEY (`device_id`) REFERENCES `devices` (`device_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: control_logs
-- Audit log of backend temperature control decisions, mode changes, and relay switches
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `control_logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `device_id` VARCHAR(50) NOT NULL,
  `change_type` VARCHAR(50) NOT NULL,
  `heater_status` TINYINT(1) NOT NULL,
  `fan_status` TINYINT(1) NOT NULL,
  `trigger_temperature` DECIMAL(4,1) NULL,
  `description` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_control_device_time` (`device_id`, `created_at`),
  CONSTRAINT `fk_control_device`
    FOREIGN KEY (`device_id`) REFERENCES `devices` (`device_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: users
-- Dashboard administrative credentials
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'operator') NOT NULL DEFAULT 'admin',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Seed Initial Data
-- ----------------------------------------------------------------------------

-- Seed default incubator device
INSERT INTO `devices` (
  `device_id`,
  `device_name`,
  `api_key`,
  `is_online`,
  `control_mode`,
  `stepper_mode`,
  `status`
) VALUES (
  'INCUBATOR_01',
  'Inkubator Telur Utama',
  'INCUBATOR_SECRET_KEY_2026',
  0,
  'AUTO',
  1,
  'ACTIVE'
) ON DUPLICATE KEY UPDATE `device_name` = VALUES(`device_name`);

-- Seed administrator account:
-- username: admin | password: admin123
INSERT INTO `users` (
  `username`,
  `password_hash`,
  `full_name`,
  `role`
) VALUES (
  'admin',
  '$2y$10$DlYMsDaeCDOG7RRIpo.aPOKRtjCtx2dc2Vv7LNF3ghsr.scWZlL7C',
  'Administrator Sistem',
  'admin'
) ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- Seed initial control log
INSERT INTO `control_logs` (
  `device_id`,
  `change_type`,
  `heater_status`,
  `fan_status`,
  `trigger_temperature`,
  `description`
) VALUES (
  'INCUBATOR_01',
  'INITIAL_SETUP',
  0,
  0,
  NULL,
  'Inisialisasi sistem Smart Egg Incubator. Mode 1 (Putar Terus) diaktifkan secara default.'
);
