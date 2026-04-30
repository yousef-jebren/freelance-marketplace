<?php
require_once "dp.php";

$userId = rand(1000000000, 9999999999);
$firstName = $_POST['first_name'];
$lastName  = $_POST['last_name'];
$email     = $_POST['email'];
$password  = $_POST['password'];
$confirm   = $_POST['confirm_password'];
$phone     = $_POST['phone_number'];
$role      = $_POST['role'];
$city      = $_POST['city'];
$bio       = $_POST['bio'] ?? "";

// Password match
if ($password !== $confirm) {
    header("Location: register.php?error=password_mismatch");
    exit;
}



// Bio required for Freelancer
if ($role === "Freelancer" && empty($bio)) {
    header("Location: register.php?error=bio_required");
    exit;
}
$emailCheckSql = "SELECT COUNT(*) FROM users WHERE email = :email";
$stmt = $conn->prepare($emailCheckSql);
$stmt->bindValue(':email', $email);
$stmt->execute();
$emailExists = $stmt->fetchColumn();
if ($emailExists) {
    header("Location: register.php?error=email_exists");
    exit;
}



// Insert using prepared statement
$sql = "INSERT INTO users 
(user_id,first_name, last_name, email, password, phone, country, city, role, status,  registration_date)
VALUES 
(:userId, :firstName, :lastName, :email, :password, :phone, :country, :city, :role, 'Active',  date('Y-m-d H:i:s'))";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':userId', $userId);
$stmt->bindValue(':firstName', $firstName);
$stmt->bindValue(':lastName', $lastName);
$stmt->bindValue(':email', $email);
$stmt->bindValue(':password', $password);
$stmt->bindValue(':phone', $phone);
$stmt->bindValue(':country', 'Palestine');
$stmt->bindValue(':city', $city);
$stmt->bindValue(':role', $role);
$stmt->execute();


header("Location: login.php?success=1");
exit;
