<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once '../../config/db.php';

class AchievementSystem {
    private $conn;
    private $user_id;
    private $newly_awarded = [];
    
    public function __construct($conn, $user_id) {
        if (!$conn) {
            throw new Exception("Database connection is required");
        }
        if (!$user_id || $user_id <= 0) {
            throw new Exception("Valid user ID is required");
        }
        
        $this->conn = $conn;
        $this->user_id = $user_id;
    }
    
    /* Main function to check and award all achievements */
    public function checkAndAwardAll() {
        try {
            // Get all achievements from database
            $all_achievements = $this->getAllAchievements();
            
            if (empty($all_achievements)) {
                return [];
            }
            
            // Get user's already awarded achievements
            $user_awarded = $this->getUserAwardedAchievements();
            
            // Process each achievement type
            $eligible_achievements = [];
            
            foreach ($all_achievements as $achievement) {
                // Check if user already has this achievement
                if (isset($user_awarded[$achievement['achievement_id']])) {
                    continue;
                }
                
                // Check if user meets requirements for this achievement
                if ($this->checkAchievementRequirements($achievement)) {
                    $eligible_achievements[] = $achievement;
                }
            }
            
            // Award eligible achievements
            return $this->awardAchievements($eligible_achievements);
            
        } catch (Exception $e) {
            error_log("Achievement System Error: " . $e->getMessage());
            // Set session error if session is active
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['error'] = "Failed to check achievements: " . $e->getMessage();
            }
            return [];
        }
    }
    
    /* Check specific achievement type (can be called from specific events) */
    public function checkAndAwardByType($requirement_type) {
        try {
            // Get achievements by type
            $achievements = $this->getAchievementsByType($requirement_type);
            
            if (empty($achievements)) {
                return [];
            }
            
            // Get user's already awarded achievements
            $user_awarded = $this->getUserAwardedAchievements();
            
            $eligible_achievements = [];
            
            foreach ($achievements as $achievement) {
                // Check if user already has this achievement
                if (isset($user_awarded[$achievement['achievement_id']])) {
                    continue;
                }
                
                // Check if user meets requirements for this achievement
                if ($this->checkAchievementRequirements($achievement)) {
                    $eligible_achievements[] = $achievement;
                }
            }
            
            // Award eligible achievements
            return $this->awardAchievements($eligible_achievements);
            
        } catch (Exception $e) {
            error_log("Achievement System Error: " . $e->getMessage());
            // Set session error if session is active
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['error'] = "Failed to check achievements by type: " . $e->getMessage();
            }
            return [];
        }
    }
    
    /* Get all achievements from database */
    private function getAllAchievements() {
        try {
            $query = "SELECT * FROM achievement ORDER BY requirement_type, requirement_value ASC";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $achievements = [];
            while ($row = $result->fetch_assoc()) {
                $achievements[] = $row;
            }
            $stmt->close();
            
            return $achievements;
        } catch (Exception $e) {
            throw new Exception("Failed to get all achievements: " . $e->getMessage());
        }
    }
    
    /* Get achievements by specific type */
    private function getAchievementsByType($requirement_type) {
        try {
            $query = "SELECT * FROM achievement WHERE requirement_type = ? ORDER BY requirement_value ASC";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }
            $stmt->bind_param("s", $requirement_type);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $achievements = [];
            while ($row = $result->fetch_assoc()) {
                $achievements[] = $row;
            }
            $stmt->close();
            
            return $achievements;
        } catch (Exception $e) {
            throw new Exception("Failed to get achievements by type: " . $e->getMessage());
        }
    }
    
    /* Get user's already awarded achievements */
    private function getUserAwardedAchievements() {
        try {
            $query = "SELECT achievement_id FROM user_achievement WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }
            $stmt->bind_param("i", $this->user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $awarded = [];
            while ($row = $result->fetch_assoc()) {
                $awarded[$row['achievement_id']] = true;
            }
            $stmt->close();
            
            return $awarded;
        } catch (Exception $e) {
            throw new Exception("Failed to get user awarded achievements: " . $e->getMessage());
        }
    }
    
    /* Check if user meets requirements for a specific achievement */
    private function checkAchievementRequirements($achievement) {
        try {
            switch ($achievement['requirement_type']) {
                case 'quizzes_completed':
                    return $this->checkQuizAchievement($achievement);
                    
                case 'event_participated':
                    return $this->checkEventAchievement($achievement);
                    
                case 'login_days':
                    return $this->checkLoginAchievement($achievement);
                    
                default:
                    return false;
            }
        } catch (Exception $e) {
            throw new Exception("Failed to check achievement requirements: " . $e->getMessage());
        }
    }
    
    /* Check quiz completion achievements */
    private function checkQuizAchievement($achievement) {
        try {
            $query = "SELECT COUNT(*) as completed_count FROM quiz_attempt 
                     WHERE user_id = ? AND quiz_completed = 'Completed'";
            
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }
            $stmt->bind_param("i", $this->user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            
            $completed_count = $data['completed_count'] ?? 0;
            
            // Check if user meets or exceeds requirement
            return $completed_count >= $achievement['requirement_value'];
        } catch (Exception $e) {
            throw new Exception("Failed to check quiz achievement: " . $e->getMessage());
        }
    }
    
    /* Check event participation achievements */
    private function checkEventAchievement($achievement) {
        try {
            // Count how many events the user has attended with status = 'True'
            $attended_events_count = $this->getAttendedEventsCount();
            
            // Check if user meets or exceeds requirement
            return $attended_events_count >= $achievement['requirement_value'];
        } catch (Exception $e) {
            throw new Exception("Failed to check event achievement: " . $e->getMessage());
        }
    }
    
    /* Get count of events attended by user */
    private function getAttendedEventsCount() {
        try {
            // Assuming the attendance table has columns: user_id, status (where 'True' means attended)
            $query = "SELECT COUNT(*) as attended_count FROM attendance 
                     WHERE user_id = ? AND status = 'True'";
            
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }
            $stmt->bind_param("i", $this->user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            
            return $data['attended_count'] ?? 0;
        } catch (Exception $e) {
            throw new Exception("Failed to get attended events count: " . $e->getMessage());
        }
    }
    
    /* Check login days achievements */
    private function checkLoginAchievement($achievement) {
        try {
            // Include the login streak helper
            if (!file_exists(__DIR__ . '/login_streak_helper.php')) {
                throw new Exception("Login streak helper not found");
            }
            
            require_once __DIR__ . '/login_streak_helper.php';
            
            // Get user's current login streak
            $current_streak = getStudentLoginStreak($this->conn, $this->user_id);
            
            // Check if user meets or exceeds requirement
            return $current_streak >= $achievement['requirement_value'];
        } catch (Exception $e) {
            throw new Exception("Failed to check login achievement: " . $e->getMessage());
        }
    }
    
    /* Award achievements to user */
    private function awardAchievements($eligible_achievements) {
        $newly_awarded = [];
        
        foreach ($eligible_achievements as $achievement) {
            try {
                if ($this->awardAchievement($achievement)) {
                    $newly_awarded[] = [
                        'id' => $achievement['achievement_id'],
                        'name' => $achievement['name']
                    ];
                }
            } catch (Exception $e) {
                error_log("Failed to award achievement: " . $e->getMessage());
                // Continue with next achievement even if one fails
                continue;
            }
        }
        
        return $newly_awarded;
    }
    
    /* Award single achievement to user */
    private function awardAchievement($achievement) {
        try {
            $query = "INSERT INTO user_achievement (achievement_id, user_id, date_awarded) 
                     VALUES (?, ?, NOW())";
            
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }
            
            $stmt->bind_param("ii", $achievement['achievement_id'], $this->user_id);
            
            if ($stmt->execute()) {
                $stmt->close();
                return true;
            } else {
                $stmt->close();
                throw new Exception("Failed to execute award query: " . $this->conn->error);
            }
            
        } catch (Exception $e) {
            throw new Exception("Error awarding achievement: " . $e->getMessage());
        }
    }
    
    /* Get user's progress for a specific achievement type */
    public function getUserProgress($requirement_type) {
        try {
            switch ($requirement_type) {
                case 'quizzes_completed':
                    return $this->getQuizProgress();
                    
                case 'event_participated':
                    return $this->getEventProgress();
                    
                case 'login_days':
                    return $this->getLoginProgress();
                    
                default:
                    return 0;
            }
        } catch (Exception $e) {
            error_log("Failed to get user progress: " . $e->getMessage());
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['error'] = "Failed to get progress: " . $e->getMessage();
            }
            return 0;
        }
    }
    
    /* Get user's quiz completion progress */
    private function getQuizProgress() {
        try {
            $query = "SELECT COUNT(*) as completed_count FROM quiz_attempt 
                     WHERE user_id = ? AND quiz_completed = 'Completed'";
            
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }
            $stmt->bind_param("i", $this->user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            
            return $data['completed_count'] ?? 0;
        } catch (Exception $e) {
            throw new Exception("Failed to get quiz progress: " . $e->getMessage());
        }
    }
    
    /* Get user's event participation progress */
    private function getEventProgress() {
        try {
            // To be implemented
            return 0;
        } catch (Exception $e) {
            throw new Exception("Failed to get event progress: " . $e->getMessage());
        }
    }
    
    /* Get user's login days progress */
    private function getLoginProgress() {
        try {
            // Include the login streak helper
            if (!file_exists(__DIR__ . '/login_streak_helper.php')) {
                throw new Exception("Login streak helper not found");
            }
            
            require_once __DIR__ . '/login_streak_helper.php';
            
            // Get user's current login streak
            return getStudentLoginStreak($this->conn, $this->user_id);
        } catch (Exception $e) {
            throw new Exception("Failed to get login progress: " . $e->getMessage());
        }
    }
    
    /* Get all user's achievements with details */
    public function getUserAchievementsWithDetails() {
        try {
            $query = "
                SELECT 
                    a.*,
                    ua.date_awarded,
                    CASE WHEN ua.user_achievement_id IS NOT NULL THEN 1 ELSE 0 END as unlocked
                FROM achievement a
                LEFT JOIN user_achievement ua ON a.achievement_id = ua.achievement_id AND ua.user_id = ?
                ORDER BY a.requirement_type, a.requirement_value ASC
            ";
            
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }
            $stmt->bind_param("i", $this->user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $achievements = [];
            while ($row = $result->fetch_assoc()) {
                $achievements[] = $row;
            }
            $stmt->close();
            
            return $achievements;
        } catch (Exception $e) {
            error_log("Failed to get user achievements with details: " . $e->getMessage());
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['error'] = "Failed to load achievements: " . $e->getMessage();
            }
            return [];
        }
    }
    
    /* Get user's unlocked achievements count */
    public function getUnlockedCount() {
        try {
            $query = "SELECT COUNT(*) as unlocked_count FROM user_achievement WHERE user_id = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }
            $stmt->bind_param("i", $this->user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            
            return $data['unlocked_count'] ?? 0;
        } catch (Exception $e) {
            error_log("Failed to get unlocked count: " . $e->getMessage());
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['error'] = "Failed to get achievement count: " . $e->getMessage();
            }
            return 0;
        }
    }
    
    /* Get total achievements count */
    public function getTotalAchievementsCount() {
        try {
            $query = "SELECT COUNT(*) as total_count FROM achievement";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $this->conn->error);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            
            return $data['total_count'] ?? 0;
        } catch (Exception $e) {
            error_log("Failed to get total achievements count: " . $e->getMessage());
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['error'] = "Failed to get total achievements: " . $e->getMessage();
            }
            return 0;
        }
    }
}
?>