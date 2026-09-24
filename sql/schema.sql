-- ============================================================
-- VST — Database schema  (MySQL / MariaDB, HostGator compatible)
-- Import this in cPanel > phpMyAdmin (select your database first).
-- ============================================================

SET NAMES utf8mb4;

-- ---- Admin users (the single administrator lives here) -----
CREATE TABLE IF NOT EXISTS admin_users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120)  NOT NULL,
  email         VARCHAR(190)  NOT NULL UNIQUE,
  password_hash VARCHAR(255)  NOT NULL,
  created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- Failed login attempts (IP-based brute-force lockout) ----
CREATE TABLE IF NOT EXISTS login_attempts (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  ip           VARCHAR(45) NOT NULL,
  attempted_at DATETIME    NOT NULL,
  INDEX idx_ip_time (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- Employees (team members) ------------------------------
CREATE TABLE IF NOT EXISTS employees (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(160) NOT NULL,
  position_en    VARCHAR(220) DEFAULT '',
  position_pt    VARCHAR(220) DEFAULT '',
  position_es    VARCHAR(220) DEFAULT '',
  department     VARCHAR(60)  DEFAULT 'Other',
  phone          VARCHAR(60)  DEFAULT '',
  email          VARCHAR(190) DEFAULT '',
  description_en TEXT NULL,
  description_pt TEXT NULL,
  description_es TEXT NULL,
  image          VARCHAR(255) DEFAULT '',
  sort_order     INT DEFAULT 0,
  is_active      TINYINT(1) DEFAULT 1,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- Settings (contacts + social links; simple key/value) ---
CREATE TABLE IF NOT EXISTS settings (
  skey   VARCHAR(64) PRIMARY KEY,
  svalue TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- Tourism destinations ----------------------------------
CREATE TABLE IF NOT EXISTS destinations (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  name           VARCHAR(160) NOT NULL,
  location_en    VARCHAR(200) DEFAULT '',
  location_pt    VARCHAR(200) DEFAULT '',
  location_es    VARCHAR(200) DEFAULT '',
  description_en TEXT NULL,
  description_pt TEXT NULL,
  description_es TEXT NULL,
  image          VARCHAR(255) DEFAULT '',
  sort_order     INT DEFAULT 0,
  is_active      TINYINT(1) DEFAULT 1,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- Tourism gallery photos --------------------------------
CREATE TABLE IF NOT EXISTS gallery (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  image       VARCHAR(255) NOT NULL,
  caption     VARCHAR(200) DEFAULT '',
  sort_order  INT DEFAULT 0,
  is_active   TINYINT(1) DEFAULT 1,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
