CREATE TABLE `dbmemo` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `category` ENUM('風景', '人物', '動物', '植物', '其他') NOT NULL DEFAULT '其他',
  `content` TEXT NOT NULL,
  `image_path` VARCHAR(255) NULL,
  `thumbnail_path` VARCHAR(255) NULL,
  `inspiration` TEXT NULL,
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_dbmemo_user_id` (`user_id`),
  CONSTRAINT `fk_dbmemo_user` FOREIGN KEY (`user_id`) REFERENCES `dbusers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
