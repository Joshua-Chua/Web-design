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
    $decision = $_POST['action']; // "Approved" or "Rejected"
    
    // 1. Start Transaction to ensure all tables update together
    $conn->begin_transaction();

    try {
        // Step A: Update the proposal status
        $stmt_update = $conn->prepare("UPDATE `proposal` SET `status` = ? WHERE `proposal_id` = ?");
        $stmt_update->bind_param("si", $decision, $proposal_id);
        $stmt_update->execute();

        if ($decision === "Approved") {
            // Step B: Record the Approval
            $admin_id = $_SESSION['user_id']; 
            $stmt_approval = $conn->prepare("INSERT INTO `approval` (`proposal_id`, `officer_id`, `approval_decision`, `approval_date`) VALUES (?, ?, 'Approved', NOW())");
            
            if (!$stmt_approval) {
                throw new Exception("Prepare failed for Approval: " . $conn->error);
            }

            $stmt_approval->bind_param("ii", $proposal_id, $admin_id);
            $stmt_approval->execute();
            $approval_id = $conn->insert_id; 

            // Step C: Fetch full proposal details to move to the event table
            $fetch_query = $conn->prepare("SELECT * FROM `proposal` WHERE `proposal_id` = ?");
            $fetch_query->bind_param("i", $proposal_id);
            $fetch_query->execute();
            $proposal_data = $fetch_query->get_result()->fetch_assoc();

            // Step D: Insert into 'event' table
            // Note: We reference proposal_id and approval_id to link back to history
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

        // Commit transaction
        $conn->commit();
        $_SESSION['message'] = "Event has been " . strtolower($decision) . " successfully.";

    } catch (Exception $e) {
        $conn->rollback();
        die("DATABASE ERROR: " . $e->getMessage());
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
        <a href="admin_approval_event.php" class="active">Pending Approvals</a>
        <a href="../auth/logout.php">Logout</a>
    </div>

    <div class="content">
        <h2>Event Approval Queue</h2>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert-success" style="padding:15px; background:#d4edda; color:#155724; border-radius:8px; margin-bottom:20px;">
                ✅ <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <table class="status-table" style="width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden;">
            <thead style="background:#004684; color:white;">
                <tr>
                    <th>Preview</th>
                    <th>Details</th>
                    <th>Management</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:15px; width:150px;">
                        <img src="../../assets/images/proposals/<?php echo $row['picture']; ?>" style="width:100px; height:70px; object-fit:cover; border-radius:5px;">
                    </td>
                    <td style="padding:15px;">
                        <strong><?php echo htmlspecialchars($row['event_name']); ?></strong><br>
                        <small>📅 <?php echo $row['date']; ?> | 📍 <?php echo htmlspecialchars($row['location']); ?></small>
                    </td>
                    <td style="padding:15px;">
                        <form method="POST">
                            <input type="hidden" name="proposal_id" value="<?php echo $row['proposal_id']; ?>">
                            <button type="submit" name="action" value="Approved" style="background:#28a745; color:white; border:none; padding:8px 15px; cursor:pointer; border-radius:4px;">Approve</button>
                            <button type="submit" name="action" value="Rejected" style="background:#dc3545; color:white; border:none; padding:8px 15px; cursor:pointer; border-radius:4px;">Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
