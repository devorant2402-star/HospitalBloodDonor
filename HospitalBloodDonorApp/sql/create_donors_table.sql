-- SQL to create patients table for blood donor application
CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    sex VARCHAR(10) NOT NULL,
    blood_group VARCHAR(3) NOT NULL,
    mobile_number VARCHAR(15) NOT NULL,
    address TEXT NOT NULL,
    last_donation_date DATE,
    is_available BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_blood_group (blood_group),
    INDEX idx_availability (is_available),
    INDEX idx_donation_date (last_donation_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Example insert matching user's sample data
INSERT INTO patients (name, age, sex, blood_group, mobile_number, address, last_donation_date, is_available)
VALUES 
    ('Aachal Chaudhari', 21, 'Female', 'A+', '8646953217', 'Rachna Metro Station, Nagpur', '2025-02-24', 1),
    ('John Doe', 30, 'Male', 'O+', '1234567890', '123 Main St, City', NULL, 1);
