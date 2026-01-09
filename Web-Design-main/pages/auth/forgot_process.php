<?php
session_start();
require '../../config/db.php';

// Initialize attempts
if (!isset($_SESSION['forgot_attempts'])) {
    $_SESSION['forgot_attempts'] = 0;
}

$username = $_POST['username'];
$id = $_POST['identical_number'];

// Check attempt limit first
if ($_SESSION['forgot_attempts'] >= 3) {
    $_SESSION['forgot_attempts'] = 0;
    header("Location: login.php?error=tooManyAttempts");
    exit();
}

// Get user
$query = "SELECT * FROM user WHERE username = '$username' LIMIT 1";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 1) {
    $user = mysqli_fetch_assoc($result);
    $user_id = $user['user_id'];
    $role = $user['role'];

    // Verify Identical Number
    if ($role == 'student') {
        $check = "SELECT * FROM student WHERE user_id = $user_id AND identical_number = '$id'";
    } elseif ($role == 'admin') {
        $check = "SELECT * FROM admin WHERE user_id = $user_id AND identical_number = '$id'";
    } else {
        $check = "SELECT * FROM officer WHERE user_id = $user_id AND identical_number = '$id'";
    }

    $verify = mysqli_query($conn, $check);

    if (mysqli_num_rows($verify) == 1) {
        // Success then reset counter
        $_SESSION['forgot_attempts'] = 0;
        $_SESSION['reset_user_id'] = $user_id;
        // Go to reset page
        header("Location: reset_password.php");
        exit();
    } else {
        // Failed verification
        $_SESSION['forgot_attempts'] += 1;
        header("Location: forgot_password.php?error=invalidID");
        exit();
    }
} else {
    // Username not found
    $_SESSION['forgot_attempts'] += 1;
    header("Location: forgot_password.php?error=nouser");
    exit();
}