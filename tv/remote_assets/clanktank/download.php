<?php
/**
 * Avatar Download Handler
 * 
 * Creates ZIP archives of avatars for download
 */

// Check if the ZipArchive extension is available
if (!class_exists('ZipArchive')) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'ZipArchive extension is not available on this server.'
    ]);
    exit;
}

// Base directory
$baseDir = dirname(__FILE__);

// Create a temporary file for the ZIP
$tempFile = tempnam(sys_get_temp_dir(), 'avatar_');

// Initialize ZIP archive
$zip = new ZipArchive();
if ($zip->open($tempFile, ZipArchive::CREATE) !== true) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to create ZIP archive.'
    ]);
    exit;
}

// Get a single avatar
if (isset($_GET['avatar'])) {
    $avatarId = $_GET['avatar'];
    $avatarDir = $baseDir . '/avatars/' . $avatarId;
    
    // Check if the avatar directory exists
    if (!is_dir($avatarDir)) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Avatar not found: ' . $avatarId
        ]);
        exit;
    }
    
    // Add all files from the avatar directory to the ZIP
    addDirToZip($zip, $avatarDir, 'avatar/');
    
    // Close the ZIP file
    $zip->close();
    
    // Send the ZIP file to the browser
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $avatarId . '.zip"');
    header('Content-Length: ' . filesize($tempFile));
    readfile($tempFile);
    
    // Delete the temporary file
    unlink($tempFile);
}
// Get all avatars
else if (isset($_GET['all'])) {
    $avatarsDir = $baseDir . '/avatars';
    
    // Check if the avatars directory exists
    if (!is_dir($avatarsDir)) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Avatars directory not found'
        ]);
        exit;
    }
    
    // Get all avatar directories
    $avatarFolders = scandir($avatarsDir);
    foreach ($avatarFolders as $folder) {
        if ($folder != '.' && $folder != '..' && is_dir($avatarsDir . '/' . $folder)) {
            $avatarDir = $avatarsDir . '/' . $folder;
            addDirToZip($zip, $avatarDir, 'avatars/' . $folder . '/');
        }
    }
    
    // Close the ZIP file
    $zip->close();
    
    // Send the ZIP file to the browser
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="all-avatars.zip"');
    header('Content-Length: ' . filesize($tempFile));
    readfile($tempFile);
    
    // Delete the temporary file
    unlink($tempFile);
}
else {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required parameters'
    ]);
    exit;
}

/**
 * Recursively add a directory to a ZIP archive
 * 
 * @param ZipArchive $zip The ZIP archive
 * @param string $dir The directory to add
 * @param string $zipPath The path within the ZIP archive
 */
function addDirToZip($zip, $dir, $zipPath) {
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filePath = $dir . '/' . $file;
            
            if (is_dir($filePath)) {
                // Recursively add subdirectories
                addDirToZip($zip, $filePath, $zipPath . $file . '/');
            } else {
                // Add file to ZIP
                $zip->addFile($filePath, $zipPath . $file);
            }
        }
    }
}