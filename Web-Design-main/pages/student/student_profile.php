<?php
session_start();
require '../../config/db.php';

// Ensure the user is a student
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get student information
$query = "SELECT * FROM student WHERE user_id = '$user_id' LIMIT 1";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) == 1) {
    $student = mysqli_fetch_assoc($result);
} else {
    // Fallback when data not found
    $student = [
        'name' => $_SESSION['username'],
        'identical_number' => '',
        'email' => '',
        'phone_number' => '',
        'gender' => ''
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APU Energy Sustainability - Student Profile</title>
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
        <a href = "student_main.php" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon" alt = "Home">
        </a>

        <span class = "breadcrumb-separator">/</span>
        <a href = "student_profile.php" class = "breadcrumb-link">Profile</a>
    </div>

    <div class = "topbar-right">
        <a href = "student_profile.php" class = "user-link">
            <img src = "../../assets/images/user-icon.png" class = "user-icon">
        </a>
        <img src = "../../assets/images/more-icon.png" class = "more-btn" id = "moreBtn">
        <div class = "more-menu" id = "moreMenu">
            <a href = "student_profile.php">Profile</a>
            <a href = "../auth/logout.php">Logout</a>
        </div>
    </div>
</div>

<div class = "dashboard">
    <div class = "sidebar">
        <a href = "student_main.php">Main Menu</a>
        <!-- <a href = "view_carbon.php">Carbon Emission</a> -->
        
        <a href = "javascript:void(0);" class="dropdown-toggle" onclick="toggleDropdown('eventMenu', this)">
            Events <span class="arrow">&#9662;</span>
        </a>
        <div id="eventMenu" class="dropdown-container" style="display: none; flex-direction: column; padding-left: 20px; background: rgba(0,0,0,0.05);">
            <a href="#" style="font-size: 0.9em;">Event Registration</a>
            <a href="#" style="font-size: 0.9em;">Upcoming Event</a>
        </div>

        <a href = "browse_tips.php">Smart Tips</a>
        <a href = "student_quiz.php">Quiz</a>
        <a href = "#">Achievement</a>
        <a href = "#">Forum</a>
        <a href = "../auth/logout.php">Logout</a>
    </div>

    <div class = "content">
        <div class = "profile-box">
            <div class = "profile-img">
                <img src = "../../assets/images/user-icon.png" alt = "User Logo">
            </div>

            <!-- Student Info -->
            <div class = "profile-info">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($student['name']); ?></p>
                <p><strong>Identical Number:</strong> <?php echo htmlspecialchars($student['identical_number']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($student['phone_number']); ?></p>
                <p><strong>Gender:</strong> <?php echo htmlspecialchars($student['gender']); ?></p>
            </div>

            <!-- Change password button -->
            <div class = "change-password-btn">
                <a href = "change_password.php"><button>Change Password</button></a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {

    const menuBtn = document.getElementById("menuBtn");
    const sidebar = document.querySelector(".sidebar");

    const moreBtn = document.getElementById("moreBtn");
    const moreMenu = document.getElementById("moreMenu");

    /* Toggle sidebar */
    if (menuBtn && sidebar) {
        menuBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            sidebar.classList.toggle("active");
        });
    }

    /* Toggle more button */
    if (moreBtn && moreMenu) {
        moreBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            moreMenu.classList.toggle("active");
        });
    }

    /* Auto-close when clicking outside */
    document.addEventListener("click", function(e) {
        
        /*  Close sidebar */
        if (
            sidebar &&
            sidebar.classList.contains("active") &&
            !sidebar.contains(e.target) &&
            e.target !== menuBtn
        ){
            sidebar.classList.remove("active");
        }

        /* Close more menu */
        if (
            moreMenu &&
            moreMenu.classList.contains("active") &&
            !moreMenu.contains(e.target) &&
            e.target !== moreBtn
        ){
            moreMenu.classList.remove("active")
        }
    });

    /* Sidebar Dropdown Toggle */
    window.toggleDropdown = function(id, el) {
        var dropdown = document.getElementById(id);
        if (dropdown.style.display === "none" || dropdown.style.display === "") {
            dropdown.style.display = "flex";
            if(el.querySelector('.arrow')) el.querySelector('.arrow').innerHTML = '&#9652;'; // Up arrow
        } else {
            dropdown.style.display = "none";
                if(el.querySelector('.arrow')) el.querySelector('.arrow').innerHTML = '&#9662;'; // Down arrow
        }
    }

    /* Reset on resize */
    window.addEventListener("resize", function() {
        if (window.innerWidth > 425) {
            if (sidebar) sidebar.classList.remove("active");
            if (moreMenu) moreMenu.classList.remove("active");
        }
    });
});
</script>

</body>
</html>
