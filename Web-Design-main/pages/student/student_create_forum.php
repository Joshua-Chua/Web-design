<?php
session_start();
require '../../config/db.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Get the actual student_id from the student table
$student_query = "SELECT student_id FROM student WHERE user_id = '$user_id' LIMIT 1";
$student_res = mysqli_query($conn, $student_query);
$student_data = mysqli_fetch_assoc($student_res);
$actual_student_id = $student_data['student_id'];

// 2. Only process if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_post'])) {
    
    // Assign variables from POST data
    $subject = mysqli_real_escape_string($conn, $_POST['post_subject']);
    $details = mysqli_real_escape_string($conn, $_POST['post_details']);
    $picture = ""; 

    // Handle Image Upload
    if (!empty($_FILES['picture']['name'])) {
        $target_dir = "../../assets/uploads/posts/";
        $file_name = time() . "_" . basename($_FILES["picture"]["name"]);
        if (move_uploaded_file($_FILES["picture"]["tmp_name"], $target_dir . $file_name)) {
            $picture = $file_name;
        }
    }

    // 3. IMPORTANT: Only insert student_id. Leave officer_id and admin_id out of the query.
    // This prevents the Foreign Key constraint error for 'officer_id'.
    $sql = "INSERT INTO post (post_subject, post_details, picture, student_id) 
            VALUES ('$subject', '$details', '$picture', '$actual_student_id')";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: student_forum.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Forum Post - APU Energy</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_main.css">
    <link rel="stylesheet" href="../../assets/css/student/student_create_forum.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_profile.css">
</head>
<body>

<div class="topbar">
    <img src="../../assets/images/menu-icon.png" class="menu-btn" id="menuBtn">
    <span class="page-title">Forum / Create Post</span>

    <div class="topbar-left">
        <img src="../../assets/images/apu-logo.png" class="top-logo">
        <a href="student_main.php" class="home-btn">
            <img src="../../assets/images/home-icon.png" class="home-icon">
        </a>
        <span class="breadcrumb">
            <span class="breadcrumb-separator">/</span>
            <a href="student_forum.php" class="breadcrumb-link">Forum</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">New Post</span>
        </span>
    </div>

    <div class="topbar-right">
        <img src="../../assets/images/more-icon.png" class="more-btn" id="moreBtn">
        <div class="more-menu" id="moreMenu">
            <a href="student_profile.php">Profile</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="dashboard">
    <div class="sidebar">
        <a href="student_main.php">Main Menu</a>
        <a href="javascript:void(0);" onclick="toggleDropdown('eventMenu', this)">Events</a>
        <div id="eventMenu" class="dropdown-container" style="display: none; padding-left: 20px;">
            <a href="#">Registration</a>
            <a href="#">Upcoming</a>
        </div>
        <a href="browse_tips.php">Smart Tips</a>
        <a href="student_quiz.php">Quiz</a>
        <a href="student_achievement.php">Achievement</a>
        <a href="student_forum.php" class="active">Forum</a>
        <a href="../auth/logout.php">Logout</a>
    </div>

    <div class="content">
        <div class="create-post-box">
            <h2>Start a New Discussion</h2>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Post Subject</label>
                    <input type="text" name="post_subject" placeholder="Enter a brief title..." required maxlength="500">
                </div>
                
                <div class="form-group">
                    <label>Description / Details</label>
                    <textarea name="post_details" rows="8" placeholder="Provide more details about your topic..." required maxlength="500"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Attachment (Optional)</label>
                    <input type="file" name="picture" class="file-input">
                </div>
                
                <div class="button-group">
                    <button type="submit" name="submit_post" class="btn-publish">Publish Post</button>
                    <a href="student_forum.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Standard Dashboard Script for Sidebar/Menu
    document.getElementById("menuBtn").onclick = () => document.querySelector(".sidebar").classList.toggle("active");
    document.getElementById("moreBtn").onclick = () => document.getElementById("moreMenu").classList.toggle("active");

    function toggleDropdown(id, el) {
        var dropdown = document.getElementById(id);
        dropdown.style.display = (dropdown.style.display === "none") ? "flex" : "none";
    }
</script>

</body>
</html>