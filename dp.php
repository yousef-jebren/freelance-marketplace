<?php
$host = "localhost";
$dbname = "freelance_1230251";   
$user = "root";
$pass = "";

// Correct DSN
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

try {
    $conn = new PDO($dsn, $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "connected successfully";  // optional
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
