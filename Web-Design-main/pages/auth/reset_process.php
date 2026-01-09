<?php
session_start();
require '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['reset_user_id'])) {
        header("Location: login.php");
        exit();
    }

    $user_id = $_SESSION['reset_user_id'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        header("Location: reset_password.php?error=nomatch");
        exit();
    }

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $update = "UPDATE user SET password = '$hashed_password' WHERE user_id = $user_id";
    if (mysqli_query($conn, $update)) {
        // Update success
        unset($_SESSION['reset_user_id']);
        // Back to login page
        header("Location: login.php?success=passwordChanged");
    } else {
        // Update error
        header("Location: reset_password.php?error=updateFail");
        exit();
    }
}
?>