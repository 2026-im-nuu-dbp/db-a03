CREATE TABLE `dblog` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `account` VARCHAR(80) NOT NULL,
  `event` VARCHAR(50) NOT NULL DEFAULT 'login',
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `ip_address` VARCHAR(45) NULL,
  `occurred_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_dblog_user_id` (`user_id`),
  CONSTRAINT `fk_dblog_user` FOREIGN KEY (`user_id`) REFERENCES `dbusers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
