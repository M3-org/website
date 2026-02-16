<?php
/**
 * Character Save Handler
 * 
 * Saves edited character JSON data for avatars
 */

// Set response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Only POST requests are allowed'
    ]);
    exit;
}

// Get the raw POST data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// Validate the data
if (!$data || !isset($data['avatarId']) || !isset($data['character'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid data format. avatarId and character are required.'
    ]);
    exit;
}

// Get the avatar ID and character data
$avatarId = $data['avatarId'];
$character = $data['character'];

// Validate avatar ID (prevent directory traversal)
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $avatarId)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid avatar ID format'
    ]);
    exit;
}

// Base directory
$baseDir = dirname(__FILE__);
$avatarDir = $baseDir . '/avatars/' . $avatarId;

// Check if the avatar directory exists
if (!is_dir($avatarDir)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Avatar directory not found: ' . $avatarId
    ]);
    exit;
}

// Find the character JSON file in the avatar directory
$jsonFile = null;
$files = scandir($avatarDir);
foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
        $jsonFile = $file;
        break;
    }
}

// If no JSON file found, create a new one with the avatar ID as the name
if ($jsonFile === null) {
    $jsonFile = $avatarId . '.json';
}

// Format the character data with indentation for readability
$jsonContent = json_encode($character, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Save the character data to the file
$jsonPath = $avatarDir . '/' . $jsonFile;
if (file_put_contents($jsonPath, $jsonContent) === false) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to write to file: ' . $jsonPath
    ]);
    exit;
}

// Return success response
echo json_encode([
    'status' => 'success',
    'message' => 'Character data saved successfully',
    'file' => $jsonFile
]);
?>