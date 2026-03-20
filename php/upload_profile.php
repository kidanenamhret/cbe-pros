<?php
session_start();
require_once 'db.php';
require_once 'auth.php';

checkLogin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if file was uploaded
if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded or upload error occurred.']);
    exit();
}

$file = $_FILES['profile_image'];

// Validate file type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['status' => 'error', 'message' => 'Only JPG, PNG, GIF, and WEBP images are allowed.']);
    exit();
}

// Validate file size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['status' => 'error', 'message' => 'Image size must be less than 5MB.']);
    exit();
}

// Ensure it really is an image
$check = getimagesize($file['tmp_name']);
if($check === false) {
    echo json_encode(['status' => 'error', 'message' => 'File is not a valid image.']);
    exit();
}

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/profiles/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'user_' . $user_id . '_' . time() . '.' . $extension;
$destination = $upload_dir . $filename;

// Move file
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save image.']);
    exit();
}

try {
    // First, check if a profile exists for this user
    $stmt = $conn->prepare("SELECT user_id FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    if ($stmt->fetch()) {
        // Update existing row
        $stmt = $conn->prepare("UPDATE user_profiles SET profile_picture = ? WHERE user_id = ?");
        $stmt->execute([$filename, $user_id]);
    } else {
        // Insert new row
        $stmt = $conn->prepare("
            INSERT INTO user_profiles (user_id, phone, profile_picture) 
            VALUES (?, '', ?)
        ");
        $stmt->execute([$user_id, $filename]);
    }
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'Profile image updated successfully.',
        'image_url' => 'uploads/profiles/' . $filename
    ]);

} catch (PDOException $e) {
    error_log("Upload error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error.']);
}
?>
