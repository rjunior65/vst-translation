-- ============================================================
-- VST — Time-clock system (Ponto) — database schema DRAFT
-- Same database as the website (yasmi374_vst). All tables use the
-- pt_ prefix so they never collide with the site's tables.
-- MySQL 5.7 (HostGator) compatible. Import in phpMyAdmin.
-- ============================================================

SET NAMES utf8mb4;

-- ---- Work locations (geofence) -----------------------------
CREATE TABLE IF NOT EXISTS pt_locations (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(120)  NOT NULL,
  address    VARCHAR(255)  DEFAULT '',
  lat        DECIMAL(10,7) NOT NULL,
  lng        DECIMAL(10,7) NOT NULL,
  radius_m   INT           NOT NULL DEFAULT 150,
  is_active  TINYINT(1)    DEFAULT 1,
  created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- Work schedules (set by the admin) ---------------------
CREATE TABLE IF NOT EXISTS pt_schedules (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  name               VARCHAR(120) NOT NULL,              -- e.g. "Escritório 8h–17h"
  start_time         TIME         NOT NULL,
  end_time           TIME         NOT NULL,
  break_minutes      INT          NOT NULL DEFAULT 60,
  late_tolerance_min INT          NOT NULL DEFAULT 10,   -- minutes before it counts as late
  overtime_after_min INT          NOT NULL DEFAULT 10,   -- extra minutes ignored before counting overtime
  workdays           SET('mon','tue','wed','thu','fri','sat','sun') NOT NULL DEFAULT 'mon,tue,wed,thu,fri',
  is_active          TINYINT(1)   DEFAULT 1,
  created_at         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- System users (admins + employees) ---------------------
-- employee_id links to the site's `employees` row (name/photo reuse).
-- work_mode: office   = punch only inside the location radius
--            field    = punch anywhere, GPS still recorded (driver, electrician)
--            flexible = GPS optional (CEOs, sponsor, consultancy)
CREATE TABLE IF NOT EXISTS pt_users (
  id                   INT AUTO_INCREMENT PRIMARY KEY,
  employee_id          INT          NULL,
  full_name            VARCHAR(160) NOT NULL,
  username             VARCHAR(80)  NOT NULL UNIQUE,     -- firstname.lastname
  password_hash        VARCHAR(255) NOT NULL,
  must_change_password TINYINT(1)   NOT NULL DEFAULT 1,
  role                 ENUM('admin','employee') NOT NULL DEFAULT 'employee',
  work_mode            ENUM('office','field','flexible') NOT NULL DEFAULT 'office',
  location_id          INT          NULL,
  schedule_id          INT          NULL,
  is_active            TINYINT(1)   DEFAULT 1,
  last_login_at        DATETIME     NULL,
  created_at           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_employee (employee_id),
  CONSTRAINT fk_ptu_location FOREIGN KEY (location_id) REFERENCES pt_locations(id) ON DELETE SET NULL,
  CONSTRAINT fk_ptu_schedule FOREIGN KEY (schedule_id) REFERENCES pt_schedules(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- Punches (raw clock records — never deleted) -----------
-- punched_at always comes from the SERVER clock, never the phone.
-- An admin correction voids the original row (is_void=1, voided_by)
-- and inserts a new row with source='admin'; both stay for audit.
CREATE TABLE IF NOT EXISTS pt_punches (
  id          BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT           NOT NULL,
  type        ENUM('in','break_start','break_end','out') NOT NULL,
  punched_at  DATETIME      NOT NULL,
  lat         DECIMAL(10,7) NULL,
  lng         DECIMAL(10,7) NULL,
  accuracy_m  INT           NULL,                       -- GPS accuracy reported by the phone
  distance_m  INT           NULL,                       -- distance to the assigned location
  status      ENUM('ok','outside','no_gps','manual') NOT NULL DEFAULT 'ok',
  photo_path  VARCHAR(255)  NULL,                       -- optional selfie, stored outside public_html
  source      ENUM('web','app','admin') NOT NULL DEFAULT 'web',
  ip          VARCHAR(45)   DEFAULT '',
  user_agent  VARCHAR(255)  DEFAULT '',
  is_void     TINYINT(1)    NOT NULL DEFAULT 0,
  voided_by   BIGINT        NULL,                       -- id of the row that replaced this one
  created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user_time (user_id, punched_at),
  CONSTRAINT fk_ptp_user FOREIGN KEY (user_id) REFERENCES pt_users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- Audit log (every admin change, with reason) -----------
CREATE TABLE IF NOT EXISTS pt_audit_log (
  id          BIGINT AUTO_INCREMENT PRIMARY KEY,
  actor_id    INT          NOT NULL,                    -- pt_users.id of the admin
  action      VARCHAR(40)  NOT NULL,                    -- create_user, reset_password, edit_punch, ...
  entity      VARCHAR(40)  NOT NULL,                    -- user, punch, schedule, location, setting
  entity_id   BIGINT       NULL,
  old_data    JSON         NULL,
  new_data    JSON         NULL,
  reason      VARCHAR(255) NOT NULL DEFAULT '',
  ip          VARCHAR(45)  DEFAULT '',
  created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_entity (entity, entity_id),
  INDEX idx_actor_time (actor_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- Failed logins (lockout by IP + username) --------------
CREATE TABLE IF NOT EXISTS pt_login_attempts (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  ip           VARCHAR(45) NOT NULL,
  username     VARCHAR(80) DEFAULT '',
  attempted_at DATETIME    NOT NULL,
  INDEX idx_ip_time (ip, attempted_at),
  INDEX idx_user_time (username, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- Chat ---------------------------------------------------
-- direct: one thread per employee with "the administration" (every admin sees it);
--         employee_user_id identifies the employee.
-- group:  created by an admin; members in pt_chat_members (admins always see all).
CREATE TABLE IF NOT EXISTS pt_chat_conversations (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  type             ENUM('direct','group') NOT NULL,
  title            VARCHAR(120) NULL,
  employee_user_id INT          NULL UNIQUE,
  created_by       INT          NOT NULL,
  last_message_at  DATETIME     NULL,
  created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_last (last_message_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pt_chat_members (
  conversation_id INT NOT NULL,
  user_id         INT NOT NULL,
  PRIMARY KEY (conversation_id, user_id),
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pt_chat_messages (
  id              BIGINT AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT       NOT NULL,
  sender_id       INT       NOT NULL,
  body            TEXT      NOT NULL,
  created_at      DATETIME  NOT NULL,
  INDEX idx_conv (conversation_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Files live OUTSIDE public_html (like the CVs); only conversa/arquivo.php can serve them.
CREATE TABLE IF NOT EXISTS pt_chat_files (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  message_id      BIGINT       NOT NULL,
  conversation_id INT          NOT NULL,
  stored_name     VARCHAR(80)  NOT NULL,
  original_name   VARCHAR(200) NOT NULL,
  mime            VARCHAR(100) NOT NULL,
  size_bytes      INT          NOT NULL,
  INDEX idx_msg (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Last message each person has seen in each conversation (unread counters).
CREATE TABLE IF NOT EXISTS pt_chat_reads (
  conversation_id INT    NOT NULL,
  user_id         INT    NOT NULL,
  last_read_id    BIGINT NOT NULL DEFAULT 0,
  PRIMARY KEY (conversation_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- System settings (key/value, like the site's `settings`) ----
CREATE TABLE IF NOT EXISTS pt_settings (
  skey   VARCHAR(64) PRIMARY KEY,
  svalue TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO pt_settings (skey, svalue) VALUES
  ('timezone',          'America/Guyana'),
  ('require_photo',     '0'),
  ('outside_policy',    'flag'),   -- flag = accept and mark "outside"; block = refuse
  ('min_gps_accuracy',  '100');    -- metres; worse readings are flagged
