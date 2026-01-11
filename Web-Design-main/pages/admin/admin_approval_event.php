<?php
session_start();
require '../../config/db.php';

// Security: Only Admin/Staff access
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $proposal_id = $_POST['proposal_id'];
    $decision = $_POST['action']; 
    
    $conn->begin_transaction();

    try {
        // Step A: Update proposal status
        $stmt_update = $conn->prepare("UPDATE `proposal` SET `status` = ? WHERE `proposal_id` = ?");
        $stmt_update->bind_param("si", $decision, $proposal_id);
        $stmt_update->execute();

        if ($decision === "Approved") {
            $admin_id = $_SESSION['user_id']; 
            
            // Step B: Record Approval FIRST (Generates the approval_id)
            $stmt_approval = $conn->prepare("INSERT INTO `approval` (`proposal_id`, `officer_id`, `approval_decision`, `approval_date`) VALUES (?, ?, 'Approved', NOW())");
            
            if (!$stmt_approval) {
                throw new Exception("Approval Table Error: " . $conn->error);
            }

            $stmt_approval->bind_param("ii", $proposal_id, $admin_id);
            $stmt_approval->execute();
            $approval_id = $conn->insert_id; 

            // Step C: Fetch Proposal Data
            $fetch_query = $conn->prepare("SELECT * FROM `proposal` WHERE `proposal_id` = ?");
            $fetch_query->bind_param("i", $proposal_id);
            $fetch_query->execute();
            $proposal_data = $fetch_query->get_result()->fetch_assoc();

            // Step D: Insert into Event table (Links to approval_id)
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

        $conn->commit();
        $_SESSION['message'] = "Event has been " . strtolower($decision) . " successfully.";

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "DATABASE ERROR: " . $e->getMessage();
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch Pending Proposals
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
    <title>Pending Proposals | APU Energy</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_main.css">
    <link rel="stylesheet" href="../../assets/css/admin/admin_event.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_profile.css">
</head>
<body>

<div class="topbar">
    <img src="../../assets/images/menu-icon.png" class="menu-btn" id="menuBtn">
    <span class="page-title">Admin/Pending Approval</span>

    <div class="topbar-left">
        <img src="../../assets/images/apu-logo.png" class="top-logo">
        <a href="admin_main.php" class="home-btn"><img src="../../assets/images/home-icon.png" class="home-icon"></a>
        <span class="breadcrumb">
            <span class="breadcrumb-separator">/</span>
            <a href="admin_main.php" class="breadcrumb-link">Admin</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-link">Pending Events</span>
        </span>
    </div>
</div>

<div class="dashboard">
    <div class="sidebar">
        <a href="admin_main.php">Main Menu</a>
        <a href="../../pages/officer/officer_monthly_report.php">Monthly Report</a>
        <a href="admin_approval_event.php" class="active">Events</a>
        <a href="../../pages/student/browse_tips.php">Smart Tips</a>
        <a href="../../pages/officer/officer_quiz.php">Quiz</a>
        <a href="#">Forum</a>
        <a href="../auth/logout.php">Logout</a>
    </div>

    <div class="content">
        <h2>Event Approval Queue</h2>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert-success">✅ <?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-error" style="padding:15px; background:#f8d7da; color:#721c24; border-radius:8px; margin-bottom:20px;">
                ❌ <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="status-table-container">
            <table class="status-table">
                <thead>
                    <tr>
                        <th class="col-photo">Preview</th>
                        <th class="col-info">Details</th>
                        <th class="col-action">Management</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="col-photo">
                                <div class="img-wrapper">
                                    <img src="../../assets/images/proposals/<?php echo htmlspecialchars($row['picture']); ?>" class="table-img">
                                </div>
                            </td>
                            <td class="col-info">
                                <span class="event-name"><?php echo htmlspecialchars($row['event_name']); ?></span>
                                <div class="event-meta-grid">
                                    <span>📅 <?php echo $row['date']; ?></span>
                                    <span>📍 <?php echo htmlspecialchars($row['location']); ?></span>
                                </div>
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
                        <tr>
                            <td colspan="3" class="no-data">No pending proposals found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>