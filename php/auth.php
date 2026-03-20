<?php
session_start();

function checkLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../index.html");
        exit();
    }
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}
?>