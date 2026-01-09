<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

if ($_SESSION['login_attempts'] >= 3) {
    $_SESSION['login_attempts'] = 0;
    header("Location: login.php?error=tooManyAttempts");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM user WHERE username = '$username' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            // Rset counter once success
            $_SESSION['login_attempts'] = 0;

            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            switch($user['role']) {
                case 'student':
                    header("Location: ../student/student_main.php");
                    exit();
                case 'admin':
                    header("Location: ../admin/admin_main.php");
                    exit();
                case 'officer':
                    header("Location: ../officer/officer_main.php");
                    exit();
                default:
                header("Location: login.php?error=roleerror");
                exit();
            }

        } else {
            // Wrong password
            $_SESSION['login_attempts'] += 1;
            header("Location: login.php?error=wrongpassword");
            exit();
        }
    } else {
        // Username not found
        $_SESSION['login_attempts'] += 1;
        header("Location: login.php?error=nouser");
        exit();
    }
} else {
    // Invalid Request
    header("Location: login.php");
    exit();
}
?>