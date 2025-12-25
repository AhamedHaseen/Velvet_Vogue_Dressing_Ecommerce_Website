<?php
// Common user authentication and session handling
session_start();

// Include database connection
include "db_connection.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: signIn.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';
$user_email = $_SESSION['user_email'] ?? '';
?>