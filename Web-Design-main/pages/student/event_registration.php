<?php
session_start();
require '../../config/db.php';

// 1. Security & Role Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

// Variables for Layout Consistency
$student_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$profile_link = "student_profile.php";
$msg = "";

// 2. Handle Registration Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_event'])) {
    $event_id = mysqli_real_escape_string($conn, $_POST['event_id']); 

    // Check if student is already registered for this event
    $check_sql = "SELECT * FROM registration WHERE student_id = '$student_id' AND event_id = '$event_id'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) == 0) {
        $reg_sql = "INSERT INTO registration (student_id, event_id, attendance) VALUES ('$student_id', '$event_id', 0)";
        if (mysqli_query($conn, $reg_sql)) {
            $msg = "success";
        } else {
            $msg = "error";
        }
    } else {
        $msg = "already_registered";
    }
}

// 3. FIXED QUERY: Joining with 'proposal' to access the 'date' column for sorting
$query = "SELECT e.*, p.date, p.time, p.location, p.event_name, p.picture 
          FROM event e
          INNER JOIN proposal p ON e.proposal_id = p.proposal_id 
          ORDER BY p.date ASC";
          
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Registration - APU Energy</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_main.css">
    <link rel="stylesheet" href="../../assets/css/student/student_event.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_profile.css">
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
        <a href="<?php echo $profile_link; ?>" class="user-link">
            <img src="../../assets/images/user-icon.png" class="user-icon">
        </a>

        <img src="../../assets/images/more-icon.png" class="more-btn" id="moreBtn">
        <div class="more-menu" id="moreMenu">
            <a href="<?php echo $profile_link; ?>">Profile</a>
            <a href="../auth/logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="dashboard">
    <div class="sidebar">
        <a href="student_main.php">Main Menu</a>
        
        <a href="javascript:void(0);" class="dropdown-toggle active" onclick="toggleDropdown('eventMenu', this)">
            Events <span class="arrow">&#9662;</span>
        </a>
        <div id="eventMenu" class="dropdown-container" style="display: flex; flex-direction: column; padding-left: 20px; background: rgba(0,0,0,0.05);">
            <a href="event_registration.php" style="font-size: 0.9em; font-weight: bold; color: #004684;">Event Registration</a>
            <a href="upcoming_event.php" style="font-size: 0.9em;">Upcoming Event</a>
        </div>
        
        <a href="browse_tips.php">Smart Tips</a>
        <a href="student_quiz.php">Quiz</a>
        <a href="student_achievement.php">Achievement</a>
        <a href="student_forum.php">Forum</a>
        <a href="../auth/logout.php">Logout</a>
    </div>

    <div class="content event-page">
        <div class="Event-header">
            <h2>Available Sustainability Events</h2>
        </div>

        <div class="registration-container" style="margin-top: 20px;">
            <?php if($msg == 'success'): ?>
                <div id="regAlert" class="alert-success" style="padding: 15px; background: #d4edda; color: #155724; border-radius: 8px; margin-bottom: 20px;">
                    ✅ Registration successful!
                </div>
            <?php elseif($msg == 'already_registered'): ?>
                <div id="regAlert" class="alert-warning" style="padding: 15px; background: #fff3cd; color: #856404; border-radius: 8px; margin-bottom: 20px;">
                    ℹ️ You are already registered for this event.
                </div>
            <?php endif; ?>

            <div class="event-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px;">
                <?php if(mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <div class="event-card" style="background: #fff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); overflow: hidden;">
                            <div class="event-img">
                                <?php if(!empty($row['picture'])): ?>
                                    <img src="../../assets/images/proposals/<?php echo $row['picture']; ?>" style="width: 100%; height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 100%; height: 200px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">No Image Available</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="event-details" style="padding: 20px;">
                                <h3 style="margin-bottom: 10px; color: #004684;"><?php echo htmlspecialchars($row['event_name']); ?></h3>
                                <p style="font-size: 0.9em; color: #666; margin-bottom: 5px;"><strong>📍 Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
                                <p style="font-size: 0.9em; color: #666; margin-bottom: 15px;"><strong>📅 Date:</strong> <?php echo date('M d, Y', strtotime($row['date'])); ?> | <?php echo $row['time']; ?></p>
                                
                                <form method="POST">
                                    <input type="hidden" name="event_id" value="<?php echo $row['event_id']; ?>">
                                    <button type="submit" name="register_event" class="reg-btn" style="width: 100%; padding: 12px; background: #004684; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">Register Now</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #999; grid-column: 1/-1;">No approved events available right now.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() {
    const alerts = document.querySelectorAll('.alert-success, .alert-warning');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = "opacity 0.5s ease";
            alert.style.opacity = "0";
            setTimeout(() => alert.remove(), 500); 
        }, 5000); 
    });
};

function toggleDropdown(id, element) {
    var menu = document.getElementById(id);
    var arrow = element.querySelector('.arrow');
    if (menu.style.display === "none" || menu.style.display === "") {
        menu.style.display = "flex";
        arrow.innerHTML = "&#9652;";
    } else {
        menu.style.display = "none";
        arrow.innerHTML = "&#9662;";
    }
}

document.getElementById('moreBtn').onclick = function() {
    var menu = document.getElementById('moreMenu');
    menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
};
</script>

</body>
</html>
