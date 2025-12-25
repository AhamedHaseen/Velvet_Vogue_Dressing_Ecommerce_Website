<?php
// Common admin authentication and session handling
session_start();

// Include database connection
include "db_connection.php";

// Check if admin is logged in (flexible check for both admin and user sessions)
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_id'])) {
    header('Location: adm_login.php');
    exit();
}

// Get admin info (flexible for both admin and user sessions)
$admin_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? '';
$admin_name = $_SESSION['admin_name'] ?? $_SESSION['username'] ?? 'Admin User';
$admin_email = $_SESSION['admin_email'] ?? 'admin@example.com';
$admin_role = $_SESSION['admin_role'] ?? 'Administrator';

// Function to safely execute queries
function executeQuery($conn, $query, $default = 0) {
    try {
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row[array_keys($row)[0]] ?? $default;
        }
    } catch (Exception $e) {
        // Return default on error
    }
    return $default;
}
?>