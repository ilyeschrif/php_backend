<?php

// Enable CORS for all origins and HTTP methods
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json'); // Set content type to JSON

$uploadDir = __DIR__ . '/images/';

// Ensure the upload directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Maximum allowed file size in bytes (e.g., 5MB)
$maxFileSize = 5 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $files = $_FILES['images'];
    $response = []; // To hold the response for each file

    // Iterate through each uploaded file
    for ($i = 0; $i < count($files['name']); $i++) {
        $fileName = basename($files['name'][$i]); // Original file name
        $uniqueFileName = time() . '-' . $fileName; // Ensure uniqueness
        $targetFilePath = $uploadDir . $uniqueFileName; // Full path to save the file

        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION); // Get file extension
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']; // Allowed file types

        // Check for upload errors
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form.',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            ];
            $response[] = [
                'fileName' => $fileName,
                'status' => 'error',
                'message' => $errorMessages[$files['error'][$i]] ?? 'Unknown upload error.',
                'path' => null,
            ];
            continue;
        }

        // Validate file type
        if (!in_array(strtolower($fileType), $allowedTypes)) {
            $response[] = [
                'fileName' => $fileName,
                'status' => 'error',
                'message' => 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes),
                'path' => null,
            ];
            continue;
        }

        // Validate file size
        if ($files['size'][$i] > $maxFileSize) {
            $response[] = [
                'fileName' => $fileName,
                'status' => 'error',
                'message' => 'File size exceeds the maximum limit of 5MB.',
                'path' => null,
            ];
            continue;
        }

        // Move file to target directory
        if (is_uploaded_file($files['tmp_name'][$i])) {
            if (move_uploaded_file($files['tmp_name'][$i], $targetFilePath)) {
                $response[] = [
                    'fileName' => $fileName,
                    'status' => 'success',
                    'path' => '/images/' . $uniqueFileName, // Relative path to the file
                ];
            } else {
                $response[] = [
                    'fileName' => $fileName,
                    'status' => 'error',
                    'message' => 'Failed to move uploaded file to the target directory.',
                    'path' => null,
                ];
            }
        } else {
            $response[] = [
                'fileName' => $fileName,
                'status' => 'error',
                'message' => 'Possible file upload attack detected.',
                'path' => null,
            ];
        }
    }

    // Return the response as JSON
    echo json_encode($response);
} else {
    // No files uploaded or wrong request method
    echo json_encode(['error' => 'No files uploaded or invalid request.']);
}
