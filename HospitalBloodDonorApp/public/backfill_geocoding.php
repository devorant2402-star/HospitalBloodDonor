<?php
include __DIR__ . '/../db.php';
require_once 'geocode.php';

// Get all donors without coordinates
$query = "SELECT id, address FROM patients WHERE latitude IS NULL OR longitude IS NULL";
$result = $conn->query($query);

if (!$result) {
    die("Error fetching donors: " . $conn->error);
}

$updated = 0;
while ($row = $result->fetch_assoc()) {
    $coords = geocodeAddress($row['address']);
    if ($coords) {
        $update = $conn->prepare("UPDATE patients SET latitude = ?, longitude = ? WHERE id = ?");
        $update->bind_param("ddi", $coords['latitude'], $coords['longitude'], $row['id']);
        if ($update->execute()) {
            $updated++;
        }
        $update->close();
    }
    // Be nice to the API - sleep briefly between requests
    usleep(200000); // 200ms delay
}

echo "Successfully updated coordinates for $updated donors\n";
$conn->close();
?>
