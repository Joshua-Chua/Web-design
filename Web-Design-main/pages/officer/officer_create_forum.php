<?php
session_start();
require '../../config/db.php';

// 1. Security Check - Ensure only officers can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'officer') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Get the actual officer_id from the officer table using the session user_id
$officer_query = "SELECT officer_id FROM officer WHERE user_id = '$user_id' LIMIT 1";
$officer_res = mysqli_query($conn, $officer_query);
$officer_data = mysqli_fetch_assoc($officer_res);
$actual_officer_id = $officer_data['officer_id'];

// 3. Process the form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_post'])) {
    
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

    // 4. FIX: We only insert the officer_id
    // Leaving student_id and admin_id out allows them to be NULL in the database,
    // which satisfies the foreign key constraints.
    
    $sql = "INSERT INTO post (post_subject, post_details, picture, officer_id) 
            VALUES ('$subject', '$details', '$picture', '$actual_officer_id')";
    
    if (mysqli_query($conn, $sql)) {
        header("Location: officer_forum.php"); // Successful redirect
        exit();
    } else {
        // This will catch any remaining database constraint issues
        echo "Database Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer - Create Forum</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_main.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_create_forum.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_profile.css">
</head>
<body>

<div class="topbar">
    <img src="../../assets/images/menu-icon.png" class="menu-btn" id="menuBtn">
    <span class="page-title">Officer / Create Post</span>

    <div class="topbar-left">
        <img src="../../assets/images/apu-logo.png" class="top-logo">
        <a href="officer_main.php" class="home-btn">
            <img src="../../assets/images/home-icon.png" class="home-icon">
        </a>
        <span class="breadcrumb">
            <span class="breadcrumb-separator">/</span>
            <a href="officer_forum.php" class="breadcrumb-link">Forum</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">New Announcement</span>
        </span>
    </div>

    <div class="topbar-right">
        <img src="../../assets/images/more-icon.png" class="more-btn" id="moreBtn">
        <div class="more-menu" id="moreMenu">
            <a href="officer_profile.php">Profile</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="dashboard">
    <div class="sidebar">
        <a href="officer_main.php">Main Menu</a>
        <a href="#">Monthly Report</a>
        <a href="#">Events</a>
        <a href="share_tips.php">Smart Tips</a>
        <a href="officer_quiz.php">Quiz</a>
        <a href="officer_forum.php" class="active">Forum</a>
        <a href="../auth/logout.php">Logout</a>
    </div>

    <div class="content">
        <div class="create-post-box">
            <h2>Post an Official Announcement</h2>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Post Subject</label>
                    <input type="text" name="post_subject" placeholder="Enter a professional title..." required maxlength="500">
                </div>
                
                <div class="form-group">
                    <label>Description / Details</label>
                    <textarea name="post_details" rows="8" placeholder="Provide full announcement details..." required maxlength="500"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Attachment (Optional)</label>
                    <input type="file" name="picture" class="file-input">
                </div>
                
                <div class="button-group">
                    <button type="submit" name="submit_post" class="btn-publish">Publish Announcement</button>
                    <a href="officer_forum.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Handles sidebar and more-menu toggles
    document.getElementById("menuBtn").onclick = () => document.querySelector(".sidebar").classList.toggle("active");
    document.getElementById("moreBtn").onclick = () => document.getElementById("moreMenu").classList.toggle("active");
</script>

</body>
</html>