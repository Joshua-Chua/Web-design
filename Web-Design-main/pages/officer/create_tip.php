<?php
session_start();
require '../../config/db.php';

// Check if user is logged in and is either 'officer' or 'admin'
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'officer' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../auth/login.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $user_id = $_SESSION['user_id'];
    $thumbnail_name = NULL;

    // Handle File Upload
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
                $thumbnail_name = $new_file_name;
            } else {
                $message = "Error uploading image.";
            }
        } else {
            $message = "Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.";
        }
    }

    if (empty($message)) {
        $query = "INSERT INTO smart_tips (title, content, thumbnail, created_by) VALUES ('$title', '$content', " . ($thumbnail_name ? "'$thumbnail_name'" : "NULL") . ", '$user_id')";
        
        if (mysqli_query($conn, $query)) {
            header("Location: ../student/browse_tips.php");
            exit();
        } else {
            $message = "Database Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Smart Tip - APU Energy Sustainability</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_main.css">
    <style>
        .form-container {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 20px auto;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        textarea.form-control {
            height: 150px;
            resize: vertical;
        }
        .btn-submit {
            background: #2E8B57;
            color: #fff;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
        }
        .btn-submit:hover {
            background: #246B44;
        }
        .alert {
            padding: 15px;
            background: #e9f7ef;
            color: #2E8B57;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
    </style>
</head>
<body>

<div class = "topbar">
    <img src = "../../assets/images/menu-icon.png" class = "menu-btn" id = "menuBtn">
    <span class = "page-title">Create Smart Tip</span>

    <div class = "topbar-left">
        <img src = "../../assets/images/apu-logo.png" class = "top-logo" alt = "APU Logo">
        <a href = "officer_main.php" class = "home-btn">
            <img src = "../../assets/images/home-icon.png" class = "home-icon" alt = "Home">
        </a>
    </div>

    <div class = "topbar-right">
        <?php 
         $back_link = ($_SESSION['role'] == 'admin') ? '../admin/admin_main.php' : 'officer_main.php';
         $profile_link = ($_SESSION['role'] == 'admin') ? '../admin/admin_profile.php' : 'officer_profile.php';
        ?>
        <a href = "<?php echo $profile_link; ?>" class = "user-link">
            <img src = "../../assets/images/user-icon.png" class = "user-icon">
        </a>
        <img src = "../../assets/images/more-icon.png" class = "more-btn" id = "moreBtn">
        <div class = "more-menu" id = "moreMenu">
             <a href = "<?php echo ($_SESSION['role'] == 'admin') ? '../admin/admin_profile.php' : 'officer_profile.php'; ?>">Profile</a>
             <a href = "../auth/logout.php">Logout</a>
        </div>
    </div>
</div>

<div class = "dashboard">
    <div class = "sidebar">
        <a href = "officer_main.php">Main Menu</a>
        <?php if($_SESSION['role'] == 'officer' || $_SESSION['role'] == 'admin'): ?>
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
        <?php endif; ?>
        <a href = "../auth/logout.php">Logout</a>
    </div>

    <div class = "content">
        <div class="form-container">
            <h2>Add New Smart Tip</h2>
            <?php if (!empty($message)): ?>
                <div class="alert <?php echo (strpos($message, 'Error') !== false) ? 'alert-error' : ''; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" class="form-control" required placeholder="Enter tip title...">
                </div>

                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea name="content" id="content" class="form-control" required placeholder="Describe the energy saving tip..."></textarea>
                </div>

                <div class="form-group">
                    <label for="thumbnail">Thumbnail Image (Optional)</label>
                    <input type="file" name="thumbnail" id="thumbnail" class="form-control" accept="image/*">
                    <small style="color:#666; display:block; margin-top:5px;">Supported formats: JPG, PNG, GIF, WEBP</small>
                </div>

                <button type="submit" class="btn-submit">Publish Tip</button>
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

    document.addEventListener("click", function(e) {
        if (sidebar && sidebar.classList.contains("active") && !sidebar.contains(e.target) && e.target !== menuBtn) {
            sidebar.classList.remove("active");
        }
        if (moreMenu && moreMenu.classList.contains("active") && !moreMenu.contains(e.target) && e.target !== moreBtn) {
            moreMenu.classList.remove("active");
        }
    });
});

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
</script>

</body>
</html>
