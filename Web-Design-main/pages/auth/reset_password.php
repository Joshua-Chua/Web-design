<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APU Energy Sustainability</title>
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
            $error = $_GET['error'];
            if ($error === 'nomatch') {
                echo "<p class = 'error-msg'>Passwords do not match.</p>";
            } elseif ($error === 'updateFail') {
                echo "<p class = 'error-msg'>Failed to update password. Please try again.</p>";
            }
        }
        ?>
        
        <form method = "POST" action = "reset_process.php">

            <div class = "form-group password-group">
                <label>New Password</label>
                <div class = "password-wrapper">
                    <input type = "password" id = "new_password" name = "new_password" placeholder = "New Password" required>
                    <img src = "../../assets/images/eye-slash.png" alt = "Toggle Password" class = "toggle-password" id = "toggleNewPassword">
                </div>
            </div>

            <div class = "form-group password-group">
                <label>Confirm Password</label>
                <div class = "password-wrapper">
                    <input type = "password" id = "confirm_password" name = "confirm_password" placeholder = "Confirm Password" required>
                    <img src = "../../assets/images/eye-slash.png" alt = "Toggle Password" class = "toggle-password" id = "toggleConfirmPassword">
                </div>
            </div>

            <button type = "submit">Reset Password</button>

        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const newPassword = document.getElementById("new_password");
    const toggleNew = document.getElementById("toggleNewPassword");

    toggleNew.addEventListener("click", function() {
        if (newPassword.type === "password") {
            newPassword.type = "text";
            toggleNew.src = "../../assets/images/eye.png";
        } else {
            newPassword.type = "password";
            toggleNew.src = "../../assets/images/eye-slash.png";
        }
    });

    const confirmPassword = document.getElementById("confirm_password");
    const toggleConfirm = document.getElementById("toggleConfirmPassword");

    toggleConfirm.addEventListener("click", function() {
        if (confirmPassword.type === "password") {
            confirmPassword.type = "text";
            toggleConfirm.src = "../../assets/images/eye.png";
        } else {
            confirmPassword.type = "password";
            toggleConfirm.src = "../../assets/images/eye-slash.png";
        }
    });
});
</script>

</body>
</html>