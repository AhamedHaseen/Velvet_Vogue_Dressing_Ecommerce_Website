<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "velvet_vogue_ecommerce_web";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for better character support
$conn->set_charset("utf8mb4");
?>



