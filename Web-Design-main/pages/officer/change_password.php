<?php
session_start();
require '../../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'officer') {
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APU Energy Sustainability</title>
    <link rel = "stylesheet" href = "../../assets/css/style.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_main.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_profile.css">
</head>
<body>

<div class = "topbar">

    <!-- Mobile Menu Button -->
    <img src = "../../assets/images/menu-icon.png" class = "menu-btn" id = "menuBtn">
    <span class = "page-title">Profile</span>

    <div class = "topbar-left">
        <img src = "../../assets/images/apu-logo.png" class = "top-logo">

        <!-- Home Button -->
        <a href = "officer_main.php" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon">
        </a>

        <span class = "breadcrumb">
            <span class = "breadcrumb-separator">/</span>
            <a href = "officer_profile.php" class = "breadcrumb-link">Profile</a>
            <span class = "breadcrumb-separator">/</span>
            <a href = "change_password.php" class = "breadcrumb-link">Change Password</a>
        </span>
    </div>

    <div class = "topbar-right">
        <img src = "../../assets/images/more-icon.png" class = "more-btn" id = "moreBtn">

        <div class = "more-menu" id = "moreMenu">
            <a href = "officer_profile.php">Profile</a>
            <a href = "../auth/logout.php">Logout</a>
        </div>
    </div>
</div>

<div class = "dashboard">
    <div class = "sidebar">
        <a href = "officer_main.php" class = "active">Main Menu</a>
        <a href = "#">Monthly Report</a>
        <a href = "#">Events</a>
        <a href = "#">Smart Tips</a>
        <a href = "officer_quiz.php">Quiz</a>
        <a href = "#">Forum</a>
        <a href = "../auth/logout.php">Logout</a>
    </div>

    <div class = "content change-password-page">
        <div class = "change-box">
            <a href = "officer_main.php" class = "close-btn">
                <img src = "../../assets/images/close-icon.png" alt = "Close">
            </a>

            <img src = "../../assets/images/shield-icon.png" class = "shield-icon">

            <h2>Change Password</h2>

            <form method = "POST" action = "change_password_process.php">

                <div class = "form-row password-wrapper">
                    <input type = "password" id = "current_password" name = "current_password" placeholder = "Current Password" required>
                    <img src = "../../assets/images/eye-slash.png" alt = "Toggle Password" class = "toggle-password" id = "toggleCurrentPassword">
                </div>

                <div class = "form-row password-wrapper">
                    <input type = "password" id = "new_password" name = "new_password" placeholder = "New Password" required>
                    <img src = "../../assets/images/eye-slash.png" alt = "Toggle Password" class = "toggle-password" id = "toggleNewPassword">
                </div>

                <div class = "form-row password-wrapper">
                    <input type = "password" id = "confirm_password" name = "confirm_password" placeholder = "Confirm Password" required>
                    <img src = "../../assets/images/eye-slash.png" alt = "Toggle Password" class = "toggle-password" id = "toggleConfirmPassword">
                </div>

                <button type = "submit" class = "change-btn">Change Password</button>

            </form>

        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const togglePassword = (inputId, toggleId) => {
        const input = document.getElementById(inputId);
        const toggle = document.getElementById(toggleId);
        if (!input || !toggle) return;

        toggle.addEventListener("click", function() {
            if (input.type === "password") {
                input.type = "text";
                toggle.src = "../../assets/images/eye.png";
            } else {
                input.type = "password";
                toggle.src = "../../assets/images/eye-slash.png";
            }
        });
    };

    togglePassword("current_password", "toggleCurrentPassword");
    togglePassword("new_password", "toggleNewPassword");
    togglePassword("confirm_password", "toggleConfirmPassword");

    const popupOkBtn = document.getElementById("popupOkBtn");
    const popupOverlay = document.getElementById("popupOverlay");

    if (popupOkBtn && popupOverlay) {
        popupOkBtn.addEventListener("click", function() {
            const type = popupOkBtn.getAttribute("data-type");
            if (type === "success") {
                window.location.href = "officer_main.php";
            } else {
                popupOverlay.style.display = "none";
            }
        });
    }
});
</script>

<script src = '../../assets/js/main.js'></script>

<?php if (isset($_SESSION['change_success'])): ?>
<div class = "popup-overlay" id = "popupOverlay">
    <div class = "popup-box">
        <h2>Password changed successfully</h2>
        <p>Your password has been changed.<br>Please use your new password the next time you log in.</p>
        <button id = "popupOkBtn" data-type = "success">Okay</button>
    </div>
</div>
<?php unset($_SESSION['change_success']); endif; ?>

<?php if (isset($_SESSION['change_error'])): ?>
<div class = "popup-overlay" id = "popupOverlay">
    <div class = "popup-box">
        <h2>Error</h2>
        <p><?php echo htmlspecialchars($_SESSION['change_error']); ?></p>
        <button id = "popupOkBtn" data-type = "error">Okay</button>
    </div>
</div>
<?php unset($_SESSION['change_error']); endif; ?>

</body>
</html>