<?php
session_start();
require '../../config/db.php';

// Allow student, officer, and admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['student', 'officer', 'admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Handle Tip Deletion (Only for Admin/Officer)
if (isset($_GET['delete_id']) && ($role == 'admin' || $role == 'officer')) {
    $delete_id = intval($_GET['delete_id']);
    // Optional: Check if they own it or if admin can delete any. Assuming Admin/Officer can delete any for now.
    $del_query = "DELETE FROM smart_tips WHERE tip_id = $delete_id";
    mysqli_query($conn, $del_query);
    // Redirect to self to clear query param
    header("Location: browse_tips.php");
    exit();
}

$search = $_GET['search'] ?? '';

$query = "SELECT t.tip_id, t.title, t.content, t.thumbnail, t.created_at, u.username as author 
          FROM smart_tips t 
          JOIN user u ON t.created_by = u.user_id";

if (!empty($search)) {
    $search_sql = mysqli_real_escape_string($conn, $search);
    $query .= " WHERE t.title LIKE '%$search_sql%' OR t.content LIKE '%$search_sql%'";
}

$query .= " ORDER BY t.created_at DESC";
$result = mysqli_query($conn, $query);

// Dynamic Links based on Role
$home_link = 'student_main.php';
$profile_link = 'student_profile.php'; // Default student
if ($role == 'officer') {
    $home_link = '../officer/officer_main.php';
    $profile_link = '../officer/officer_profile.php'; // Assuming this exists or similar
} elseif ($role == 'admin') {
    $home_link = '../admin/admin_main.php';
    $profile_link = '../admin/admin_profile.php';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APU Energy Sustainability - Smart Tips</title>
    <link rel = "stylesheet" href = "../../assets/css/style.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_main.css">
    <link rel = "stylesheet" href = "../../assets/css/officer/officer_profile.css">
    <style>
        .tips-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            padding: 20px 0;
        }

        .tip-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
            border: 1px solid rgba(0,0,0,0.02);
        }

        .tip-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }

        .tip-thumbnail {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f8f9fa;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .tip-body {
            padding: 25px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .tip-title {
            color: #1a1a1a;
            margin-bottom: 12px;
            font-size: 1.35rem;
            font-weight: 700;
            line-height: 1.3;
            letter-spacing: -0.5px;
        }

        .tip-content {
            color: #555;
            line-height: 1.7;
            margin-bottom: 20px;
            flex-grow: 1;
            font-size: 0.95rem;
        }

        .tip-meta {
            font-size: 0.85rem;
            color: #999;
            border-top: 1px solid #f0f0f0;
            padding-top: 15px;
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 500;
        }

        .action-overlay {
            position: absolute;
            top: 15px;
            right: 15px;
            display: flex;
            gap: 10px;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .tip-card:hover .action-overlay {
            opacity: 1;
            transform: translateY(0);
        }

        .btn-action {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.2s;
            text-decoration: none;
            color: #444;
            backdrop-filter: blur(4px);
        }

        .btn-action:hover {
            background: #fff;
            transform: scale(1.1);
        }

        .btn-delete:hover {
            color: #e74c3c;
            background: #fff0f0;
        }

        .add-btn-container {
            margin-bottom: 30px;
            text-align: right;
        }

        .btn-add {
            background: linear-gradient(135deg, #2E8B57 0%, #3CB371 100%);
            color: white;
            padding: 12px 28px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(46, 139, 87, 0.3);
            font-size: 0.95rem;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 139, 87, 0.4);
            filter: brightness(1.05);
        }
        
        .btn-add span {
            font-size: 1.2rem;
            line-height: 1;
        }
    </style>
</head>
<body>

<div class = "topbar">
    <img src = "../../assets/images/menu-icon.png" class = "menu-btn" id = "menuBtn">
    <span class = "page-title">Smart Tips</span>

    <div class = "topbar-left breadcrumb">
        <img src = "../../assets/images/apu-logo.png" class = "top-logo" alt = "APU Logo">
        <a href = "<?php echo $home_link; ?>" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon" alt = "Home">
        </a>
        <span class = "breadcrumb-separator">/</span>
        <a href = "browse_tips.php" class = "breadcrumb-link">Smart Tips</a>
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
            <a href = "../officer/officer_monthly_report.php">Monthly Report</a>
            <a href = "#">Events</a>
            <a href = "browse_tips.php" class = "active">Smart Tips</a>
            
            <a href = "javascript:void(0);" class="dropdown-toggle" onclick="toggleDropdown('quizMenu', this)">
                Quiz <span class="arrow">&#9662;</span>
            </a>
            <div id="quizMenu" class="dropdown-container" style="display: none; flex-direction: column; padding-left: 20px; background: rgba(0,0,0,0.05);">
                <a href="../officer/officer_quiz.php" style="font-size: 0.9em;">View Quiz</a>
                <a href="../officer/officer_my_quiz.php" style="font-size: 0.9em;">My Quiz</a>
            </div>

            <a href = "#">Forum</a>
        <?php else: ?>
             <!-- Student default -->
            <!-- <a href = "view_carbon.php">Carbon Emission</a> -->
            <a href = "javascript:void(0);" class="dropdown-toggle" onclick="toggleDropdown('eventMenu', this)">
                Events <span class="arrow">&#9662;</span>
            </a>
            <div id="eventMenu" class="dropdown-container" style="display: none; flex-direction: column; padding-left: 20px; background: rgba(0,0,0,0.05);">
                <a href="#" style="font-size: 0.9em;">Event Registration</a>
                <a href="#" style="font-size: 0.9em;">Upcoming Event</a>
            </div>

            <a href = "browse_tips.php" class = "active">Smart Tips</a>
            <a href = "student_quiz.php">Quiz</a>
            <a href = "student_achievement.php">Achievement</a>
            <a href = "#">Forum</a>
        <?php endif; ?>


        <a href = "../auth/logout.php">Logout</a>
    </div>

    <div class = "content">
        <?php if ($role == 'admin' || $role == 'officer'): ?>
        <div class="add-btn-container">
            <a href="../officer/create_tip.php" class="btn-add">
                <span>+</span> Add Tips
            </a>
        </div>
        <?php endif; ?>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 style="margin-bottom: 5px;">Energy Saving Tips</h2>
                <p>Browse through smart tips to help you save energy.</p>
            </div>
            
            <form method="GET" style="display: flex; gap: 10px;" onsubmit="return false;">
                <input type="text" id="searchInput" name="search" placeholder="Search tips..." value="<?php echo htmlspecialchars($search); ?>" 
                       style="padding: 10px 15px; border-radius: 50px; border: 1px solid #ddd; outline: none; width: 250px;">
                <!-- Auto search enabled, button removed -->
            </form>
        </div>

        <div class="tips-container" id="tipsContainer">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <div class="tip-card" style="cursor: pointer;" onclick="window.location.href='view_tips.php?id=<?php echo $row['tip_id']; ?>'">
                        <?php if ($role == 'admin' || $role == 'officer'): ?>
                        <div class="action-overlay" onclick="event.stopPropagation();">
                            <!-- Edit Button -->
                            <a href="../officer/edit_tip.php?id=<?php echo $row['tip_id']; ?>" class="btn-action" title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                  <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                                </svg>
                            </a>
                            <!-- Delete Button -->
                            <a href="browse_tips.php?delete_id=<?php echo $row['tip_id']; ?>" class="btn-action btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this tip?');">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                  <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5Zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6Z"/>
                                  <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1ZM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118ZM2.5 3h11V2h-11v1Z"/>
                                </svg>
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($row['thumbnail'])): ?>
                            <img src="../../uploads/tips/<?php echo htmlspecialchars($row['thumbnail']); ?>" alt="Tip Thumbnail" class="tip-thumbnail">
                        <?php else: ?>
                            <!-- Valid, simple placeholder if no image -->
                            <div class="tip-thumbnail" style="display:flex; align-items:center; justify-content:center; color:#ccc;">
                                <span>No Image</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="tip-body">
                            <h3 class="tip-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                            <!-- Content and Meta removed as requested -->
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No tips available at the moment. Check back later!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
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

    document.addEventListener("click", function(e) {
        if (sidebar && sidebar.classList.contains("active") && !sidebar.contains(e.target) && e.target !== menuBtn) {
            sidebar.classList.remove("active");
        }
        if (moreMenu && moreMenu.classList.contains("active") && !moreMenu.contains(e.target) && e.target !== moreBtn) {
            moreMenu.classList.remove("active");
        }
    });

    /* Auto Search Functionality */
    const searchInput = document.getElementById('searchInput');
    const tipsContainer = document.getElementById('tipsContainer');
    let timeout = null;

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            const query = this.value;

            timeout = setTimeout(() => {
                fetch(`browse_tips.php?search=${encodeURIComponent(query)}`)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newContent = doc.getElementById('tipsContainer').innerHTML;
                        tipsContainer.innerHTML = newContent;
                    })
                    .catch(err => console.error('Search failed', err));
            }, 300); // 300ms delay
        });
    }
});
</script>

</body>
</html>
