<?php
/**
 * Password Management for AI-Solutions Admin Panel
 */

session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$page_title = "Change Password";

// Current admin user
$admin_username = $_SESSION['admin_username'];
$admin_id = $_SESSION['admin_id'] ?? 0;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate input
    if (empty($current_password)) {
        set_flash_message('danger', 'Please enter your current password.');
    } elseif (empty($new_password)) {
        set_flash_message('danger', 'Please enter a new password.');
    } elseif (empty($confirm_password)) {
        set_flash_message('danger', 'Please confirm your new password.');
    } elseif ($new_password !== $confirm_password) {
        set_flash_message('danger', 'New password and confirmation do not match.');
    } elseif (strlen($new_password) < 8) {
        set_flash_message('danger', 'Password must be at least 8 characters long.');
    } else {
        try {
            // Get current password hash
            $sql = "SELECT password FROM users WHERE username = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$admin_username]);
            $user = $stmt->fetch();

            if ($user) {
                $stored_hash = $user['password'];

                // Verify current password
                if (password_verify($current_password, $stored_hash) || md5($current_password) === $stored_hash) {
                    // Generate new password hash
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

                    // Update password
                    $update_sql = "UPDATE users SET password = ? WHERE username = ?";
                    $update_stmt = $pdo->prepare($update_sql);
                    $update_stmt->execute([$new_hash, $admin_username]);

                    set_flash_message('success', 'Password has been updated successfully.');
                } else {
                    set_flash_message('danger', 'Current password is incorrect.');
                }
            } else {
                set_flash_message('danger', 'User account not found.');
            }
        } catch (PDOException $e) {
            set_flash_message('danger', 'Database error: ' . $e->getMessage());
        }
    }
}

include 'includes/header.php';
?>

<div class="admin-form">
    <h3>Update Your Password</h3>
    <form action="" method="POST" id="password-form">
        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" class="form-control" minlength="8" required>
            <div class="password-strength mt-2" id="password-strength">
                <div class="strength-bar">
                    <div class="strength-indicator" id="strength-indicator"></div>
                </div>
                <div class="strength-text" id="strength-text">Password strength</div>
            </div>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="8" required>
            <div id="password-match" class="mt-2"></div>
        </div>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                Update Password
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>
    </form>
</div>

<div class="admin-form">
    <h3>Password Guidelines</h3>
    <p>For a strong password, include:</p>
    <ul>
        <li>At least 8 characters</li>
        <li>Uppercase letters (A-Z)</li>
        <li>Lowercase letters (a-z)</li>
        <li>Numbers (0-9)</li>
        <li>Special characters (!@#$%^&*)</li>
    </ul>
    <div class="flash-message warning">
        <i class="fas fa-exclamation-triangle"></i> Never share your password with anyone else!
    </div>
</div>

<style>
.password-strength {
    margin-top: 10px;
}

.strength-bar {
    height: 5px;
    background-color: #e9ecef;
    border-radius: 5px;
    margin-bottom: 5px;
}

.strength-indicator {
    height: 100%;
    width: 0;
    border-radius: 5px;
    transition: width 0.3s, background-color 0.3s;
}

.strength-text {
    font-size: 12px;
    color: #6c757d;
}

.very-weak { background-color: #dc3545; width: 20%; }
.weak { background-color: #fd7e14; width: 40%; }
.medium { background-color: #ffc107; width: 60%; }
.strong { background-color: #20c997; width: 80%; }
.very-strong { background-color: #198754; width: 100%; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password match validation
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const passwordMatch = document.getElementById('password-match');

    function checkPasswordMatch() {
        if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
            passwordMatch.innerHTML = '<div style="color: var(--danger-color);">Passwords do not match</div>';
            confirmPassword.setCustomValidity('Passwords do not match');
        } else {
            passwordMatch.innerHTML = confirmPassword.value ? '<div style="color: var(--success-color);">Passwords match</div>' : '';
            confirmPassword.setCustomValidity('');
        }
    }

    newPassword.addEventListener('input', checkPasswordMatch);
    confirmPassword.addEventListener('input', checkPasswordMatch);

    // Password strength meter
    const strengthIndicator = document.getElementById('strength-indicator');
    const strengthText = document.getElementById('strength-text');

    function checkPasswordStrength(password) {
        let strength = 0;

        if (password.length >= 8) strength += 1;
        if (password.match(/[a-z]+/)) strength += 1;
        if (password.match(/[A-Z]+/)) strength += 1;
        if (password.match(/[0-9]+/)) strength += 1;
        if (password.match(/[^a-zA-Z0-9]+/)) strength += 1;

        switch (strength) {
            case 0:
            case 1:
                strengthIndicator.className = 'strength-indicator very-weak';
                strengthText.textContent = 'Very Weak';
                break;
            case 2:
                strengthIndicator.className = 'strength-indicator weak';
                strengthText.textContent = 'Weak';
                break;
            case 3:
                strengthIndicator.className = 'strength-indicator medium';
                strengthText.textContent = 'Medium';
                break;
            case 4:
                strengthIndicator.className = 'strength-indicator strong';
                strengthText.textContent = 'Strong';
                break;
            case 5:
                strengthIndicator.className = 'strength-indicator very-strong';
                strengthText.textContent = 'Very Strong';
                break;
        }
    }

    newPassword.addEventListener('input', function() {
        checkPasswordStrength(this.value);
    });
});
</script>

<?php include 'includes/footer.php'; ?>