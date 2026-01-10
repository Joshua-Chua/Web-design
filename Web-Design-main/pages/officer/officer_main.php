<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();

require '../../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'officer') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$officer_name = $_SESSION['username'] ?? 'User';

try {
    $query = "SELECT name FROM officer WHERE user_id = '$user_id' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }
    
    if (mysqli_num_rows($result) == 1) {
        $officer = mysqli_fetch_assoc($result);
        $officer_name = $officer['name'];
    }
    mysqli_free_result($result);
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APU Energy Sustainability</title>
    <link rel = "stylesheet" href = "../../assets/css/style.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_main.css">
</head>
<body>

<div class = "topbar">

    <!-- Mobile Menu Button -->
    <img src = "../../assets/images/menu-icon.png" class = "menu-btn" id = "menuBtn">
    <span class = "page-title">Main Menu</span>

    <div class = "topbar-left">
        <img src = "../../assets/images/apu-logo.png" class = "top-logo" alt = "APU Logo">

        <!-- Home Button -->
        <a href = "officer_main.php" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon" alt = "Home">
        </a>

        <span class = "welcome-text">Welcome, <?php echo htmlspecialchars($officer_name); ?></span>
    </div>

    <div class = "topbar-right">

        <!-- Laptop User Icon -->
        <a href = "officer_profile.php" class = "user-link">
            <img src = "../../assets/images/user-icon.png" class = "user-icon">
        </a>

        <!-- Mobile More Button-->
        <img src = "../../assets/images/more-icon.png" class = "more-btn" id = "moreBtn">

        <div class = "more-menu" id = "moreMenu">
            <a href = "officer_profile.php">Profile</a>
            <a href = "../auth/logout.php">Logout</a>
        </div>
    </div>

</div>

<div class = "dashboard">
    <div class = "sidebar">
        <a href = "officer_main.php" class = "active">Main Menu</a>
        <a href = "officer_monthly_report.php">Monthly Report</a>
        <a href = "officer_event.php">Events</a>
        <a href = "../../pages/student/browse_tips.php">Smart Tips</a>
        <a href = "officer_quiz.php">Quiz</a>
        <a href = "officer_forum.php">Forum</a>
        <a href = "../auth/logout.php">Logout</a>
    </div>

        <style>
        .dashboard-cards {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .stat-card {
            flex: 1;
            min-width: 280px;
            background: #ffffff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08); /* Softer, deeper shadow */
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            align-items: center;
            gap: 25px;
            cursor: default;
            position: relative;
            overflow: hidden;
            border: none; /* Removed border for cleaner look */
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        /* Decorative background circle */
        .stat-card::before {
            content: '';
            position: absolute;
            top: -20px;
            right: -20px;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15); /* Subtle overlay */
            pointer-events: none;
        }

        .icon-box {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: rgba(255, 255, 255, 0.25); /* Glossy icon background */
            backdrop-filter: blur(5px);
        }

        .icon-box svg {
            width: 36px;
            height: 36px;
            stroke: #fff; /* White icons */
        }

        .stat-content {
            z-index: 1; /* Ensure text is above background elements */
        }

        .stat-content h3 {
            margin: 0;
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.9); /* White text */
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 8px 0 0 0;
            line-height: 1.1;
            color: #fff;
        }

        .stat-unit {
            font-size: 1rem;
            font-weight: 500;
            margin-left: 5px;
            opacity: 0.8;
        }

        .stat-period {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 8px;
        }

        /* Energy Card - Vibrant Green Gradient */
        .card-energy {
            background: linear-gradient(135deg, #2E8B57 0%, #3CB371 100%);
        }

        /* Carbon Card - Deep Blue/Cyan Gradient */
        .card-carbon {
            background: linear-gradient(135deg, #008CBA 0%, #00BFFF 100%);
        }
    </style>

    <div class = "content">
        <p style="color: #666; margin-bottom: 30px;">Here is the latest energy sustainability data.</p>
        
        <?php
        // Fetch and Update January 2026 Data
        $month = 'January';
        $year = 2026;
        
        $stmt = $conn->prepare("SELECT * FROM carbon_emission WHERE month = ? AND year = ?");
        $stmt->bind_param("si", $month, $year);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $usage = $row['electricity_usage_kwh'];
            $avoided = $row['carbon_avoided_kg'];

            // Increment usage to simulate real-time growth
            // Add between 0.5 and 1.5 kWh per page load
            $increment = rand(50, 150) / 100; 
            $usage += $increment;
            
            // Recalculate carbon (approx factor 0.3)
            $avoided = $usage * 0.3;

            // Distribute increment to blocks (simplistic split)
            $split = $increment / 4;
            
            $stmt_up = $conn->prepare("UPDATE carbon_emission SET 
                electricity_usage_kwh = ?, 
                carbon_avoided_kg = ?,
                block_a_usage = block_a_usage + ?,
                block_b_usage = block_b_usage + ?,
                block_c_usage = block_c_usage + ?,
                block_d_usage = block_d_usage + ?
                WHERE emission_id = ?");
            
            $stmt_up->bind_param("ddddddi", $usage, $avoided, $split, $split, $split, $split, $row['emission_id']);
            $stmt_up->execute();

        } else {
            // Fallback if not found
            $usage = 1245.50; 
            $avoided = 432.80;
        }
        ?>

        <div class="dashboard-cards">
             <!-- Energy Usage Card -->
             <div class="stat-card card-energy" style="cursor: default;">
                <div class="icon-box">
                    <!-- Lightning Bolt SVG -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <h3>Energy Usage</h3>
                    <p class="stat-value">
                        <span id="energyUsage"><?php echo number_format($usage, 2); ?></span>
                        <span class="stat-unit">kWh</span>
                    </p>
                    <div class="stat-period">January 2026</div>
                </div>
             </div>

             <!-- Carbon Emission Avoided Card -->
             <div class="stat-card card-carbon" style="cursor: default;">
                <div class="icon-box">
                    <!-- Shield/Leaf SVG -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div class="stat-content">
                    <h3>Carbon Avoided</h3>
                    <p class="stat-value">
                        <span id="carbonAvoided"><?php echo number_format($avoided, 2); ?></span>
                        <span class="stat-unit">kg</span>
                    </p>
                    <div class="stat-period">January 2026</div>
                </div>
             </div>
        </div>
    </div>

</div>

<script>
<?php if (isset($_SESSION['error'])): ?>
    alert("Error: <?php echo addslashes($_SESSION['error']); ?>");
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

document.addEventListener("DOMContentLoaded", function () {
/* Life Data Simulation */
    let currentEnergy = <?php echo json_encode($usage); ?>;
    let currentCarbon = <?php echo json_encode($avoided); ?>;
    const carbonRatio = currentCarbon / currentEnergy; 

    const energyEl = document.getElementById('energyUsage');
    const carbonEl = document.getElementById('carbonAvoided');

    function updateCounters() {
        // Increment energy by a random small amount between 0.01 and 0.05
        const increment = (Math.random() * (0.05 - 0.01) + 0.01);
        currentEnergy += increment;

        // Calculate new carbon avoided based on the formula
        const newCarbon = currentEnergy * carbonRatio;

        // Update DOM
        if(energyEl) energyEl.innerText = currentEnergy.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if(carbonEl) carbonEl.innerText = newCarbon.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    // Update every 3 seconds
    setInterval(updateCounters, 3000);

    /* Sidebar Dropdown Toggle */
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
});
</script>

<script src = '../../assets/js/main.js'></script>

</body>
</html>