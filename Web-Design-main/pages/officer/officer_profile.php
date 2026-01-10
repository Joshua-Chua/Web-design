<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();
require '../../config/db.php';

// Ensure the user is an officer
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'officer') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get officer information
$officer = [
    'name' => $_SESSION['username'] ?? 'User',
    'identical_number' => '',
    'email' => '',
    'phone_number' => '',
    'gender' => ''
];

try {
    $query = "SELECT * FROM officer WHERE user_id = '$user_id' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }
    
    if (mysqli_num_rows($result) == 1) {
        $officer = mysqli_fetch_assoc($result);
    }
    mysqli_free_result($result);
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
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
<body class = "profile-page">
    
<div class = "topbar">
    <img src = "../../assets/images/menu-icon.png" class = "menu-btn" id = "menuBtn">
    <span class = "page-title">Profile</span>

    <div class = "topbar-left breadcrumb">
        <img src = "../../assets/images/apu-logo.png" class = "top-logo" alt = "APU Logo">
        <a href = "officer_main.php" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon" alt = "Home">
        </a>

        <span class = "breadcrumb-separator">/</span>
        <a href = "officer_profile.php" class = "breadcrumb-link">Profile</a>
    </div>

    <div class = "topbar-right">
        <a href = "officer_profile.php" class = "user-link">
            <img src = "../../assets/images/user-icon.png" class = "user-icon">
        </a>
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
        <a href = "officer_monthly_report.php">Monthly Report</a>
        <a href = "officer_event.php">Events</a>
        <a href = "../../pages/student/browse_tips.php">Smart Tips</a>
        <a href = "officer_quiz.php">Quiz</a>
        <a href = "officer_forum.php">Forum</a>
        <a href = "../auth/logout.php">Logout</a>
    </div>

    <div class = "content">
        <div class = "profile-box">
            <div class = "profile-img">
                <img src = "../../assets/images/user-icon.png" alt = "User Logo">
            </div>

            <!-- Officer Info -->
            <div class = "profile-info">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($officer['name']); ?></p>
                <p><strong>Identical Number:</strong> <?php echo htmlspecialchars($officer['identical_number']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($officer['email']); ?></p>
                <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($officer['phone_number']); ?></p>
                <p><strong>Gender:</strong> <?php echo htmlspecialchars($officer['gender']); ?></p>
            </div>

            <!-- Change password button -->
            <div class = "change-password-btn">
                <a href = "change_password.php"><button>Change Password</button></a>
            </div>
        </div>
    </div>
</div>

<script>
<?php if (isset($_SESSION['error'])): ?>
    alert("Error: <?php echo addslashes($_SESSION['error']); ?>");
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

document.addEventListener("DOMContentLoaded", function() {

    /* Reset on resize */
    window.addEventListener("resize", function() {
        if (window.innerWidth > 425) {
            if (sidebar) sidebar.classList.remove("active");
            if (moreMenu) moreMenu.classList.remove("active");
        }
    });
});
</script>

<script src = '../../assets/js/main.js'></script>

</body>
</html>