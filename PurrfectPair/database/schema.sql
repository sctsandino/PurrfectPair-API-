CREATE DATABASE IF NOT EXISTS auth_db;
USE auth_db;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(15) NOT NULL CHECK (
        phone_number REGEXP '^09[0-9]{9}$' OR phone_number REGEXP '^\\+639[0-9]{9}$'
    ),
    password VARCHAR(255) NOT NULL,
    token VARCHAR(64) UNIQUE NULL,
    token_expiry DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add indexes for faster lookups
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_token ON users(token);

-- Posts Table
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    cat_image VARCHAR(255) NOT NULL,
    cat_name VARCHAR(100) NOT NULL CHECK (CHAR_LENGTH(cat_name) > 0),
    cat_age INT NOT NULL CHECK (cat_age >= 0),
    cat_breed VARCHAR(100) NOT NULL CHECK (CHAR_LENGTH(cat_breed) > 0),
    status ENUM('pending', 'approved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add indexes for faster post retrieval
CREATE INDEX idx_posts_user_id ON posts(user_id);
CREATE INDEX idx_posts_status ON posts(status);

-- Admins Table (Only 1 pre-registered admin)
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    token VARCHAR(64) UNIQUE NULL,
    token_expiry DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Add indexes for faster admin authentication
CREATE INDEX idx_admins_email ON admins(email);
CREATE INDEX idx_admins_token ON admins(token);

-- Insert the pre-registered admin (only do this once)
INSERT INTO admins (email, password) VALUES
('admin@example.com', '$2y$10$hashedpassword');
