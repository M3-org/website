<?php
/**
 * Media API Endpoint
 * 
 * This file provides an API endpoint to retrieve information about
 * media files in the commercials, reels, and avatars directories.
 */

// Set the content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

/**
 * Gets all files in a directory
 * 
 * @param string $dir The directory to scan
 * @return array Array of files in the directory
 */
function getDirectoryContents($dir) {
    $result = [];
    
    if (!is_dir($dir)) {
        return $result;
    }
    
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                // For avatars directory, we need to go one level deeper
                if (basename($dir) === 'avatars') {
                    $avatarInfo = [
                        'name' => $file,
                        'files' => []
                    ];
                    
                    $avatarFiles = scandir($path);
                    foreach ($avatarFiles as $avatarFile) {
                        if ($avatarFile != '.' && $avatarFile != '..') {
                            $avatarInfo['files'][] = $avatarFile;
                        }
                    }
                    
                    $result[] = $avatarInfo;
                }
            } else {
                // Skip manifest.php in commercials directory when listing files
                if (basename($dir) === 'commercials' && $file === 'manifest.php') {
                    continue;
                }
                
                $result[] = $file;
            }
        }
    }
    
    return $result;
}

/**
 * Gets avatar information by ID (folder name)
 * 
 * @param string $avatarId The avatar ID (folder name)
 * @return array Avatar information or null if not found
 */
function getAvatarInfo($avatarId) {
    $baseDir = dirname(__FILE__);
    $avatarPath = $baseDir . '/avatars/' . $avatarId;
    
    if (!is_dir($avatarPath)) {
        return null;
    }
    
    // Get default avatar info for fallback values
    $defaultAvatar = null;
    if ($avatarId !== 'default') {
        $defaultAvatar = getAvatarInfo('default');
    }
    
    $files = scandir($avatarPath);
    $jsonFile = null;
    $glbFile = null;
    $pngFile = null;
    
    // Find the first file of each type
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            
            if ($extension === 'json' && $jsonFile === null) {
                $jsonFile = $file;
            } else if ($extension === 'glb' && $glbFile === null) {
                $glbFile = $file;
            } else if ($extension === 'png' && $pngFile === null) {
                $pngFile = $file;
            }
        }
    }
    
    // Construct the avatar info
    $avatar = [
        'name' => $avatarId
    ];
    
    // Add headshot URL if found, or use default - using relative paths
    if ($pngFile !== null) {
        $avatar['headshot'] = 'avatars/' . $avatarId . '/' . $pngFile;
    } else if ($defaultAvatar !== null && isset($defaultAvatar['headshot'])) {
        $avatar['headshot'] = $defaultAvatar['headshot'];
    }
    
    // Add model URL if found, or use default - using relative paths
    if ($glbFile !== null) {
        $avatar['model'] = 'avatars/' . $avatarId . '/' . $glbFile;
    } else if ($defaultAvatar !== null && isset($defaultAvatar['model'])) {
        $avatar['model'] = $defaultAvatar['model'];
    }
    
    // Add character data if found, or use default
    if ($jsonFile !== null) {
        $jsonPath = $avatarPath . '/' . $jsonFile;
        $jsonContent = file_get_contents($jsonPath);
        $avatar['character'] = json_decode($jsonContent, true);
    } else if ($defaultAvatar !== null && isset($defaultAvatar['character'])) {
        $avatar['character'] = $defaultAvatar['character'];
    }
    
    return $avatar;
}

/**
 * Gets information for all avatars
 * 
 * @return array Array of avatar information
 */
function getAllAvatarsInfo() {
    $baseDir = dirname(__FILE__);
    $avatarsDir = $baseDir . '/avatars';
    $result = [];
    
    if (!is_dir($avatarsDir)) {
        return $result;
    }
    
    $folders = scandir($avatarsDir);
    
    foreach ($folders as $folder) {
        if ($folder != '.' && $folder != '..' && is_dir($avatarsDir . '/' . $folder)) {
            $avatarInfo = getAvatarInfo($folder);
            if ($avatarInfo !== null && $folder !== 'default') {
                $result[] = $avatarInfo;
            }
        }
    }
    
    return $result;
}

/**
 * Returns all media information
 * 
 * @return array JSON formatted data containing all media information
 */
function getAllInfo() {
    $baseDir = dirname(__FILE__);
    
    $data = [
        'commercials' => getDirectoryContents($baseDir . '/commercials'),
        'reels' => getDirectoryContents($baseDir . '/reels'),
        'avatars' => getDirectoryContents($baseDir . '/avatars')
    ];
    
    return $data;
}

// Handle API requests
$method = isset($_GET['method']) ? $_GET['method'] : '';

switch ($method) {
    case 'getAllInfo':
        echo json_encode(getAllInfo(), JSON_PRETTY_PRINT);
        break;
    
    case 'getAvatar':
        $avatarId = isset($_GET['id']) ? $_GET['id'] : '';
        if (empty($avatarId)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing avatar ID parameter'
            ], JSON_PRETTY_PRINT);
        } else {
            $avatar = getAvatarInfo($avatarId);
            if ($avatar === null) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Avatar not found'
                ], JSON_PRETTY_PRINT);
            } else {
                echo json_encode($avatar, JSON_PRETTY_PRINT);
            }
        }
        break;
    
    case 'getAllAvatars':
        echo json_encode(getAllAvatarsInfo(), JSON_PRETTY_PRINT);
        break;
    
    default:
        // Return available methods if no method specified or invalid method
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid or missing method parameter',
            'available_methods' => ['getAllInfo', 'getAvatar', 'getAllAvatars']
        ], JSON_PRETTY_PRINT);
        break;
}
?>