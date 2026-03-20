<?php
require_once 'db.php';
require_once 'auth.php';
// checkLogin();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Transaction logic here
    echo json_encode(["status" => "success", "message" => "Transaction recorded."]);
}
?>