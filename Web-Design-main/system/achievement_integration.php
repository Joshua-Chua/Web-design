<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function handleQuizCompletion($conn, $user_id, $quiz_id) {
    try {
        require_once 'achievement_helpers.php';
        $new_achievements = awardAchievementsAfterQuiz($conn, $user_id);
        
        // Store the new achievements in session to show notification
        if (!empty($new_achievements)) {
            if (!isset($_SESSION)) {
                session_start();
            }
            $_SESSION['new_achievements'] = $new_achievements;
        }
        
        return $new_achievements;
    } catch (Exception $e) {
        // Set error in session if session is active
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['error'] = "Failed to handle quiz completion: " . $e->getMessage();
        return [];
    }
}

function displayAchievementsPage($conn, $user_id) {
    $default_data = [
        'data' => [
            'achievements' => [],
            'unlocked_count' => 0,
            'total_count' => 0,
            'quiz_progress' => 0,
            'event_progress' => 0,
            'login_progress' => 0
        ],
        'notification' => ''
    ];
    
    try {
        require_once 'achievement_helpers.php';
        
        // Get all achievement data for display
        $achievement_data = getUserAchievementData($conn, $user_id);
        
        if (!$achievement_data || !is_array($achievement_data)) {
            throw new Exception("Invalid achievement data returned");
        }
        
        // Check if there are new achievements in session
        $notification = '';
        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (isset($_SESSION['new_achievements']) && !empty($_SESSION['new_achievements'])) {
            $notification = displayAchievementNotification($_SESSION['new_achievements']);
            unset($_SESSION['new_achievements']);
        }
        
        // Use the data to render your achievements page
        return [
            'data' => $achievement_data,
            'notification' => $notification
        ];
    } catch (Exception $e) {
        // Set error in session if session is active
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['error'] = "Failed to display achievements page: " . $e->getMessage();
        return $default_data;
    }
}

function runAchievementCronJob() {
    $log = [];
    
    try {
        require_once '../../config/db.php';
        require_once 'achievement_helpers.php';
        
        if (!isset($conn) || !$conn) {
            throw new Exception("Database connection not available");
        }
        
        // Get all users
        $query = "SELECT user_id FROM users WHERE active = 1";
        $result = $conn->query($query);
        
        if (!$result) {
            throw new Exception("Failed to fetch users: " . $conn->error);
        }
        
        while ($row = $result->fetch_assoc()) {
            $user_id = $row['user_id'];
            try {
                $new_achievements = checkAllAchievements($conn, $user_id);
                
                if (!empty($new_achievements)) {
                    $achievement_names = [];
                    foreach ($new_achievements as $ach) {
                        if (is_array($ach) && isset($ach['name'])) {
                            $achievement_names[] = $ach['name'];
                        } elseif (is_string($ach)) {
                            $achievement_names[] = $ach;
                        }
                    }
                    if (!empty($achievement_names)) {
                        $log[] = "User $user_id earned: " . implode(', ', $achievement_names);
                    }
                }
            } catch (Exception $user_error) {
                $log[] = "Error processing user $user_id: " . $user_error->getMessage();
                continue; // Continue with next user even if one fails
            }
        }
        
        // Close result set if it exists
        if (isset($result)) {
            $result->free();
        }
        
        return $log;
    } catch (Exception $e) {
        // Log the main error
        $log[] = "Cron job failed: " . $e->getMessage();
        
        // If session is available, set error
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['error'] = "Cron job error: " . $e->getMessage();
        }
        
        return $log;
    }
}
?>