<?php
header('Content-Type: application/json');
require_once 'db.php';
require_once 'auth.php';

checkLogin();
$user_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("
        SELECT u.fullname, u.email, up.phone, DATE_FORMAT(u.created_at, '%M %Y') as member_since
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $profile
    ]);
} catch (Exception $e) {
    error_log("Error in get_profile: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "An error occurred."]);
}
?>
