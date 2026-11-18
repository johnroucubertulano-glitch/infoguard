USE Security_lab;

CREATE TABLE IF NOT EXISTS folders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(9) NOT NULL,
  folder_name VARCHAR(80) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_user_folder (user_id, folder_name),
  FOREIGN KEY (user_id) REFERENCES users(id_number) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS documents (
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
