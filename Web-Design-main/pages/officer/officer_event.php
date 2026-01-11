<?php
session_start();
require '../../config/db.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'officer') {
    header("Location: ../auth/login.php");
    exit();
}

$officer_id = $_SESSION['user_id'];

// Fetch proposals for this specific officer
$query = "SELECT * FROM proposal WHERE officer_id = '$officer_id' ORDER BY proposal_id DESC";
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
            <a href = "officer_event.php" class = "breadcrumb-link">My Event</a>
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

<div class="dashboard">
    <div class = "sidebar">
        <a href = "officer_main.php">Main Menu</a>
        <a href = "#">Monthly Report</a>

            <div class = "sidebar-group">
                <a href = "officer_event.php" class = "active">Events</a>
                <a href = "officer_event.php" class = "sub-link active">My Event</a>
                <a href="officer_create_event.php" class = "sub-link">Create Event</a>
                <a href="officer_ongoing_event.php"class = "sub-link">Ongoing Events</a>
            </div>

        <a href = "#">Smart Tips</a>
        <a href = "officer_quiz.php">Quiz</a>
        <a href = "officer_forum.php">Forum</a>
        <a href = "../auth/logout.php">Logout</a>

    </div>

    <div class="content event-page">
        <div class="Event-header">
            <h2>My Event</h2>
        </div>

        <div class="status-table-container">
            <table class="status-table">
                <thead>
                    <tr>
                        <th>Photo</th> <th>Event Name</th>
                        <th>Date & Time</th>
                        <th>Location</th>
                        <th>Participant Limit</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($row['picture'])): ?>
                                        <img src="../../assets/images/proposals/<?php echo $row['picture']; ?>" class="table-img">
                                    <?php else: ?>
                                        <div class="no-img-placeholder">No Image</div>
                                    <?php endif; ?>
                                </td>

                                <td><strong><?php echo htmlspecialchars($row['event_name']); ?></strong></td>
                                <td><?php echo $row['date']; ?><br><small><?php echo $row['time']; ?></small></td>
                                <td><?php echo htmlspecialchars($row['location']); ?></td>
                                <td><?php echo $row['participant_limit']; ?></td>
                                <td>
                                    <?php 
                                        $status = strtolower($row['status']); 
                                        $badge_class = "badge-" . $status;
                                    ?>
                                    <span class="status-badge <?php echo $badge_class; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-data">You haven't submitted any proposals yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>