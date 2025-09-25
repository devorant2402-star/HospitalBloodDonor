<?php
// Corrected path to db.php
include __DIR__ . '/../db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if form data is received via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $sex = trim($_POST['sex'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');
    $mobile_number = trim($_POST['mobile_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $last_donation = !empty($_POST['last_donation']) ? $_POST['last_donation'] : null;
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    // Ensure no field is empty
    if (empty($name) || empty($sex) || empty($blood_group) || empty($mobile_number) || empty($address)) {
        die("Error: All fields are required!");
    }

    // Check if $conn exists
    if (!isset($conn)) {
        die("Error: Database connection failed.");
    }

    // Check if patients table exists and has required columns
    $result = $conn->query("SHOW TABLES LIKE 'patients'");
    if ($result->num_rows > 0) {
        // Table exists - check for latitude/longitude columns
        $columns = $conn->query("SHOW COLUMNS FROM patients LIKE 'latitude'");
        if ($columns->num_rows == 0) {
            $conn->query("ALTER TABLE patients ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER address");
            $conn->query("ALTER TABLE patients ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude");
        }
    } else {
        // Create new table with all columns
        $createTable = "CREATE TABLE patients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            age INT NOT NULL,
            sex VARCHAR(10) NOT NULL,
            blood_group VARCHAR(5) NOT NULL,
            mobile_number VARCHAR(15) NOT NULL,
            address TEXT NOT NULL,
            latitude DECIMAL(10, 8) NULL,
            longitude DECIMAL(11, 8) NULL,
            last_donation_date DATE NULL,
            is_available BOOLEAN DEFAULT TRUE,
            sms_opt_out BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if (!$conn->query($createTable)) {
            die("Error creating table: " . $conn->error);
        }
        
        // Insert test data
        $testDonors = [
            ["John Doe", 30, "Male", "O+", "1234567890", "123 Main St, City", "2023-01-15", 1],
            ["Jane Smith", 25, "Female", "A-", "0987654321", "456 Oak Ave, Town", "2023-03-20", 1]
        ];
        
        foreach ($testDonors as $donor) {
            $stmt = $conn->prepare("INSERT INTO patients (name, age, sex, blood_group, mobile_number, address, last_donation_date, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisssssi", ...$donor);
            if ($stmt->execute()) {
                error_log("Test donor inserted: " . $donor[0]);
            } else {
                error_log("Failed to insert test donor: " . $stmt->error);
            }
            $stmt->close();
        }
    }

    // Geocode address using our utility function
    require_once 'geocode.php';
    $coords = geocodeAddress($address);
    $latitude = $coords ? $coords['latitude'] : null;
    $longitude = $coords ? $coords['longitude'] : null;

    // Prepare SQL statement for current submission
    $stmt = $conn->prepare("INSERT INTO patients (name, sex, blood_group, mobile_number, address, latitude, longitude, last_donation_date, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    // Bind parameters and execute
    $stmt->bind_param("sssssddsi", $name, $sex, $blood_group, $mobile_number, $address, $latitude, $longitude, $last_donation, $is_available);
    if ($stmt->execute()) {
        header("Location: http://localhost/HospitalBloodDonorApp/HospitalBloodDonorApp/public/search.html");
        exit();
    } else {
        die("Error executing statement: " . $stmt->error);
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
} else {
    die("Invalid request method!");
}
?>
