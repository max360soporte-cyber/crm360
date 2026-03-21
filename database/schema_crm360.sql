CREATE DATABASE IF NOT EXISTS `crm360` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `crm360`;

CREATE TABLE IF NOT EXISTS `google_contacts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `google_id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NULL,
  `phone_number` VARCHAR(255) NULL,
  `photo_url` TEXT NULL,
  `etag` VARCHAR(255) NULL,
  `synced_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_google_id` (`google_id`),
  KEY `idx_phone_number` (`phone_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `companies` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `google_contact_id` BIGINT UNSIGNED NULL,
  `ruc` VARCHAR(20) NOT NULL,
  `business_name` VARCHAR(255) NOT NULL,
  `trade_name` VARCHAR(255) NULL,
  `address` TEXT NULL,
  `mobile` VARCHAR(20) NULL,
  `category` VARCHAR(100) NULL,
  `creation_date` DATE NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_ruc` (`ruc`),
  KEY `idx_company_mobile` (`mobile`),
  CONSTRAINT `fk_company_google_contact`
    FOREIGN KEY (`google_contact_id`)
    REFERENCES `google_contacts` (`id`)
    ON DELETE SET NULL 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trainings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `google_contact_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `scheduled_date` DATETIME NOT NULL,
  `status` ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_training_contact`
    FOREIGN KEY (`google_contact_id`)
    REFERENCES `google_contacts` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
