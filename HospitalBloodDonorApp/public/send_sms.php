<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Read JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Validate phone number
if (!isset($data['phone']) || empty($data['phone'])) {
    echo json_encode(["success" => false, "error" => "Phone number is required"]);
    exit();
}

$phone = $data['phone'];
$message = "Urgent: A hospital needs your blood donation. Please respond ASAP!";

// Use TextBelt self-hosted API
$url = 'http://localhost:9090/text';
$postData = http_build_query([
    'phone' => $phone,
    'message' => $message,
    'key' => 'textbelt'  // Default key for self-hosted instance
]);

$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded",
        'method' => 'POST',
        'content' => $postData,
    ]
];

$context = stream_context_create($options);
$response = @file_get_contents($url, false, $context);

// Handle request failure
if ($response === false) {
    $errorMsg = "Failed to connect to SMS API";
    logSMSStatus($phone, $errorMsg);
    echo json_encode(["success" => false, "error" => $errorMsg]);
    exit();
}

$result = json_decode($response, true);

// Log and return response
if (!empty($result['success']) && $result['success']) {
    logSMSStatus($phone, "SMS sent successfully");
    echo json_encode(["success" => true, "message" => "SMS sent successfully"]);
} else {
    $errorMsg = $result['error'] ?? "Unknown error";
    logSMSStatus($phone, $errorMsg);
    echo json_encode(["success" => false, "error" => $errorMsg]);
}

// Function to log SMS status
function logSMSStatus($phone, $status) {
    $logFile = __DIR__ . '/sms_log.txt';
    $logData = date('Y-m-d H:i:s') . " | Phone: $phone | Status: $status\n";
    file_put_contents($logFile, $logData, FILE_APPEND);
}

exit();
?>
