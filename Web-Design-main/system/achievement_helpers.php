<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    require_once 'achievement_system.php';
} catch (Exception $e) {
    // Set error in session if session is active
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['error'] = "Failed to load achievement system: " . $e->getMessage();
    }
    // Return empty data to avoid fatal errors
    return [];
}

/* Award achievements after quiz completion */
function awardAchievementsAfterQuiz($conn, $user_id) {
    try {
        $achievementSystem = new AchievementSystem($conn, $user_id);
        $new_achievements = $achievementSystem->checkAndAwardByType('quizzes_completed');
        
        return $new_achievements ?? [];
    } catch (Exception $e) {
        // Set error in session if session is active
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['error'] = "Failed to award achievements: " . $e->getMessage();
        }
        return [];
    }
}

/* Award achievements after event participation */
function awardAchievementsAfterEvent($conn, $user_id) {
    try {
        $achievementSystem = new AchievementSystem($conn, $user_id);
        $new_achievements = $achievementSystem->checkAndAwardByType('event_participated');
        
        return $new_achievements ?? [];
    } catch (Exception $e) {
        // Set error in session if session is active
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['error'] = "Failed to award event achievements: " . $e->getMessage();
        }
        return [];
    }
}

/* Award achievements after daily login */
function awardAchievementsAfterLogin($conn, $user_id) {
    try {
        $achievementSystem = new AchievementSystem($conn, $user_id);
        $new_achievements = $achievementSystem->checkAndAwardByType('login_days');
        
        return $new_achievements ?? [];
    } catch (Exception $e) {
        // Set error in session if session is active
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['error'] = "Failed to award login achievements: " . $e->getMessage();
        }
        return [];
    }
}

/* Check all achievements for user */
function checkAllAchievements($conn, $user_id) {
    try {
        $achievementSystem = new AchievementSystem($conn, $user_id);
        $new_achievements = $achievementSystem->checkAndAwardAll();
        
        return $new_achievements ?? [];
    } catch (Exception $e) {
        // Set error in session if session is active
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['error'] = "Failed to check achievements: " . $e->getMessage();
        }
        return [];
    }
}

/* Get user's achievement progress for display */
function getUserAchievementData($conn, $user_id) {
    $default_data = [
        'achievements' => [],
        'unlocked_count' => 0,
        'total_count' => 0,
        'quiz_progress' => 0,
        'event_progress' => 0,
        'login_progress' => 0
    ];
    
    try {
        $achievementSystem = new AchievementSystem($conn, $user_id);
        
        if (!$achievementSystem) {
            throw new Exception("Failed to create achievement system");
        }
        
        $data = [
            'achievements' => $achievementSystem->getUserAchievementsWithDetails() ?? [],
            'unlocked_count' => $achievementSystem->getUnlockedCount() ?? 0,
            'total_count' => $achievementSystem->getTotalAchievementsCount() ?? 0,
            'quiz_progress' => $achievementSystem->getUserProgress('quizzes_completed') ?? 0,
            'event_progress' => $achievementSystem->getUserProgress('event_participated') ?? 0,
            'login_progress' => $achievementSystem->getUserProgress('login_days') ?? 0
        ];
        
        return $data;
    } catch (Exception $e) {
        // Set error in session if session is active
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['error'] = "Failed to get achievement data: " . $e->getMessage();
        }
        return $default_data;
    }
}

/* Display achievement notification */
function displayAchievementNotification($new_achievements) {
    if (empty($new_achievements)) {
        return '';
    }
    
    try {
        $achievement_names = array_map(function($ach) {
            if (is_array($ach) && isset($ach['name'])) {
                return $ach['name'];
            } elseif (is_string($ach)) {
                return $ach;
            }
            return '';
        }, $new_achievements);
        
        // Filter out empty names
        $achievement_names = array_filter($achievement_names);
        
        if (empty($achievement_names)) {
            return '';
        }
        
        $notification = "
        <div id='newAchievementNotification' class='new-achievement-notification'>
            <div class='notification-icon'>
                <img src='../../assets/images/trophy-icon.png' alt='Trophy'>
            </div>
            <div class='notification-content'>
                <h4>New Achievement" . (count($new_achievements) > 1 ? 's' : '') . " Unlocked!</h4>
                <p>You've unlocked: " . htmlspecialchars(implode(', ', $achievement_names)) . "</p>
            </div>
        </div>
        <script>
            setTimeout(() => {
                const notification = document.getElementById('newAchievementNotification');
                if (notification) {
                    notification.style.display = 'none';
                }
            }, 5000);
        </script>";
        
        return $notification;
    } catch (Exception $e) {
        // Set error in session if session is active
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['error'] = "Failed to display achievement notification: " . $e->getMessage();
        }
        return '';
    }
}