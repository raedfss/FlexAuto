-- --------------------------------------------------------
-- هيكل قاعدة البيانات لنظام FlexAuto لإدارة طلبات الخدمة
-- --------------------------------------------------------

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- إنشاء جدول المستخدمين
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `user_type` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- إنشاء جدول طلبات مسح Airbag
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `airbag_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `car_make` varchar(100) NOT NULL,
  `car_model` varchar(100) DEFAULT NULL,
  `car_year` int(4) DEFAULT NULL,
  `ecu_model` varchar(100) DEFAULT NULL,
  `vin` varchar(50) NOT NULL,
  `car_status` varchar(255) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=new, 1=in progress, 2=completed, 3=cancelled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  CONSTRAINT `airbag_requests_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- إنشاء جدول طلبات تعديل برمجة ECU
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `ecu_tuning_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `car_make` varchar(100) NOT NULL,
  `car_model` varchar(100) DEFAULT NULL,
  `car_year` int(4) DEFAULT NULL,
  `tool_type` varchar(100) NOT NULL,
  `vin` varchar(50) NOT NULL,
  `modifications` text DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=new, 1=in progress, 2=completed, 3=cancelled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  CONSTRAINT `ecu_tuning_requests_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- إنشاء جدول طلبات برمجة المفاتيح
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `key_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `car_make` varchar(100) NOT NULL,
  `car_model` varchar(100) DEFAULT NULL,
  `car_year` int(4) DEFAULT NULL,
  `ecu_type` varchar(100) NOT NULL,
  `vin` varchar(50) NOT NULL,
  `keys_count` int(11) NOT NULL DEFAULT 1,
  `comments` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=new, 1=in progress, 2=completed, 3=cancelled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  CONSTRAINT `key_requests_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- إنشاء جدول طلبات تشخيص الأعطال
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `diagnostic_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `car_make` varchar(100) NOT NULL,
  `car_model` varchar(100) DEFAULT NULL,
  `car_year` int(4) DEFAULT NULL,
  `vin` varchar(50) NOT NULL,
  `issue_desc` text NOT NULL,
  `error_codes` varchar(255) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=new, 1=in progress, 2=completed, 3=cancelled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  CONSTRAINT `diagnostic_requests_ibfk_1` FOREIGN KEY (`username`) REFERENCES `users` (`username`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- إضافة مستخدم افتراضي للمسؤول
-- --------------------------------------------------------

INSERT INTO `users` (`username`, `email`, `password`, `phone`, `name`, `user_type`) VALUES
('admin', 'admin@flexauto.com', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa', '+962796519007', 'مدير النظام', 'admin');

COMMIT;