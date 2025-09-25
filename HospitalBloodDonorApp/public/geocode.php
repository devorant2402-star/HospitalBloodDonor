<?php
include __DIR__ . '/../db.php';

function geocodeAddress($address) {
    $apiKey = 'ebdddbdc2db647408baefd2c121fd4d2'; // OpenCage API key
    
    if (empty($apiKey)) {
        error_log('OpenCage API key not configured');
        return null;
    }

    $url = "https://api.opencagedata.com/geocode/v1/json?q=".urlencode($address)."&key={$apiKey}";
    
    $context = stream_context_create([
        'http' => ['timeout' => 5] // 5 second timeout
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        error_log('Geocoding API request failed');
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (!$data || $data['status']['code'] !== 200 || empty($data['results'])) {
        error_log('Geocoding failed: ' . ($data['status']['message'] ?? 'Invalid response'));
        return null;
    }
    
    return [
        'latitude' => $data['results'][0]['geometry']['lat'],
        'longitude' => $data['results'][0]['geometry']['lng'],
        'formatted_address' => $data['results'][0]['formatted'] ?? $address
    ];
}
?>
