<?php
session_start();
require '../../config/db.php';    

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
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
<div class="topbar">
    <img src="../../assets/images/menu-icon.png" class="menu-btn" id="menuBtn">
    <span class="page-title">Student/Registration</span>

    <div class="topbar-left">
        <img src="../../assets/images/apu-logo.png" class="top-logo" alt="APU Logo">
        
        <a href="student_main.php" class="home-btn">
            <img src="../../assets/images/home-icon.png" class="home-icon" alt="Home">
        </a>

        <span class="breadcrumb">
            <span class="breadcrumb-separator">/</span>
            <a href="student_main.php" class="breadcrumb-link">Student</a>
            <span class="breadcrumb-separator">/</span>
            <a href="event_registration.php" class="breadcrumb-link">Registration</a>
        </span>
    </div>

    <div class="topbar-right">

        <img src="../../assets/images/more-icon.png" class="more-btn" id="moreBtn">
        <div class="more-menu" id="moreMenu">
            <a href="<?php echo $profile_link; ?>">Profile</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
    </div>
</div>

<div class = "dashboard">
    <div class="sidebar">
        <a href="student_main.php">Main Menu</a>
        
        <a href="javascript:void(0);" class="dropdown-toggle active" onclick="toggleDropdown('eventMenu', this)">
            Events <span class="arrow">&#9662;</span>
        </a>
        <div id="eventMenu" class="dropdown-container" style="display: flex; flex-direction: column; padding-left: 20px; background: rgba(0,0,0,0.05);">
            <a href="event_registration.php" style="font-size: 0.9em;">Event Registration</a>
            <a href="upcoming_event.php" style="font-size: 0.9em; font-weight: bold; color: #004684;">Upcoming Event</a>
        </div>
        
        <a href="browse_tips.php">Smart Tips</a>
        <a href="student_quiz.php">Quiz</a>
        <a href="student_achievement.php">Achievement</a>
        <a href="student_forum.php">Forum</a>
        <a href="../auth/logout.php">Logout</a>
    </div>
    

    
    <div class = "content event-page">
        <div class = "Event-header">
            <h2>Upcoming Events</h2>
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