<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
session_start();
require '../../config/db.php';

// Include the new achievement system
try {
    require_once '../../system/achievement_helpers.php';
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to load achievement system: " . $e->getMessage();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['username'] ?? 'Student';

$new_achievements_from_check = [];
$achievement_data = [];
$achievements = [];
$unlocked_count = 0;
$total_achievements = 0;
$quiz_progress = 0;
$login_progress = 0;
$progress_percentage = 0;
$notification_html = '';

try {
    $new_achievements_from_check = checkAllAchievements($conn, $user_id);

    // If there are new achievements from the check, add them to session for notification
    if (!empty($new_achievements_from_check)) {
        if (!isset($_SESSION['new_achievements'])) {
            $_SESSION['new_achievements'] = [];
        }
        
        // Add new achievements to session
        foreach ($new_achievements_from_check as $new_achievement) {
            // Check if this achievement was already notified in this session
            $already_notified = false;
            foreach ($_SESSION['new_achievements'] as $existing) {
                if ((is_array($existing) && $existing['name'] == $new_achievement['name']) || 
                    (is_string($existing) && $existing == $new_achievement['name'])) {
                    $already_notified = true;
                    break;
                }
            }
            
            if (!$already_notified) {
                $_SESSION['new_achievements'][] = [
                    'name' => $new_achievement['name'],
                    'description' => $new_achievement['description'] ?? ''
                ];
            }
        }
    }

    // Get all achievement data for display
    $achievement_data = getUserAchievementData($conn, $user_id);
    
    if (!$achievement_data || !is_array($achievement_data)) {
        throw new Exception("Failed to load achievement data");
    }

    // Extract data from the achievement system
    $achievements = $achievement_data['achievements'] ?? [];
    $unlocked_count = $achievement_data['unlocked_count'] ?? 0;
    $total_achievements = $achievement_data['total_count'] ?? 0;

    // Set progress variables from achievement data
    $quiz_progress = $achievement_data['quiz_progress'] ?? 0;
    $login_progress = $achievement_data['login_progress'] ?? 0;

    // Filter to show all achievements
    $filtered_achievements = array_filter($achievements, function($ach) {
        return in_array($ach['requirement_type'], ['quizzes_completed', 'login_days']);
    });

    // Recalculate counts based on filtered achievements
    $achievements = array_values($filtered_achievements);
    $total_achievements = count($achievements);

    // Recalculate unlocked count for filtered achievements
    $unlocked_count = 0;
    foreach ($achievements as $ach) {
        if ($ach['unlocked']) {
            $unlocked_count++;
        }
    }

    // Calculate progress percentage
    $progress_percentage = $total_achievements > 0 ? round(($unlocked_count / $total_achievements) * 100) : 0;

    // Check for new achievements in session
    if (isset($_SESSION['new_achievements']) && !empty($_SESSION['new_achievements'])) {
        // Convert to the format expected by displayAchievementNotification
        $new_achievements = [];
        foreach ($_SESSION['new_achievements'] as $achievement) {
            if (isset($achievement['name'])) {
                $new_achievements[] = ['name' => $achievement['name']];
            } else if (is_string($achievement)) {
                $new_achievements[] = ['name' => $achievement];
            }
        }
        
        if (!empty($new_achievements)) {
            $notification_html = displayAchievementNotification($new_achievements);
        }
        unset($_SESSION['new_achievements']);
    }

    // Also check the old format
    if (isset($_SESSION['achievement_notification']) && !empty($_SESSION['achievement_notification'])) {
        $new_achievements = [];
        foreach ($_SESSION['achievement_notification'] as $achievement) {
            if (isset($achievement['name'])) {
                $new_achievements[] = ['name' => $achievement['name']];
            } else if (is_string($achievement)) {
                $new_achievements[] = ['name' => $achievement];
            }
        }
        
        if (!empty($new_achievements)) {
            $notification_html = displayAchievementNotification($new_achievements);
        }
        unset($_SESSION['achievement_notification']);
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Achievement system error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APU Energy Sustainability</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_main.css">
    <link rel="stylesheet" href="../../assets/css/officer/officer_profile.css">
    <link rel="stylesheet" href="../../assets/css/student/student_quiz.css">
    <link rel="stylesheet" href="../../assets/css/student/student_achievement.css">
</head>
<body>
    
<div class="topbar">
    <img src="../../assets/images/menu-icon.png" class="menu-btn" id="menuBtn">
    <span class="page-title">Achievement</span>

    <div class="topbar-left">
        <img src="../../assets/images/apu-logo.png" class="top-logo">

        <a href="student_main.php" class="home-btn" id="homeBtn">
            <img src="../../assets/images/home-icon.png" class="home-icon">
        </a>

        <span class="breadcrumb">
            <span class="breadcrumb-separator">/</span>
            <a href="student_achievement.php" class="breadcrumb-link" id="breadcrumb">Achievement</a>
        </span>
    </div>

    <div class="topbar-right">
        <img src="../../assets/images/more-icon.png" class="more-btn" id="moreBtn">
        <div class="more-menu" id="moreMenu">
            <a href="student_profile.php" class="more-menu-link">Profile</a>
            <a href="../auth/logout.php" class="more-menu-link">Logout</a>
        </div>
    </div>
</div>

<div class="dashboard">

    <div class="sidebar">
        <a href="student_main.php" class="sidebar-link">Main Menu</a>
        <a href="#" class="sidebar-link">Events</a>
        <a href="browse_tips.php" class="sidebar-link">Smart Tips</a>
        <a href="student_quiz.php" class="sidebar-link">Quiz</a>
        <a href="student_achievement.php" class="sidebar-link active">Achievement</a>
        <a href="#" class="sidebar-link">Forum</a>
        <a href="../auth/logout.php" class="sidebar-link">Logout</a>
    </div>

    <div class="content achievement-page">
        <!-- Display achievement notification from session -->
        <?= $notification_html ?>
        
        <div class="achievement-header">
            <h1>Achievements</h1>
            <h2>Track your progress and unlock rewards</h2>
            
            <div class="progress-stats">
                <div class="progress-card">
                    <div class="progress-icon">
                        <img src="../../assets/images/trophy-icon.png" alt="Trophy">
                    </div>
                    <div class="progress-content">
                        <div class="progress-value"><?= $unlocked_count ?>/<?= $total_achievements ?></div>
                        <div class="progress-label">Achievements Unlocked</div>
                    </div>
                </div>
                
                <div class="progress-card">
                    <div class="progress-icon">
                        <img src="../../assets/images/progress-icon.png" alt="Progress">
                    </div>
                    <div class="progress-content">
                        <div class="progress-value"><?= $progress_percentage ?>%</div>
                        <div class="progress-label">Overall Progress</div>
                    </div>
                </div>
            </div>
            
            <?php if ($total_achievements > 0): ?>
            <div class="progress-container">
                <div class="progress-label-bar">
                    <span class="progress-text">Achievement Progress</span>
                    <span class="progress-text"><?= $unlocked_count ?>/<?= $total_achievements ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $progress_percentage ?>%"></div>
                </div>
            </div>
            
            <div class="achievement-categories">
                <button class="category-btn active" data-category="all">All Achievements</button>
                <button class="category-btn" data-category="unlocked">Unlocked</button>
                <button class="category-btn" data-category="locked">Locked</button>
            </div>
            <?php endif; ?>
        </div>

        <?php if (count($achievements) > 0): ?>
        <div class="achievement-grid" id="achievementGrid">
            <?php foreach ($achievements as $achievement): 
                $is_unlocked = $achievement['unlocked'];
                $date_awarded = !empty($achievement['date_awarded']) ? date('M d, Y', strtotime($achievement['date_awarded'])) : null;
    
                // Calculate progress for this achievement
                $user_progress = 0;
                $requirement_text = '';
                
                if ($achievement['requirement_type'] === 'quizzes_completed') {
                    $user_progress = $quiz_progress;
                    $progress = $user_progress >= $achievement['requirement_value'] ? 100 : 
                        ($user_progress / $achievement['requirement_value'] * 100);
                    $requirement_text = "Complete " . $achievement['requirement_value'] . " quizzes";
                } elseif ($achievement['requirement_type'] === 'login_days') {
                    $user_progress = $login_progress;
                    $progress = $user_progress >= $achievement['requirement_value'] ? 100 : 
                        ($user_progress / $achievement['requirement_value'] * 100);
                    $requirement_text = "Login for " . $achievement['requirement_value'] . " consecutive days";
                } else {
                    $progress = 0;
                    $requirement_text = "Complete requirements";
                }
    
                $progress = min(100, $progress);
                
                // Determine which image to use
                $image_path = '../../uploads/achievement/' . htmlspecialchars($achievement['picture']);
                $default_image = '../../assets/images/default-achievement.png';
                $final_image_path = $default_image;
                
                if (!empty($achievement['picture']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/Web-design/Web-Design-main/uploads/achievement/' . $achievement['picture'])) {
                    $final_image_path = $image_path;
                }
            ?>
            <div class="achievement-card <?= $is_unlocked ? 'unlocked' : 'locked' ?>" 
                 data-id="<?= $achievement['achievement_id'] ?>"
                 data-category="<?= $is_unlocked ? 'unlocked' : 'locked' ?>"
                 data-image="<?= $final_image_path ?>"
                 data-name="<?= htmlspecialchars($achievement['name']) ?>"
                 data-description="<?= htmlspecialchars($achievement['description']) ?>"
                 data-requirement="<?= $achievement['requirement_value'] ?>"
                 data-unlocked="<?= $is_unlocked ?>"
                 data-type="<?= $achievement['requirement_type'] ?>"
                 data-progress="<?= $user_progress ?>">
                
                <?php if (!$is_unlocked): ?>
                    <img src="../../assets/images/lock-icon.png" alt="Locked" class="lock-icon">
                <?php endif; ?>
                
                <div class="achievement-image">
                    <img src="<?= $final_image_path ?>" 
                         alt="<?= htmlspecialchars($achievement['name']) ?>"
                         onerror="this.src='<?= $default_image ?>'">
                </div>
                
                <h3 class="achievement-name"><?= htmlspecialchars($achievement['name']) ?></h3>
                
                <p class="achievement-description">
                    <?= htmlspecialchars($achievement['description']) ?>
                </p>
                
                <div class="achievement-requirement">
                    <?php if ($is_unlocked): ?>
                        ✓ Unlocked
                    <?php else: ?>
                        <?php if ($achievement['requirement_type'] === 'login_days'): ?>
                            <img src="../../assets/images/lock-icon.png" alt="Locked" style="width: 12px; height: 12px; margin-right: 4px; vertical-align: middle;">
                        <?php endif; ?>
                        <?= $requirement_text ?>
                    <?php endif; ?>
                </div>
                
                <?php if ($is_unlocked && $date_awarded): ?>
                    <div class="achievement-date">Awarded: <?= $date_awarded ?></div>
                <?php elseif (!$is_unlocked): ?>
                    <div class="achievement-date">
                        Progress: <?= $user_progress ?>/<?= $achievement['requirement_value'] ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <img src="../../assets/images/empty-achievement.png" alt="No Achievements">
            <h3>No Achievements Available</h3>
            <p>Complete quizzes to unlock achievements!</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Achievement Details Modal -->
<div id="achievementModal" class="achievement-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Achievement Details</h3>
            <button class="close-modal" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Content will be dynamically inserted here -->
        </div>
    </div>
</div>

<script>
<?php if (isset($_SESSION['error'])): ?>
    alert("Error: <?php echo addslashes($_SESSION['error']); ?>");
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

document.addEventListener("DOMContentLoaded", function () {

    // Achievement category filtering
    const categoryButtons = document.querySelectorAll('.category-btn');
    const achievementCards = document.querySelectorAll('.achievement-card');
    
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            categoryButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            const category = this.dataset.category;
            
            // Filter achievement cards
            achievementCards.forEach(card => {
                if (category === 'all') {
                    card.style.display = 'flex';
                } else {
                    const cardCategory = card.dataset.category;
                    if (cardCategory === category) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        });
    });

    // Add click event to achievement cards
    document.querySelectorAll('.achievement-card').forEach(card => {
        card.addEventListener('click', function() {
            const achievementId = this.getAttribute('data-id');
            const isUnlocked = this.getAttribute('data-unlocked') === '1';
            const achievementName = this.getAttribute('data-name');
            const achievementDescription = this.getAttribute('data-description');
            const requirementValue = this.getAttribute('data-requirement');
            const imagePath = this.getAttribute('data-image');
            const achievementType = this.getAttribute('data-type');
            const userProgress = parseInt(this.getAttribute('data-progress'));
            
            showAchievementDetails({
                achievement_id: achievementId,
                name: achievementName,
                description: achievementDescription,
                requirement_value: parseInt(requirementValue),
                unlocked: isUnlocked,
                image: imagePath,
                type: achievementType
            }, userProgress);
        });
    });

    // Auto-hide new achievement notification after 5 seconds
    const notification = document.getElementById('newAchievementNotification');
    if (notification) {
        setTimeout(() => {
            notification.style.display = 'none';
        }, 5000);
    }
});

function showAchievementDetails(achievement, userProgress) {
    const modal = document.getElementById('achievementModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    
    const isUnlocked = achievement.unlocked;
    const progress = Math.min(100, (userProgress / achievement.requirement_value) * 100);
    const defaultImage = '../../assets/images/default-achievement.png';
    
    // Determine requirement text based on type
    let requirementText = '';
    let progressText = '';
    let unitText = '';
    
    switch(achievement.type) {
        case 'quizzes_completed':
            requirementText = `Complete ${achievement.requirement_value} quizzes`;
            progressText = `${userProgress}/${achievement.requirement_value} quizzes completed`;
            unitText = 'quiz';
            break;
        case 'login_days':
            requirementText = `Login for ${achievement.requirement_value} consecutive days`;
            progressText = `${userProgress}/${achievement.requirement_value} consecutive days`;
            unitText = 'day';
            break;
        default:
            requirementText = `Complete ${achievement.requirement_value} requirements`;
            progressText = `${userProgress}/${achievement.requirement_value} completed`;
            unitText = '';
    }
    
    // Determine which image to show in modal
    let modalImageHTML;
    if (isUnlocked) {
        modalImageHTML = `
            <div class="modal-image">
                <img src="${achievement.image}" 
                     alt="${achievement.name}" 
                     onerror="this.src='${defaultImage}'"
                     style="width: 120px; height: 120px; margin: 0 auto 15px; border-radius: 50%; object-fit: contain; display: block;">
            </div>
        `;
    } else {
        // Show lock icon for locked achievements
        modalImageHTML = `
            <div class="modal-image">
                <img src="../../assets/images/lock-icon.png" 
                     alt="Locked" 
                     style="width: 80px; height: 80px; margin: 0 auto 15px; padding: 20px; border-radius: 50%; background: #f8f9fa; object-fit: contain; display: block;">
            </div>
        `;
    }
    
    let statusHTML;
    if (isUnlocked) {
        statusHTML = `
            <span style="color: #27ae60; font-weight: bold;">
                ✓ Unlocked
            </span>
        `;
    } else {
        statusHTML = `
            <span style="color: #e74c3c; font-weight: bold; display: flex; align-items: center;">
                <img src="../../assets/images/lock-icon.png" 
                     alt="Locked" 
                     style="width: 14px; height: 14px; margin-right: 6px; vertical-align: middle;">
                Locked
            </span>
        `;
    }
    
    let modalHTML = modalImageHTML;
    
    modalHTML += `
        <h3 style="color: #2c3e50; margin-bottom: 12px; text-align: center; font-size: 18px;">${achievement.name}</h3>
        
        <p style="color: #7f8c8d; margin-bottom: 15px; text-align: center; font-size: 14px;">${achievement.description}</p>
        
        <div class="modal-details" style="background: #f9f9f9; padding: 12px; border-radius: 6px; margin-bottom: 15px;">
            <div class="detail-item" style="display: flex; justify-content: space-between; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #eee; font-size: 13px;">
                <span style="font-weight: bold;">Requirement:</span>
                <span>${requirementText}</span>
            </div>
            
            <div class="detail-item" style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                <span style="font-weight: bold;">Status:</span>
                ${statusHTML}
            </div>
        </div>
    `;
    
    if (!isUnlocked) {
        const remaining = achievement.requirement_value - userProgress;
        modalHTML += `
            <div class="progress-info" style="margin-top: 15px;">
                <p style="margin-bottom: 8px; font-size: 13px;">Your Progress: ${progressText}</p>
                <div style="margin-top: 8px; background: #ecf0f1; height: 8px; border-radius: 4px; overflow: hidden;">
                    <div style="width: ${progress}%; height: 100%; background: linear-gradient(90deg, #3498db, #2ecc71); transition: width 0.5s ease;"></div>
                </div>
                <p style="margin-top: 5px; font-size: 12px; color: #7f8c8d;">
                    ${remaining} more ${unitText}${remaining !== 1 ? 's' : ''} to unlock
                </p>
            </div>
        `;
    }
    
    modalTitle.textContent = achievement.name;
    modalBody.innerHTML = modalHTML;
    modal.style.display = 'flex';
    
    // Prevent scrolling of background
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const modal = document.getElementById('achievementModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal with escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});
</script>

<script src = '../../assets/js/main.js'></script>

</body>
</html>