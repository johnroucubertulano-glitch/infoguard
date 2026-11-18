CREATE DATABASE IF NOT EXISTS Security_lab CHARACTER SET utf8mb4;
USE Security_lab;

DROP TABLE IF EXISTS documents, folders, alerts, login_attempts, security_logs, otp_codes;
DROP TABLE IF EXISTS password_history, auth_answers, otps, activity_log, security_flags, ip_blocks, login_tracker, users;

CREATE TABLE users (
  id_number     VARCHAR(9)   NOT NULL PRIMARY KEY,           -- xxxx-xxxx
  first_name    VARCHAR(50)  NOT NULL,
  middle_name   VARCHAR(50)  NULL,
  family_name   VARCHAR(50)  NOT NULL,
  name_ext      VARCHAR(10)  NULL,
  birthdate     DATE         NOT NULL,
  street        VARCHAR(50)  NOT NULL,
  barangay      VARCHAR(50)  NOT NULL,
  municipality  VARCHAR(50)  NOT NULL,
  province      VARCHAR(50)  NOT NULL,
  country       VARCHAR(50)  NOT NULL,
  zip           CHAR(4)      NOT NULL,
  email         VARCHAR(100) NOT NULL UNIQUE,
  username      VARCHAR(20)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('user','admin') NOT NULL DEFAULT 'user',
  is_verified   TINYINT(1)   NOT NULL DEFAULT 0,
  consent_at    DATETIME     NOT NULL,
  emergency_enc VARCHAR(255) NOT NULL,                       -- AES-256 encrypted
  program       VARCHAR(10)  NOT NULL,
  date_enrolled DATE         NOT NULL,
  remarks       VARCHAR(100) NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE auth_answers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_number VARCHAR(9) NOT NULL,
  q_no TINYINT NOT NULL,
  answer_hash VARCHAR(255) NOT NULL,
  FOREIGN KEY (id_number) REFERENCES users(id_number) ON DELETE CASCADE
);

CREATE TABLE otps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_number VARCHAR(9) NOT NULL,
  purpose ENUM('register','login','reset') NOT NULL,
  code_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  attempts INT NOT NULL DEFAULT 0,
  used TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (id_number, purpose),
  FOREIGN KEY (id_number) REFERENCES users(id_number) ON DELETE CASCADE
);

CREATE TABLE password_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_number VARCHAR(9) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_number) REFERENCES users(id_number) ON DELETE CASCADE
);

-- Lockout counter per typed username (works for non-existing usernames too: no enumeration)
CREATE TABLE login_tracker (
  key_name VARCHAR(50) NOT NULL PRIMARY KEY,
  failed INT NOT NULL DEFAULT 0,
  locked_until DATETIME NULL
);

CREATE TABLE ip_blocks (
  ip VARCHAR(45) NOT NULL PRIMARY KEY,
  blocked_until DATETIME NOT NULL
);

-- Append-only: the application only ever INSERTs and SELECTs here.
CREATE TABLE activity_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_type VARCHAR(40) NOT NULL,
  username_typed VARCHAR(100) NULL,
  user_id VARCHAR(9) NULL,
  ip_address VARCHAR(45) NOT NULL,
  user_agent VARCHAR(255) NULL,
  details VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (event_type, created_at), INDEX (ip_address, created_at), INDEX (user_id, created_at)
);

CREATE TABLE folders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(9) NOT NULL,
  folder_name VARCHAR(80) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_folder (user_id, folder_name),
  FOREIGN KEY (user_id) REFERENCES users(id_number) ON DELETE CASCADE
);

CREATE TABLE documents (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(9) NOT NULL,
  folder_id INT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name CHAR(36) NOT NULL UNIQUE,
  mime_type VARCHAR(100) NOT NULL,
  file_size BIGINT UNSIGNED NOT NULL,
  sha256 CHAR(64) NOT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (user_id, folder_id, uploaded_at),
  FOREIGN KEY (user_id) REFERENCES users(id_number) ON DELETE CASCADE,
  FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE SET NULL
);

CREATE TABLE security_flags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pattern VARCHAR(30) NOT NULL,
  target VARCHAR(100) NOT NULL,
  severity ENUM('HIGH','MEDIUM','LOW') NOT NULL,
  details VARCHAR(255) NULL,
  flag_date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY one_per_day (pattern, target, flag_date)
);

/* OPTIONAL (protection of the log itself): run this as root, then put 'infoguard_app'
   in config.php instead of root. This account can never DELETE or UPDATE the log.
CREATE USER 'infoguard_app'@'localhost' IDENTIFIED BY 'change-me';
GRANT SELECT, INSERT, UPDATE, DELETE ON Security_lab.users TO 'infoguard_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON Security_lab.auth_answers TO 'infoguard_app'@'localhost';
GRANT SELECT, INSERT, UPDATE ON Security_lab.otps TO 'infoguard_app'@'localhost';
GRANT SELECT, INSERT ON Security_lab.password_history TO 'infoguard_app'@'localhost';
GRANT SELECT, INSERT, UPDATE ON Security_lab.login_tracker TO 'infoguard_app'@'localhost';
GRANT SELECT, INSERT, UPDATE ON Security_lab.ip_blocks TO 'infoguard_app'@'localhost';
GRANT SELECT, INSERT ON Security_lab.activity_log TO 'infoguard_app'@'localhost';
GRANT SELECT, INSERT ON Security_lab.security_flags TO 'infoguard_app'@'localhost';
GRANT SELECT, INSERT, DELETE ON Security_lab.folders TO 'infoguard_app'@'localhost';
GRANT SELECT, INSERT, DELETE ON Security_lab.documents TO 'infoguard_app'@'localhost';
*/
