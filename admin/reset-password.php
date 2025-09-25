<?php
/**
 * Reset Password Page
 * Handles password reset with token validation
 */

session_start();
require_once 'includes/config.php';

// Check if user is already logged in
if (is_logged_in()) {
    redirect('index.php');
}

$success_message = '';
$error_message = '';
$token = $_GET['token'] ?? '';

// Validate token
if (empty($token)) {
    $error_message = "Invalid or missing reset token.";
    $show_form = false;
} else {
    try {
        // Check if token exists and is valid
        $sql = "SELECT pr.*, u.username, u.email 
                FROM password_resets pr 
                JOIN users u ON pr.user_id = u.id 
                WHERE pr.token = :token AND pr.expires_at > NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            $reset_data = $stmt->fetch();
            $show_form = true;
        } else {
            $error_message = "Invalid or expired reset token. Please request a new password reset.";
            $show_form = false;
        }
    } catch (PDOException $e) {
        error_log("Reset password token validation error: " . $e->getMessage());
        $error_message = "An error occurred. Please try again later.";
        $show_form = false;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $show_form) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($new_password) || empty($confirm_password)) {
        $error_message = "Please fill in all fields.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } else {
        try {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :user_id";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
            $update_stmt->bindParam(':user_id', $reset_data['user_id'], PDO::PARAM_INT);
            $update_stmt->execute();

            // Delete used reset token
            $delete_sql = "DELETE FROM password_resets WHERE token = :token";
            $delete_stmt = $pdo->prepare($delete_sql);
            $delete_stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $delete_stmt->execute();

            // Log the activity
            $logSql = "INSERT INTO activity_log (user_id, activity, ip_address)
                      VALUES (:user_id, :activity, :ip_address)";
            $logStmt = $pdo->prepare($logSql);
            $activity = "Password reset completed";
            $logStmt->bindParam(':user_id', $reset_data['user_id'], PDO::PARAM_INT);
            $logStmt->bindParam(':activity', $activity, PDO::PARAM_STR);
            $logStmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
            $logStmt->execute();

            $success_message = "Password has been reset successfully! You can now log in with your new password.";
            $show_form = false;

        } catch (PDOException $e) {
            error_log("Password reset error: " . $e->getMessage());
            $error_message = "An error occurred while resetting your password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - AI-Solutions Admin</title>

    <!-- CSS Files -->
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="css/admin.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" href="../images/logo.png" type="image/png">

    <style>
        body {
            background-color: var(--gray-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .reset-password-container {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 50rem;
            padding: 4rem;
        }

        .reset-password-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .reset-password-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .reset-password-logo img {
            height: 6rem;
            width: auto;
        }

        .reset-password-title {
            font-size: 2.8rem;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }

        .reset-password-subtitle {
            font-size: 1.6rem;
            color: var(--gray-color);
            line-height: 1.6;
        }

        .user-info {
            background-color: var(--body-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .user-info h4 {
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .user-info p {
            color: var(--gray-color);
            font-size: 1.4rem;
        }

        .alert {
            padding: 1.5rem 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(50, 205, 50, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .alert-error {
            background-color: rgba(220, 20, 60, 0.1);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }

        .reset-password-form .form-group {
            margin-bottom: 2rem;
        }

        .reset-password-form label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .reset-password-form .input-group {
            position: relative;
        }

        .reset-password-form .input-group i {
            position: absolute;
            top: 50%;
            left: 1.5rem;
            transform: translateY(-50%);
            color: var(--gray-color);
        }

        .reset-password-form input {
            width: 100%;
            padding: 1.2rem 1.2rem 1.2rem 4rem;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1.6rem;
        }

        .reset-password-form input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 1.2rem;
        }

        .password-strength.weak {
            color: var(--danger-color);
        }

        .password-strength.medium {
            color: var(--warning-color);
        }

        .password-strength.strong {
            color: var(--success-color);
        }

        .reset-password-form button {
            width: 100%;
            padding: 1.2rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--dark-color);
            border: none;
            border-radius: var(--border-radius);
            font-size: 1.6rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .reset-password-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(255, 215, 0, 0.3);
        }

        .reset-password-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-light);
        }

        .reset-password-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .reset-password-footer a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .reset-password-container {
                padding: 2rem;
            }
            
            .reset-password-title {
                font-size: 2.4rem;
            }
        }
    </style>
</head>
<body>
    <div class="reset-password-container">
        <div class="reset-password-header">
            <div class="reset-password-logo">
                <img src="../images/logo.png" alt="AI-Solutions Logo">
            </div>
            <h1 class="reset-password-title">Reset Password</h1>
            <p class="reset-password-subtitle">Enter your new password below</p>
        </div>

        <?php if (isset($reset_data) && $show_form): ?>
            <div class="user-info">
                <h4>Resetting password for:</h4>
                <p><?php echo htmlspecialchars($reset_data['username']); ?> (<?php echo htmlspecialchars($reset_data['email']); ?>)</p>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($show_form): ?>
            <form class="reset-password-form" method="POST" id="resetForm">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="new_password" name="new_password" 
                               placeholder="Enter your new password" required minlength="6">
                    </div>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm your new password" required minlength="6">
                    </div>
                </div>

                <button type="submit">
                    <i class="fas fa-save"></i>
                    Reset Password
                </button>
            </form>
        <?php endif; ?>

        <div class="reset-password-footer">
            <?php if (!$show_form): ?>
                <p><a href="forgot-password.php">Request a new password reset</a></p>
            <?php endif; ?>
            <p>Remember your password? <a href="login.php">Sign in here</a></p>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/main.js"></script>
    <script>
        // Password strength checker
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            let strength = 0;
            let strengthText = '';
            
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            switch (strength) {
                case 0:
                case 1:
                    strengthText = 'Weak';
                    strengthDiv.className = 'password-strength weak';
                    break;
                case 2:
                case 3:
                    strengthText = 'Medium';
                    strengthDiv.className = 'password-strength medium';
                    break;
                case 4:
                case 5:
                    strengthText = 'Strong';
                    strengthDiv.className = 'password-strength strong';
                    break;
            }
            
            strengthDiv.textContent = strengthText ? 'Password strength: ' + strengthText : '';
        });

        // Form validation
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                alert('Passwords do not match');
                e.preventDefault();
                return;
            }
            
            if (newPassword.length < 6) {
                alert('Password must be at least 6 characters long');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>
