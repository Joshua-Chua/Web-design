<?php
session_start();
require '../../config/db.php';

// Check permissions
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'officer' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: ../student/browse_tips.php");
    exit();
}

$tip_id = intval($_GET['id']);
$message = "";

// Fetch existing tip
$query = "SELECT * FROM smart_tips WHERE tip_id = $tip_id";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    die("Tip not found.");
}
$tip = mysqli_fetch_assoc($result);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    
    // Optional: Update image
    $thumbnail_name = $tip['thumbnail']; // Keep existing by default
    
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $upload_dir = '../../uploads/tips/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = uniqid('tip_') . '.' . $file_ext;
            $upload_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $upload_path)) {
                // Determine if we should delete the old image? Maybe later.
                $thumbnail_name = $new_file_name;
            } else {
                $message = "Error uploading image.";
            }
        } else {
            $message = "Invalid file type.";
        }
    }

    if (empty($message)) {
        $update_query = "UPDATE smart_tips SET title = '$title', content = '$content', thumbnail = " . ($thumbnail_name ? "'$thumbnail_name'" : "NULL") . " WHERE tip_id = $tip_id";
        
        if (mysqli_query($conn, $update_query)) {
            $message = "Smart Tip updated successfully!";
            // Refresh data
            $tip['title'] = $title;
            $tip['content'] = $content;
            $tip['thumbnail'] = $thumbnail_name;
        } else {
            $message = "Database Error: " . mysqli_error($conn);
        }
    }
}
?>

<?php
// Determine Role-Based Links
$role = $_SESSION['role'];
$home_link = 'officer_main.php';
$profile_link = 'officer_profile.php';

if ($role == 'admin') {
    $home_link = '../admin/admin_main.php';
    $profile_link = '../admin/admin_profile.php';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Smart Tip - APU Energy Sustainability</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_main.css">
    <style>
        .form-container {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto; /* Centered in content area */
        }
        .form-group {
            margin-bottom: 20px;
        }
        .btn-submit {
            background: #2E8B57;
            color: #fff;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        textarea.form-control {
            height: 150px;
        }
        .alert {
            padding: 15px;
            background: #e9f7ef;
            color: #2E8B57;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .current-img {
            max-width: 200px;
            border-radius: 8px;
            margin-top: 10px;
            display: block;
        }
    </style>
</head>
<body>

<div class = "topbar">
    <img src = "../../assets/images/menu-icon.png" class = "menu-btn" id = "menuBtn">
    <span class = "page-title">Edit Smart Tip</span>

    <div class = "topbar-left">
        <img src = "../../assets/images/apu-logo.png" class = "top-logo" alt = "APU Logo">
        <a href = "<?php echo $home_link; ?>" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon" alt = "Home">
        </a>
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
            <a href = "officer_monthly_report.php">Monthly Report</a>
            <a href = "officer_event.php">Events</a>
            <a href = "../student/browse_tips.php" class="active">Smart Tips</a>
            
            <a href = "javascript:void(0);" class="dropdown-toggle" onclick="toggleDropdown('quizMenu', this)">
                Quiz <span class="arrow">&#9662;</span>
            </a>
            <div id="quizMenu" class="dropdown-container" style="display: none; flex-direction: column; padding-left: 20px; background: rgba(0,0,0,0.05);">
                <a href="officer_quiz.php" style="font-size: 0.9em;">View Quiz</a>
                <a href="officer_my_quiz.php" style="font-size: 0.9em;">My Quiz</a>
            </div>

            <a href = "officer_forum.php">Forum</a>
        <?php else: ?>
             <!-- Should not happen given the auth check -->
        <?php endif; ?>
        <a href = "../auth/logout.php">Logout</a>
    </div>

    <div class = "content">
        <div class="form-container">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="margin:0;">Edit Content</h2>
                <a href="../student/browse_tips.php" style="color: #666; text-decoration: none;">&larr; Back to Tips</a>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert"><?php echo $message; ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" class="form-control" required value="<?php echo htmlspecialchars($tip['title']); ?>">
                </div>

                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea name="content" id="content" class="form-control" required><?php echo htmlspecialchars($tip['content']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="thumbnail">Thumbnail Image (Optional - leave blank to keep current)</label>
                    <input type="file" name="thumbnail" id="thumbnail" class="form-control" accept="image/*">
                    <?php if ($tip['thumbnail']): ?>
                        <div style="margin-top:10px;">
                            <small>Current Image:</small><br>
                            <img src="../../uploads/tips/<?php echo htmlspecialchars($tip['thumbnail']); ?>" class="current-img" alt="Current Thumbnail">
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-submit">Update Tip</button>
            </form>
        </div>
    </div>
</div>

<script>
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
            moreMenu.classList.remove("active")
        }
    });
});
</script>

</body>
</html>
