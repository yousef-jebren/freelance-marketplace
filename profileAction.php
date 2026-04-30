<?php
session_start();
require_once "dp.php"; // make sure this is correct

/* ---------- AUTH CHECK ---------- */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Handel Upload image
 
   if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['just-image'])){
        $imageName = $user['image'] ?? null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {

        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            header("Location: profile.php?error=invalid_image");
            exit();
        }

        $imageName = uniqid() . "." . $ext;
        if (!is_dir("uploads/profiles/" . $userId)) {
            mkdir("uploads/profiles/" . $userId, 0755, true);
        }
        move_uploaded_file(
            $_FILES['image']['tmp_name'],
            "uploads/profiles/" . $userId . "/" . $imageName
        );

            $_SESSION['profile_photo'] = $imageName;
            $updateImage = "UPDATE users SET profile_photo = :photo where user_id = :userID";
            $stmt = $conn->prepare($updateImage);
            $stmt->bindvalue(':photo',$imageName);
            $stmt->bindValue(':userID',$userId);
            $stmt->execute();

          header("Location: profile.php");
          exit();

    }else{
        header("Location: profile.php?error=Invalid-Imagehello-world");
        exit();
    }



   }

/* ---------- HANDLE FORM ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save-changes'])) {

    /* ---------- BASIC DATA ---------- */
    $firstName = trim($_POST['first_name']);
    $lastName  = trim($_POST['last_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone_number']);
    $country   = trim($_POST['country']);
    $city      = trim($_POST['city']);


    /* ---------- VALIDATION ---------- */
    if (
        empty($firstName) || empty($lastName) || empty($email) ||
        empty($phone) ||  empty($country) || empty($city)
    ) {
        header("Location: profile.php?error=missing_fields");
        exit();
    }

    /* ---------- GET CURRENT USER ---------- */
    $stmt = $conn->prepare("SELECT password, role FROM users WHERE user_id = :id");
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: login.php");
        exit();
    }

    /* ---------- PASSWORD UPDATE (OPTIONAL) ---------- */
    if (!empty($_POST['password'])) {

        $currentPassword = $_POST['current_password'];
        $newPassword     = $_POST['password'];

        if ($currentPassword !== $user['password']) {
            header("Location: profile.php?error=invalid_current_password");
            exit();
        }

        if (strlen($newPassword) < 8) {
            header("Location: profile.php?error=weak_password");
            exit();
        }


        $stmt = $conn->prepare(
            "UPDATE users SET password = :password WHERE user_id = :id"
        );
        $stmt->bindValue(':password', $newPassword);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }


  

    /* ---------- PROFILE IMAGE (OPTIONAL) ---------- */


    $imageName = $user['profile_photo'] ?? null;

    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {

        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            header("Location: profile.php?error=invalid_image");
            exit();
        }

        $imageName = uniqid() . "." . $ext;
        if (!is_dir("uploads/profiles/" . $userId)) {
            mkdir("uploads/profiles/" . $userId, 0755, true);
        }
        move_uploaded_file(
            $_FILES['profile_photo']['tmp_name'],
            "uploads/profiles/" . $userId . "/" . $imageName
        );
    }



 




    /* ---------- FREELANCER EXTRA DATA ----------
    if ($user['role'] === "Freelancer") {

        $skills = isset($_POST['skills']) ? $_POST['skills'] : [];
        $skillsStr = implode(',', $skills);
        $rate = is_numeric($_POST['rate']) ? $_POST['rate'] : 0;

        $stmt = $conn->prepare(
            "UPDATE users SET skills = :skills, rate = :rate WHERE user_id = :id"
        );
        $stmt->bindValue(':skills', $skillsStr);
        $stmt->bindValue(':rate', $rate);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }



    /* ---------- UPDATE MAIN PROFILE ---------- */
    $stmt = $conn->prepare(
        "UPDATE users SET
            first_name = :first_name,
            last_name  = :last_name,
            email      = :email,
            phone_number = :phone,
            city       = :city,
            country    = :country,
            profile_photo = :photo
         WHERE user_id = :id"
    );

    $stmt->bindValue(':first_name', $firstName);
    $stmt->bindValue(':last_name', $lastName);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':phone', $phone);
    $stmt->bindValue(':city', $city);
    $stmt->bindValue(':country', $country);
    $stmt->bindValue(':photo', $imageName);
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    /* ---------- UPDATE SESSION ---------- */
    $_SESSION['first_name'] = $firstName;
    $_SESSION['last_name']  = $lastName;
    $_SESSION['email']      = $email;
    $_SESSION['phone']      = $phone;
    $_SESSION['city']       = $city;
    $_SESSION['country']    = $country;
    $_SESSION['profile_photo'] = $imageName;

    header("Location: profile.php?success=1");
    exit();
}
