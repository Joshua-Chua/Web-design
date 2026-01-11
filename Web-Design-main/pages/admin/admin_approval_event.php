<?php
session_start();
require '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $proposal_id = $_POST['proposal_id'];
    $decision = $_POST['action']; // "Approved" or "Rejected"
    
    // 1. Start Transaction
    $conn->begin_transaction();

    try {
        $stmt_update = $conn->prepare("UPDATE `proposal` SET `status` = ? WHERE `proposal_id` = ?");
        $stmt_update->bind_param("si", $decision, $proposal_id);
        $stmt_update->execute();

        if ($decision === "Approved") {

            // --- START OF FIXED STEP B ---
            $admin_id = $_SESSION['officer_id'] ?? $_SESSION['user_id'] ?? 1; 
    
            // We added backticks around table and column names to fix the syntax error
            $stmt_approval = $conn->prepare("INSERT INTO `approval` (`proposal_id`, `officer_id`, `approval_date`) VALUES (?, ?, NOW())");
    
        if (!$stmt_approval) {
            throw new Exception("Prepare failed for Approval: " . $conn->error);
        }

        $stmt_approval->bind_param("ii", $proposal_id, $admin_id);
        $stmt_approval->execute();
        $approval_id = $conn->insert_id; 
        // --- END OF FIXED STEP B ---

        // Step C: Fetch the original proposal details
        $fetch_query = $conn->prepare("SELECT * FROM `proposal` WHERE `proposal_id` = ?");
        $fetch_query->bind_param("i", $proposal_id);
        $fetch_query->execute();
        $proposal_data = $fetch_query->get_result()->fetch_assoc();

        // Step D: Insert into 'event' table
        $stmt_event = $conn->prepare("INSERT INTO `event` (`approval_id`, `proposal_id`, `event_name`, `event_description`, `date`, `time`, `location`, `picture`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_event->bind_param("iissssss", 
            $approval_id, 
            $proposal_id, 
            $proposal_data['event_name'], 
            $proposal_data['event_description'], 
            $proposal_data['date'], 
            $proposal_data['time'], 
            $proposal_data['location'], 
            $proposal_data['picture']
        );
        $stmt_event->execute();
    }

        // 2. Commit everything if no errors occurred
        $conn->commit();
        $_SESSION['message'] = "Event proposal has been successfully " . strtolower($decision) . " and moved to events.";

    } catch (Exception $e) {
        // 3. Rollback changes if something goes wrong
        $conn->rollback();
        die("DATABASE ERROR: " . $e->getMessage() . " on line " . $e->getLine());
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// 2. Fetch the list (unchanged)
$status_pending = 'Pending';
$stmt_fetch = $conn->prepare("SELECT * FROM proposal WHERE status = ?");
$stmt_fetch->bind_param("s", $status_pending);
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Proposals | Event </title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin/admin_event.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_profile.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_main.css">

</head>
<body>
<div class = "topbar">
    <img src = "../../assets/images/menu-icon.png" class = "menu-btn" id = "menuBtn">
    <span class = "page-title">Event/Pending Event</span>

    <div class = "topbar-left">
        <img src = "../../assets/images/apu-logo.png" class = "top-logo">

        <a href = "officer_main.php" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon">
        </a>

        <span class = "breadcrumb">
            <span class = "breadcrumb-separator">/</span>
            <a href = "admin_approval_event.php" class = "breadcrumb-link">Event</a>
            <span class = "breadcrumb-separator">/</span>
            <a href = "admin_approval_event.php" class = "breadcrumb-link">Pending Event</a>
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
        <a href = "admin_main.php">Main Menu</a>
        <a href = "#">Monthly Report</a>
        <a href = "admin_approval_event.php" class = "active">Events</a>
        <a href = "#">Smart Tips</a>
        <a href = "#">Quiz</a>
        <a href = "#">Forum</a>
        <a href = "../auth/logout.php">Logout</a>
        
    </div>

    <div class="content admin-page">
        <header class="Event-header">
            <h2>Pending Event Approval</h2>
        </header>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert-success">
                ✅ <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>



        <div class="status-table-container">
            <table class="status-table">
                <thead>
                    <tr class="table-header-row">
                    <th class="col-photo">Preview</th>
                    <th class="col-info">Event Details</th>
                    <th class="col-desc">Brief Description</th>
                    <th class="col-action">Management</th>
                  </tr>
                </thead>

                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td class="col-photo">
                                <div class="img-wrapper">
                                    <img src="../../assets/images/proposals/<?php echo $row['picture']; ?>" class="table-img">
                                </div>
                            </td>
                            <td class="col-info">
                                <h3 class="event-title"><?php echo htmlspecialchars($row['event_name']); ?></h3>
                                <div class="event-meta-grid">
                                    <span>📅 <?php echo date("d M Y", strtotime($row['date'])); ?></span>
                                    <span>⏰ <?php echo date("h:i A", strtotime($row['time'])); ?></span>
                                    <span>📍 <?php echo htmlspecialchars($row['location']); ?></span>
                                </div>
                            </td>

                            <td class="col-desc">
                                <p class="description-text"><?php echo htmlspecialchars(substr($row['event_description'], 0, 80)); ?>...</p>
                            </td>
                            <td class="col-action">
                                <form method="POST" class="action-buttons">
                                    <input type="hidden" name="proposal_id" value="<?php echo $row['proposal_id']; ?>">
                                    <button type="submit" name="action" value="Approved" class="btn-approve">Approve</button>
                                    <button type="submit" name="action" value="Rejected" class="btn-reject">Reject</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="no-data">No pending proposals found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>    
</body>
</html>