<?php
/**
 * Admin Profile Management
 */

session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['admin_id'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    try {
        // Get current user data
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user) {
            throw new Exception("User not found");
        }

        // Validate current password if trying to change password
        if (!empty($new_password)) {
            if (empty($current_password)) {
                throw new Exception("Current password is required to change password");
            }

            if (!password_verify($current_password, $user['password'])) {
                throw new Exception("Current password is incorrect");
            }

            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match");
            }

            if (strlen($new_password) < 6) {
                throw new Exception("New password must be at least 6 characters long");
            }
        }

        // Check if username or email already exists (excluding current user)
        $checkSql = "SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->bindParam(':username', $username, PDO::PARAM_STR);
        $checkStmt->bindParam(':email', $email, PDO::PARAM_STR);
        $checkStmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            throw new Exception("Username or email already exists");
        }

        // Update user profile
        if (!empty($new_password)) {
            // Update with new password
            $updateSql = "UPDATE users SET username = :username, email = :email, password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $updateStmt = $pdo->prepare($updateSql);
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $updateStmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
        } else {
            // Update without password
            $updateSql = "UPDATE users SET username = :username, email = :email, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $updateStmt = $pdo->prepare($updateSql);
        }

        $updateStmt->bindParam(':username', $username, PDO::PARAM_STR);
        $updateStmt->bindParam(':email', $email, PDO::PARAM_STR);
        $updateStmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $updateStmt->execute();

        // Update session variables
        $_SESSION['admin_username'] = $username;

        // Log the activity
        $logSql = "INSERT INTO activity_log (user_id, activity, ip_address)
                  VALUES (:user_id, :activity, :ip_address)";
        $logStmt = $pdo->prepare($logSql);
        $activity = "Profile updated";
        $logStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $logStmt->bindParam(':activity', $activity, PDO::PARAM_STR);
        $logStmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
        $logStmt->execute();

        $success_message = "Profile updated successfully!";

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get current user data
try {
    $sql = "SELECT * FROM users WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $error_message = "Error loading user data";
    $user = null;
}

$page_title = "Profile";
include 'includes/header.php';
?>

<div class="profile-container">
    <div class="profile-header">
        <h2>Profile Management</h2>
        <p>Manage your account settings and personal information</p>
    </div>

    <div class="profile-content">
        <div class="profile-card">
            <div class="profile-avatar-large">
                <div class="avatar-circle">
                    <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                </div>
                <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                <p class="role-badge"><?php echo ucfirst($user['role']); ?></p>
            </div>

            <div class="profile-form">
                <?php if ($success_message): ?>
                    <div class="flash-message success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="flash-message error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="POST" id="profileForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="role">Role</label>
                            <input type="text" id="role" value="<?php echo ucfirst($user['role']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="created_at">Member Since</label>
                            <input type="text" id="created_at" value="<?php echo format_date($user['created_at']); ?>" readonly>
                        </div>
                    </div>

                    <div class="password-section">
                        <h4>Change Password</h4>
                        <p class="form-note">Leave blank to keep current password</p>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" minlength="6">
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" minlength="6">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Update Profile
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="activity-card">
            <h4>Recent Activity</h4>
            <div class="activity-list">
                <?php
                try {
                    $activitySql = "SELECT * FROM activity_log WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 10";
                    $activityStmt = $pdo->prepare($activitySql);
                    $activityStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $activityStmt->execute();
                    $activities = $activityStmt->fetchAll();

                    if (empty($activities)) {
                        echo "<p class='no-data'>No recent activity</p>";
                    } else {
                        foreach ($activities as $activity) {
                            echo "<div class='activity-item'>";
                            echo "<div class='activity-icon'>";
                            echo "<i class='fas fa-circle'></i>";
                            echo "</div>";
                            echo "<div class='activity-content'>";
                            echo "<p class='activity-text'>{$activity['activity']}</p>";
                            echo "<span class='activity-time'>" . format_date($activity['created_at'], 'M d, Y H:i') . "</span>";
                            echo "</div>";
                            echo "</div>";
                        }
                    }
                } catch (PDOException $e) {
                    echo "<p class='no-data'>Error loading activity</p>";
                }
                ?>
            </div>
        </div>
    </div>
</div>

<style>
.profile-container {
    max-width: 120rem;
    margin: 0 auto;
}

.profile-header {
    margin-bottom: 3rem;
}

.profile-header h2 {
    font-size: 2.8rem;
    margin-bottom: 0.5rem;
}

.profile-header p {
    color: var(--gray-color);
    font-size: 1.6rem;
}

.profile-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 3rem;
}

.profile-card {
    background-color: var(--light-color);
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 3rem;
}

.profile-avatar-large {
    text-align: center;
    margin-bottom: 3rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--gray-light);
}

.avatar-circle {
    width: 8rem;
    height: 8rem;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 2.4rem;
    font-weight: 700;
    color: var(--dark-color);
}

.profile-avatar-large h3 {
    margin-bottom: 0.5rem;
}

.role-badge {
    display: inline-block;
    background-color: var(--primary-color);
    color: var(--dark-color);
    padding: 0.5rem 1rem;
    border-radius: 1.5rem;
    font-size: 1.2rem;
    font-weight: 600;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 0.8rem;
    color: var(--dark-color);
}

.form-group input {
    padding: 1.2rem;
    border: 1px solid var(--gray-light);
    border-radius: var(--border-radius);
    font-size: 1.4rem;
    transition: var(--transition);
}

.form-group input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
}

.form-group input[readonly] {
    background-color: var(--gray-light);
    color: var(--gray-color);
}

.password-section {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid var(--gray-light);
}

.password-section h4 {
    margin-bottom: 1rem;
}

.form-note {
    color: var(--gray-color);
    font-size: 1.4rem;
    margin-bottom: 2rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid var(--gray-light);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.8rem;
    padding: 1.2rem 2rem;
    border: none;
    border-radius: var(--border-radius);
    font-size: 1.4rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: var(--transition);
}

.btn-primary {
    background-color: var(--primary-color);
    color: var(--dark-color);
}

.btn-primary:hover {
    background-color: var(--secondary-color);
}

.btn-secondary {
    background-color: var(--gray-light);
    color: var(--dark-color);
}

.btn-secondary:hover {
    background-color: var(--gray-color);
}

.activity-card {
    background-color: var(--light-color);
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 2rem;
    height: fit-content;
}

.activity-card h4 {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--gray-light);
}

.activity-list {
    max-height: 40rem;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid var(--gray-light);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    margin-top: 0.5rem;
}

.activity-icon i {
    font-size: 0.8rem;
    color: var(--primary-color);
}

.activity-content {
    flex: 1;
}

.activity-text {
    font-size: 1.4rem;
    margin-bottom: 0.5rem;
}

.activity-time {
    font-size: 1.2rem;
    color: var(--gray-color);
}

.no-data {
    text-align: center;
    color: var(--gray-color);
    font-style: italic;
    padding: 2rem;
}

@media (max-width: 768px) {
    .profile-content {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
