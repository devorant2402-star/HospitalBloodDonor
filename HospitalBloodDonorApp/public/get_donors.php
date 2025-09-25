<?php
include __DIR__ . '/../db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($conn)) {
    error_log("Database connection failed in get_donors.php");
    die(json_encode(["error" => "Database connection failed.", "success" => false]));
}

// Get input parameters
$bloodGroup = isset($_GET['blood_group']) ? trim($_GET['blood_group']) : "";
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
$radius = isset($_GET['radius']) ? floatval($_GET['radius']) : null;

// Base query
$query = "SELECT 
    id, name, age, sex, blood_group, mobile_number, address,
    last_donation_date, is_available, latitude, longitude";

// Add distance calculation if coordinates provided
if ($lat && $lng) {
    $query .= ", 
        (6371 * ACOS(
            COS(RADIANS(?)) * COS(RADIANS(latitude)) * 
            COS(RADIANS(longitude) - RADIANS(?)) + 
            SIN(RADIANS(?)) * SIN(RADIANS(latitude))
        )) AS distance";
}

$query .= " FROM patients WHERE is_available = 1";

// Add blood group filter if specified
if (!empty($bloodGroup)) {
    $query .= " AND blood_group = ?";
}

// Add distance filter if coordinates and radius provided
if ($lat && $lng && $radius) {
    $query .= " HAVING distance <= ?";
}

$query .= " ORDER BY " . ($lat && $lng ? "distance" : "created_at") . " DESC LIMIT 50";

$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die(json_encode(["error" => "Database query preparation failed", "success" => false]));
}

// Prepare parameters for binding
$params = [];
$types = "";

if ($lat && $lng) {
    $params = array_merge($params, [$lat, $lng, $lat]);
    $types .= "ddd";
}

if (!empty($bloodGroup)) {
    $params[] = $bloodGroup;
    $types .= "s";
}

if ($lat && $lng && $radius) {
    $params[] = $radius;
    $types .= "d";
}

$stmt->bind_param($types, ...$params);
if (!$stmt->execute()) {
    error_log("Execute failed: " . $stmt->error);
    die(json_encode(["error" => "Database query execution failed", "success" => false]));
}

$result = $stmt->get_result();
$donors = [];
while ($row = $result->fetch_assoc()) {
    $donors[] = [
        'name' => $row['name'],
        'mobile_number' => $row['mobile_number'],
        'address' => $row['address'],
        'blood_group' => $row['blood_group'],
        'last_donation_date' => $row['last_donation_date'],
        'is_available' => (bool)$row['is_available'],
        'latitude' => $row['latitude'] ?? null,
        'longitude' => $row['longitude'] ?? null
    ];
}

header('Content-Type: application/json');
if (empty($donors)) {
    $message = "No available donors found";
    $message .= !empty($bloodGroup) ? " for blood group $bloodGroup" : "";
    $message .= ($lat && $lng && $radius) ? " within $radius km radius" : "";
    
    echo json_encode([
        "message" => $message,
        "success" => false,
        "count" => 0
    ]);
} else {
    echo json_encode([
        "donors" => $donors,
        "success" => true,
        "count" => count($donors)
    ]);
}

$stmt->close();
$conn->close();
?>
