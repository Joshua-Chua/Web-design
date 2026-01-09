<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'officer') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $stmt = $conn->prepare("SELECT password FROM user WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();

    if (!password_verify($current_password, $hashed_password)) {
        $_SESSION['change_error'] = "Incorrect current password.";
        header("Location: change_password.php");
        exit();
    }

    if ($new_password !== $confirm_password) {
        $_SESSION['change_error'] = "New password and confirm password do not match.";
        header("Location: change_password.php");
        exit();
    }

    $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE user SET password = ? WHERE user_id = ?");
    $stmt->bind_param("si", $new_hashed, $user_id);
    if ($stmt->execute()) {
        $_SESSION['change_success'] = true;
    } else {
        $_SESSION['change_error'] = "Something went wrong. Please try again.";
    }
    $stmt->close();

    header("Location: change_password.php");
    exit();
}
?>