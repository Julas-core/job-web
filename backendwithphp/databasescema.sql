-- Users table (Login info)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Job Seeker Profile
CREATE TABLE job_seeker (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    fullname VARCHAR(255),
    profession_title VARCHAR(255),
    skill_level VARCHAR(50),
    city VARCHAR(100),
    primary_interest VARCHAR(255),
    bio TEXT,
    portofilio_link TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Company Profile
CREATE TABLE company (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    company_name VARCHAR(255),
    contact_name VARCHAR(255),
    description TEXT,
    representative VARCHAR(100),
    location VARCHAR(100),
    FOREIGN KEY (-- Users table (Login info)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Job Seeker Profile
CREATE TABLE job_seeker (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    fullname VARCHAR(255),
    profession_title VARCHAR(255),
    skill_level VARCHAR(50),
    city VARCHAR(100),
    primary_interest VARCHAR(255),
    bio TEXT,
    portofilio_link TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Company Profile
CREATE TABLE company (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    company_name VARCHAR(255),
    contact_name VARCHAR(255),
    description TEXT,
    representative VARCHAR(100),
    location VARCHAR(100),
    FOREIGN KEY (