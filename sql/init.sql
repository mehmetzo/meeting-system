-- ============================================================
-- Dijital Toplanti Katilim Sistemi -- Veritabani Kurulum
-- ============================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET CHARACTER SET utf8mb4;
SET character_set_connection = utf8mb4;
SET time_zone = '+03:00';

ALTER DATABASE meeting_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLO: settings
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `id`            int NOT NULL AUTO_INCREMENT,
  `setting_key`   varchar(100)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text          CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `updated_at`    timestamp     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLO: admin_users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`         int NOT NULL AUTO_INCREMENT,
  `username`   varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password`   varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name`  varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `email`      varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `role`       enum('superadmin','admin','viewer') DEFAULT 'admin',
  `is_active`  tinyint(1)   DEFAULT 1,
  `last_login` timestamp    NULL,
  `created_at` timestamp    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLO: meetings
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `meetings` (
  `id`             int NOT NULL AUTO_INCREMENT,
  `meeting_code`   varchar(50)  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title`          varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description`    text         CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `meeting_date`   date         NOT NULL,
  `meeting_time`   time         NOT NULL,
  `location`       varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `organizer_id`   int,
  `organizer_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `organizer_unit` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status`         enum('active','completed','cancelled') DEFAULT 'active',
  `qr_token`       varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at`     timestamp    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     timestamp    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meeting_code` (`meeting_code`),
  UNIQUE KEY `qr_token`     (`qr_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLO: attendees
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attendees` (
  `id`             int NOT NULL AUTO_INCREMENT,
  `meeting_id`     int NOT NULL,
  `attendee_type`  enum('staff','guest') NOT NULL,
  `full_name`      varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tc_no`          varchar(11),
  `phone`          varchar(20),
  `email`          varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `institution`    varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `title`          varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `unit`           varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ldap_username`  varchar(100),
  `ip_address`     varchar(45),
  `attended_at`    timestamp    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `meeting_id` (`meeting_id`),
  FOREIGN KEY (`meeting_id`) REFERENCES `meetings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLO: access_logs
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `access_logs` (
  `id`         int NOT NULL AUTO_INCREMENT,
  `user_id`    int,
  `username`   varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `action`     varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `details`    text         CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45),
  `user_agent` text,
  `status`     enum('success','warning','error','info') DEFAULT 'info',
  `created_at` timestamp    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABLO: ldap_users_cache
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ldap_users_cache` (
  `id`         int NOT NULL AUTO_INCREMENT,
  `username`   varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name`  varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `email`      varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tc_no`      varchar(11),
  `phone`      varchar(20),
  `department` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `title`      varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_sync`  timestamp    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- VERI: Varsayilan ayarlar
-- ------------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('institution_name',      'T.C. Saglik Bakanligi'),
('hospital_name',         'Ornek Devlet Hastanesi'),
('footer_text',           'Bilgi Islem Daire Baskanligi'),
('logo_path',             ''),
('primary_color',         '#1a5276'),
('secondary_color',       '#2ecc71'),
('ldap_enabled',          '0'),
('ldap_host',             ''),
('ldap_port',             '389'),
('ldap_base_dn',          'dc=domain,dc=local'),
('ldap_domain',           'domain.local'),
('ldap_bind_user',        ''),
('ldap_bind_password',    ''),
('ldap_group',            ''),
('ldap_tc_attribute',     'employeeID'),
('ldap_phone_attribute',  'mobile'),
('app_version',           '1.0.0'),
('installed',             '1')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- ------------------------------------------------------------
-- VERI: Admin kullanicisi
-- Varsayilan sifre: admin
-- Hash: password_hash('admin', PASSWORD_BCRYPT, ['cost'=>12])
-- ------------------------------------------------------------
INSERT IGNORE INTO `admin_users`
  (`username`, `password`, `full_name`, `email`, `role`, `is_active`)
VALUES (
  'admin',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'Sistem Yoneticisi',
  'admin@hastane.gov.tr',
  'superadmin',
  1
);

COMMIT;