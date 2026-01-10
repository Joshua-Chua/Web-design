<?php
session_start();
require '../../config/db.php';

// Allow Officer and Admin
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'officer' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../auth/login.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['username']; 
$profile_link = ($role == 'admin') ? '../admin/admin_profile.php' : 'officer_profile.php';
$home_link = ($role == 'admin') ? '../admin/admin_main.php' : 'officer_main.php';

// Fetch Report Data
$reports = [];
$query = "SELECT * FROM carbon_emission ORDER BY year DESC, FIELD(month, 'December', 'November', 'October', 'September', 'August', 'July', 'June', 'May', 'April', 'March', 'February', 'January')";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Energy Report</title>
    <link rel = "stylesheet" href = "../../assets/css/style.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_main.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_profile.css">
    <style>
        .report-page-container {
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 0.95rem;
        }
        .report-table th, .report-table td {
            padding: 12px 15px;
            border: 1px solid #eee;
            text-align: center;
        }
        .report-table th {
            background-color: #f4f4f4;
            color: #333;
            font-weight: 600;
        }
        .report-table tr:nth-child(even) {
            background-color: #fcfcfc;
        }
        .report-table tr:hover {
            background-color: #f1f1f1;
        }
        .section-header {
            background-color: #e9ecef;
            color: #495057;
            text-transform: uppercase;
            font-size: 0.8em;
            letter-spacing: 1px;
        }
        /* Group headers */
        .group-header-row th {
            border-bottom: none;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
            color: #555;
        }
        .sub-header-row th {
            border-top: none;
            font-size: 0.9em;
            color: #777;
        }
        .print-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 10px;
            float: right;
        }
        .print-btn:hover { background: #0056b3; }
        
        @media print {
            .sidebar, .topbar, .print-btn { display: none !important; }
            .dashboard { margin: 0; padding: 0; }
            .content { margin: 0; padding: 0; width: 100%; }
        }
        /* Cache bypass fix */
        .dashboard {
            min-height: calc(100vh - 80px) !important;
            height: auto !important;
        }
        .content {
            background: #ffffff !important;
        }
    </style>
</head>
<body>

<div class = "topbar">
    <img src = "../../assets/images/menu-icon.png" class = "menu-btn" id = "menuBtn">
    <span class = "page-title">Monthly Report</span>

    <div class = "topbar-left breadcrumb">
        <img src = "../../assets/images/apu-logo.png" class = "top-logo" alt = "APU Logo">
        <a href = "<?php echo $home_link; ?>" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon" alt = "Home">
        </a>
        <span class = "breadcrumb">
            <span class = "breadcrumb-separator">/</span>
            <a href = "officer_monthly_report.php" class = "breadcrumb-link">Monthly Report</a>
        </span>
    </div>

    <div class = "topbar-right">
        <a href = "<?php echo $profile_link; ?>" class = "user-link">
            <img src = "../../assets/images/user-icon.png" class = "user-icon">
        </a>
        <img src = "../../assets/images/more-icon.png" class = "more-btn" id = "moreBtn">
        <div class = "more-menu" id = "moreMenu">
            <a href = "<?php echo $profile_link; ?>">Profile</a>
            <a href = "../auth/logout.php">Logout</a>
        </div>
    </div>
</div>

<div class = "dashboard">
    <div class = "sidebar">
        <a href = "<?php echo $home_link; ?>">Main Menu</a>
        
        <?php if($role == 'officer' || $role == 'admin'): ?>
            <a href = "officer_monthly_report.php" class="active">Monthly Report</a>
            <a href = "officer_event.php">Events</a>
            <a href = "../student/browse_tips.php">Smart Tips</a>
            
            <a href = "javascript:void(0);" class="dropdown-toggle" onclick="toggleDropdown('quizMenu', this)">
                Quiz <span class="arrow">&#9662;</span>
            </a>
            <div id="quizMenu" class="dropdown-container" style="display: none; flex-direction: column; padding-left: 20px; background: rgba(0,0,0,0.05);">
                <a href="officer_quiz.php" style="font-size: 0.9em;">View Quiz</a>
                <a href="officer_my_quiz.php" style="font-size: 0.9em;">My Quiz</a>
            </div>

            <a href = "officer_forum.php">Forum</a>
        <?php else: ?>
             <!-- Should not happen given the auth check, but just in case -->
        <?php endif; ?>

        <a href = "../auth/logout.php">Logout</a>
    </div>

    <div class = "content">
        <div class="report-page-container">
            <h2 style="margin-bottom:20px;">Monthly Energy & Carbon Emission Report</h2>
            
            <button class="print-btn" onclick="window.print()">Print Report</button>
            <div style="clear:both;"></div>

            <div style="overflow-x:auto;">
                <table class="report-table">
                    <thead>
                        <tr class="group-header-row">
                            <th rowspan="2" style="vertical-align: middle; border-bottom: 1px solid #eee;">Month / Year</th>
                            <th colspan="2" style="background:#e3f2fd;">Total Campus</th>
                            <th colspan="2" style="background:#f1f8e9;">Block A</th>
                            <th colspan="2" style="background:#fff3e0;">Block B</th>
                            <th colspan="2" style="background:#fce4ec;">Block C</th>
                            <th colspan="2" style="background:#f3e5f5;">Block D</th>
                        </tr>
                        <tr class="sub-header-row">
                            <!-- Total -->
                            <th style="background:#e3f2fd;">Energy (kWh)</th>
                            <th style="background:#e3f2fd;">Carbon (kg)</th>
                            
                            <!-- Block A -->
                            <th style="background:#f1f8e9;">Energy</th>
                            <th style="background:#f1f8e9;">Carbon</th>
                            
                            <!-- Block B -->
                            <th style="background:#fff3e0;">Energy</th>
                            <th style="background:#fff3e0;">Carbon</th>
                            
                            <!-- Block C -->
                            <th style="background:#fce4ec;">Energy</th>
                            <th style="background:#fce4ec;">Carbon</th>
                            
                            <!-- Block D -->
                            <th style="background:#f3e5f5;">Energy</th>
                            <th style="background:#f3e5f5;">Carbon</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($reports as $rpt): 
                            $total_e = $rpt['electricity_usage_kwh'];
                            $total_c = $rpt['carbon_avoided_kg'];
                            $ratio = ($total_e > 0) ? ($total_c / $total_e) : 0;

                            // Calculate Carbon for blocks based on ratio
                            // If block usage is null (old data before migration, though we updated it), treat as 0
                            $ba_e = $rpt['block_a_usage'] ?? 0;
                            $bb_e = $rpt['block_b_usage'] ?? 0;
                            $bc_e = $rpt['block_c_usage'] ?? 0;
                            $bd_e = $rpt['block_d_usage'] ?? 0;

                            $ba_c = $ba_e * $ratio;
                            $bb_c = $bb_e * $ratio;
                            $bc_c = $bc_e * $ratio;
                            $bd_c = $bd_e * $ratio;
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($rpt['month'] . ' ' . $rpt['year']); ?></strong></td>
                            
                            <!-- Total -->
                            <td style="background:#f8faff; font-weight:bold;"><?php echo number_format($total_e, 2); ?></td>
                            <td style="background:#f8faff; font-weight:bold;color:#2e7d32;"><?php echo number_format($total_c, 2); ?></td>

                            <!-- Block A -->
                            <td><?php echo number_format($ba_e, 2); ?></td>
                            <td><?php echo number_format($ba_c, 2); ?></td>

                            <!-- Block B -->
                            <td><?php echo number_format($bb_e, 2); ?></td>
                            <td><?php echo number_format($bb_c, 2); ?></td>

                            <!-- Block C -->
                            <td><?php echo number_format($bc_e, 2); ?></td>
                            <td><?php echo number_format($bc_c, 2); ?></td>

                            <!-- Block D -->
                            <td><?php echo number_format($bd_e, 2); ?></td>
                            <td><?php echo number_format($bd_c, 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($reports)): ?>
                            <tr><td colspan="11">No reports found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
window.toggleDropdown = function(id, el) {
    var dropdown = document.getElementById(id);
    if (dropdown.style.display === "none" || dropdown.style.display === "") {
        dropdown.style.display = "flex";
        if(el.querySelector('.arrow')) el.querySelector('.arrow').innerHTML = '&#9652;'; // Up arrow
    } else {
        dropdown.style.display = "none";
            if(el.querySelector('.arrow')) el.querySelector('.arrow').innerHTML = '&#9662;'; // Down arrow
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const menuBtn = document.getElementById("menuBtn");
    const sidebar = document.querySelector(".sidebar");
    const moreBtn = document.getElementById("moreBtn");
    const moreMenu = document.getElementById("moreMenu");

    if (menuBtn && sidebar) {
        menuBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            sidebar.classList.toggle("active");
        });
    }

    if (moreBtn && moreMenu) {
        moreBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            moreMenu.classList.toggle("active");
        });
    }

    document.addEventListener("click", function(e) {
        if (sidebar && sidebar.classList.contains("active") && !sidebar.contains(e.target) && e.target !== menuBtn) {
            sidebar.classList.remove("active");
        }
        if (moreMenu && moreMenu.classList.contains("active") && !moreMenu.contains(e.target) && e.target !== moreBtn) {
            moreMenu.classList.remove("active");
        }
    });
});
</script>
</body>
</html>
