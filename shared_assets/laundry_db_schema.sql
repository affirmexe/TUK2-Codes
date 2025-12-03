-- Create database
CREATE DATABASE laundry_db;
USE laundry_db;

-- Create users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    address VARCHAR(255),
    email VARCHAR(255),
    no_telp VARCHAR(20),
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create staffs table
CREATE TABLE staffs (
    staff_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    address VARCHAR(255),
    email VARCHAR(255),
    no_telp VARCHAR(20),
    password VARCHAR(255),
    role VARCHAR(100),
    start_time TIME,
    end_time TIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create admins table
CREATE TABLE admins (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    address VARCHAR(255),
    email VARCHAR(255),
    no_telp VARCHAR(20),
    password VARCHAR(255),
    role VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create transactions table
CREATE TABLE transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    staff_id INT,
    entry_date TIMESTAMP,
    status BOOLEAN,
    clothings_amount INT,
    clothings_detail VARCHAR(255),
    total_price INT,
    completion_date TIMESTAMP,
    pickup_date TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (staff_id) REFERENCES staffs(staff_id)
);

-- Create attendance table
CREATE TABLE attendance (
    attendance_id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT,
    attendance_date DATE,
    check_in_time DATETIME,
    check_out_time DATETIME,
    is_late BOOLEAN DEFAULT FALSE,
    total_hours DECIMAL(4,2),
    status ENUM('present', 'absent', 'partial') DEFAULT 'present',
    FOREIGN KEY (staff_id) REFERENCES staffs(staff_id),
    UNIQUE KEY unique_staff_date (staff_id, attendance_date)
);

-- Create reviews table
CREATE TABLE reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    transaction_id INT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id)
);

-- Insert default data for users
INSERT INTO users (name, address, email, no_telp, password) VALUES
('user1', 'Jl. Pahlawan No. 88, Kel. Purworejo, Kec. Laweyan, Surakarta', 'user1@mail.com', '0822-6451-7809', '$2y$10$bFaYX2S47xpzdMGYkyQZ/.6yzCRRRkI7HDer3LcnekeOov6rfK7Y6');

-- Insert default data for staffs
INSERT INTO staffs (name, address, email, no_telp, password, role) VALUES
('staff1', 'Jl. Kenanga Indah Blok C-14, Perum Bumi Asri, Metro Pusat, Kota Metro, Lampung', 'staff1@mail.com', '0852-9174-3368', '$2y$10$p4Eedi4.pykMZoCLtcpRiOIdKrbn/e1WbihPOZI0WvjPZO2SNcwJW', 'Staff');

-- Insert default data for admins
INSERT INTO admins (name, address, email, no_telp, password, role) VALUES
('admin1', 'Jl. Melati No. 23, RT 02/RW 05, Kel. Sukamaju, Kec. Klaten Utara', 'admin1@mail.com', '0813-2765-4421', '$2y$10$ps3Xdw2VPi670U1pQJzVreOzSzN2MwVhu37B6xAkaB5kCum6GqRfK', 'Admin');
