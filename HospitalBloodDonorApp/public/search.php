<?php
// Use consistent database connection
include __DIR__ . '/../db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate blood group input
    $blood_group = isset($_POST['blood_group']) ? trim($_POST['blood_group']) : "";
    if (empty($blood_group)) {
        die("<div class='container'><h1>Error: Blood group is required</h1></div>");
    }

    // Prepare and execute the query to find donors
    $query = "SELECT name, age, sex, mobile_number, address, last_donation_date, is_available 
              FROM patients 
              WHERE blood_group = ?
              ORDER BY is_available DESC, last_donation_date DESC";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        die("<div class='container'><h1>Error: Database query preparation failed</h1></div>");
    }

    $stmt->bind_param("s", $blood_group);
    
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        die("<div class='container'><h1>Error: Database query execution failed</h1></div>");
    }

    $result = $stmt->get_result();
    $total_donors = $result->num_rows;

    // Output results
    echo "<div class='container'>";
    if ($total_donors > 0) {
        echo "<h1>Found $total_donors donors with Blood Group: $blood_group</h1>";
        echo "<table>
                <tr>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Sex</th>
                    <th>Mobile Number</th>
                    <th>Address</th>
                    <th>Last Donation</th>
                    <th>Available</th>
                </tr>";
        
        while ($row = $result->fetch_assoc()) {
            $lastDonation = $row['last_donation_date'] ?? 'Never';
            $isAvailable = isset($row['is_available']) ? ($row['is_available'] ? 'Yes' : 'No') : 'Unknown';
            
            echo "<tr>
                    <td>{$row['name']}</td>
                    <td>{$row['age']}</td>
                    <td>{$row['sex']}</td>
                    <td>{$row['mobile_number']}</td>
                    <td>{$row['address']}</td>
                    <td>$lastDonation</td>
                    <td>$isAvailable</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<h1>No available donors found for blood group: $blood_group</h1>";
        echo "<p>Suggestions:</p>";
        echo "<ul>
                <li>Try a different blood group</li>
                <li>Check back later as new donors register</li>
                <li>Contact your local blood bank for alternatives</li>
              </ul>";
    }
    echo "</div>";

    $stmt->close();
} else {
    header("Location: search.html");
    exit();
}

$conn->close();
?>
