<?php
// Database Configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "perpustakaan_db";

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set character set
mysqli_set_charset($conn, "utf8");
?> 