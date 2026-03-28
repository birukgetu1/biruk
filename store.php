<?php
// Set proper headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Enable error logging but don't display to users
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Security: Check if request is AJAX (optional)
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Get and validate parameters
$latitude = isset($_GET['lat']) ? filter_var($_GET['lat'], FILTER_VALIDATE_FLOAT) : null;
$longitude = isset($_GET['lng']) ? filter_var($_GET['lng'], FILTER_VALIDATE_FLOAT) : null;
$timestamp = isset($_GET['timestamp']) ? filter_var($_GET['timestamp'], FILTER_VALIDATE_INT) : time();

// Validate coordinates
if ($latitude === null || $latitude === false || 
    $longitude === null || $longitude === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid coordinates provided']);
    exit;
}

// Validate coordinate ranges
if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    http_response_code(400);
    echo json_encode(['error' => 'Coordinates out of valid range']);
    exit;
}

// Sanitize for file writing
$latitude = round($latitude, 6);
$longitude = round($longitude, 6);

// Define log file path (store outside web root in production)
$logFile = __DIR__ . '/location_log.txt';

// Prepare data with timestamp and IP for auditing
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$dateTime = date('Y-m-d H:i:s', $timestamp);

$logEntry = sprintf(
    "[%s] IP: %s | Location: %.6f, %.6f | User-Agent: %s\n",
    $dateTime,
    $ipAddress,
    $latitude,
    $longitude,
    substr($userAgent, 0, 100) // Truncate long user agents
);

// Write to file with proper error handling
try {
    // Use 'a' mode to append instead of overwrite
    if (file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX) === false) {
        throw new Exception("Failed to write to log file");
    }
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Location saved successfully',
        'timestamp' => $dateTime
    ]);
    
} catch (Exception $e) {
    // Log error internally
    error_log("Location storage error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save location data']);
}
?>
