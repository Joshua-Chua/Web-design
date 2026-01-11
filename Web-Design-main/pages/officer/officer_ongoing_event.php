<?php
session_start();
require '../../config/db.php';    

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'officer') {
    header("Location: ../auth/login.php");
    exit();
}

$query = "SELECT p.*, a.officer_id 
          FROM proposal p 
          JOIN approval a ON p.proposal_id = a.proposal_id 
          WHERE p.status = 'Approved' 
          ORDER BY p.date ASC";
          
$result = mysqli_query($conn, $query);

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
            <a href = "officer_ongoing_event.php" class = "breadcrumb-link">Ongoing Event</a>
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
        <a href="officer_create_event.php" class = "sub-link">Create Event</a>
        <a href="officer_ongoing_event.php"class = "sub-link active">Ongoing Events</a>
        </div>

        <a href = "#">Smart Tips</a>
        <a href = "officer_quiz.php">Quiz</a>
        <a href = "officer_forum.php">Forum</a>
        <a href = "../auth/logout.php">Logout</a>
    </div>
    
    <div class = "content event-page">
        <div class = "Event-header">
            <h2>Ongoing Events</h2>
        </div>
        
        <div class="ongoing-list">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="ongoing-card">
                    <div class="card-image">
                        <?php if (!empty($row['picture'])): ?>
                            <img src="../../assets/images/proposals/<?php echo $row['picture']; ?>" alt="Event Poster">
                        <?php else: ?>
                            <div class="no-pic">No Poster Available</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-details">
                        <span class="status-tag">ACTIVE</span>
                        <h3><?php echo htmlspecialchars($row['event_name']); ?></h3>
                        <p class="event-meta">
                            <strong>📍 Location:</strong> <?php echo htmlspecialchars($row['location']); ?><br>
                            <strong>📅 Date:</strong> <?php echo $row['date']; ?> at <?php echo $row['time']; ?>
                        </p>
                        <p class="event-desc"><?php echo substr(htmlspecialchars($row['event_description']), 0, 100); ?>...</p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-data">There are currently no ongoing events.</div>
        <?php endif; ?>
    </div>
</div>
        
</body>
</html>