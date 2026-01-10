<?php
session_start();
require '../../config/db.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'officer') {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_POST['submit_proposal'])) {
    $event_name = mysqli_real_escape_string($conn, $_POST['event_name']);
    $description = mysqli_real_escape_string($conn, $_POST['event_description']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $limit = (int)$_POST['participant_limit'];
    $officer_id = $_SESSION['user_id']; 

    // Handle Image Upload
    $picture_name = "";
    if (!empty($_FILES['picture']['name'])) {
        $target_dir = "../../assets/images/proposals/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $picture_name = time() . "_" . basename($_FILES["picture"]["name"]);
        $target_file = $target_dir . $picture_name;
        move_uploaded_file($_FILES["picture"]["tmp_name"], $target_file);
    }

    // Status is 'pending' by default because of your SQL ALTER TABLE
    $sql = "INSERT INTO proposal (event_name, event_description, date, time, location, officer_id, participant_limit, picture) 
            VALUES ('$event_name', '$description', '$date', '$time', '$location', '$officer_id', '$limit', '$picture_name')";

    if (mysqli_query($conn, $sql)) {
        // Redirect to same page with a success parameter
        header("Location: officer_create_event.php?msg=pending");
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
    <title>APU Energy Sustainability </title>
    <link rel = "stylesheet" href = "../../assets/css/style.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_main.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_event.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_profile.css">
    
</head>
<body>
<div class = "topbar">
    <img src = "../../assets/images/menu-icon.png" class = "menu-btn" id = "menuBtn">
    <span class = "page-title">Event/My Event</span>

    <div class = "topbar-left">
        <img src = "../../assets/images/apu-logo.png" class = "top-logo">

        <a href = "officer_main.php" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon">
        </a>

        <span class = "breadcrumb">
            <span class = "breadcrumb-separator">/</span>
            <a href = "officer_event.php" class = "breadcrumb-link">Event</a>
            <span class = "breadcrumb-separator">/</span>
            <a href = "officer_create_event.php" class = "breadcrumb-link">Create Event</a>
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
        <a href = "officer_main.php">Main Menu</a>
        <a href = "#">Monthly Report</a>

        <div class = "sidebar-group">
        <a href = "officer_event.php" class = "active">Events</a>
        <a href = "officer_event.php" class = "sub-link ">My Event</a>
        <a href="officer_create_event.php" class = "sub-link active">Create Event</a>
        <a href="officer_ongoing_event.php"class = "sub-link">Ongoing Events</a>
        </div>

        <a href = "#">Smart Tips</a>
        <a href = "officer_quiz.php">Quiz</a>
        <a href = "officer_forum.php">Forum</a>
        <a href = "../auth/logout.php">Logout</a>
        
    </div>

    <div class="content event-page">
    <div class="Event-header">
        <h2>Create Event</h2>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'pending'): ?>
    <div class="approval-alert">
        <div class="alert-icon">⏳</div>
        <div class="alert-content">
            <strong>Submission Successful!</strong>
            <p>Your proposal has been sent. Please wait for an administrator to approve your event.</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="form-container">
        <form action="" method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label for="event_name">Event Name</label>
                <input type="text" id="event_name" name="event_name" placeholder="Enter event title" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" required>
                </div>
                <div class="form-group">
                    <label for="time">Time</label>
                    <input type="time" id="time" name="time" required>
                </div>
            </div>

            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location" placeholder="e.g., Auditorium 1, Level 8" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="participant_limit">Participant Limit</label>
                    <input type="number" id="participant_limit" name="participant_limit" min="1" placeholder="Max attendees" required>
                </div>
                <div class="form-group">
                    <label for="picture">Event Banner / Poster</label>
                    <input type="file" id="picture" name="picture" accept="image/*">
                </div>
            </div>

            <div class="form-group">
                <label for="event_description">Event Description</label>
                <textarea id="event_description" name="event_description" rows="5" placeholder="Describe the purpose and activities of the event..." required></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" name="submit_proposal" class="submit-btn">Submit</button>
                <button type="reset" class="reset-btn">Clear</button>
            </div>
        </form>
    </div>
</div>
    
</body>
</html>