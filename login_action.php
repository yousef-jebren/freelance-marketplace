<?php
session_start();
include "dp.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header("Location: login.php?error=1");
    exit;
}

 $s1 = "select * from users where email=:email and account_status='Active'";
            $stmt = $conn->prepare($s1);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();



$fullName = $user['first_name'] . ' ' . $user['last_name'];

if ($user && $user['password'] === $password) {

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['full_name'] = $fullName;
    $_SESSION['email'] = $user['email'];
    $_SESSION['last_activity'] = time();
    $_SESSION['profile_photo'] = $user['profile_photo'];
    $_SESSION['role'] = $user['role'];

    header("Location: profile.php");
    exit;
}

header("Location: login.php?error=1");
exit;
