<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APU Energy Sustainability</title>

    <!-- CSS -->
    <link rel = "stylesheet" href = "../../assets/css/style.css">
</head>
<body>
    
<div class = "login-wrapper">
    <div class = "logo">
        <img src = "../../assets/images/apu-logo.png" alt = "APU logo">
    </div>

    <div class = "login-container">

        <?php
        if (isset($_GET['error'])) {
            if ($_GET['error'] === 'wrongpassword') {
                echo "<p class = 'error-msg'>Incorrect password</p>";
            } elseif ($_GET['error'] === 'nouser') {
                echo "<p class = 'error-msg'>User not found</p>";
            } elseif ($_GET['error'] === 'tooManyAttempts') {
                echo "<p class = 'error-msg'>You have exceeded 3 attempts. Please try again later.</p>";
            }
        } elseif (isset($_GET['success'])) {
            if ($_GET['success'] === 'passwordChanged') {
                echo "<p class = 'success-msg'>Password successfully changed! You can now login.</p>";
            }
        }
        ?>

        <form method = "POST" action = "login_process.php">
            <div class = "form-group">
                <label>Username</label>
                <input type = "text" name = "username" placeholder = "Username" required>
            </div>

            <div class = "form-group password-group">
                <label>Password</label>
                <div class = "password-wrapper">
                    <input type = "password" id = "password" name = "password" placeholder = "Password" required>
                    <img src = "../../assets/images/eye-slash.png" alt = "Toggle Password" class = "toggle-password" id = "togglePassword">
                </div>
            </div>

            <div class = "forgot-password">
                <a href = "forgot_password.php">Forgot Password?</a>
            </div>

            <button type = "submit">Login</button>

        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const passwordInput = document.getElementById("password");
    const toggleIcon = document.getElementById("togglePassword");

    toggleIcon.addEventListener("click", function() {
        if (passwordInput.type === "password") {
            passwordInput.type = "text";
            toggleIcon.src = "../../assets/images/eye.png";
        } else {
            passwordInput.type = "password";
            toggleIcon.src = "../../assets/images/eye-slash.png";
        }
    });
});
</script>

</body>
</html>