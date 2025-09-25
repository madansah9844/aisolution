<?php
/**
 * Forgot Password Page
 * Handles password reset requests (conditional on PHPMailer availability)
 */

session_start();
require_once 'includes/config.php';

// Check if user is already logged in
if (is_logged_in()) {
    redirect('index.php');
}

$success_message = '';
$error_message = '';
$show_form = true;

// Check if PHPMailer is available (for online hosting)
$phpmailer_available = false;
if (class_exists('PHPMailer\PHPMailer\PHPMailer') || class_exists('PHPMailer')) {
    $phpmailer_available = true;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_input($_POST['email']);
    
    if (empty($email)) {
        $error_message = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            // Check if email exists in database
            $sql = "SELECT id, username, email FROM users WHERE email = :email LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch();
                
                if ($phpmailer_available) {
                    // Generate reset token
                    $reset_token = bin2hex(random_bytes(32));
                    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Store reset token in database
                    $token_sql = "INSERT INTO password_resets (user_id, token, expires_at) 
                                  VALUES (:user_id, :token, :expires_at)
                                  ON DUPLICATE KEY UPDATE token = :token, expires_at = :expires_at";
                    $token_stmt = $pdo->prepare($token_sql);
                    $token_stmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
                    $token_stmt->bindParam(':token', $reset_token, PDO::PARAM_STR);
                    $token_stmt->bindParam(':expires_at', $expires_at, PDO::PARAM_STR);
                    $token_stmt->execute();
                    
                    // Send reset email
                    try {
                        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                        
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com'; // Change to your SMTP server
                        $mail->SMTPAuth = true;
                        $mail->Username = 'your-email@gmail.com'; // Change to your email
                        $mail->Password = 'your-app-password'; // Change to your app password
                        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;
                        
                        // Recipients
                        $mail->setFrom('noreply@ai-solutions.com', 'AI-Solutions');
                        $mail->addAddress($email, $user['username']);
                        
                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Password Reset Request - AI-Solutions';
                        $mail->Body = "
                            <h2>Password Reset Request</h2>
                            <p>Hello {$user['username']},</p>
                            <p>You have requested to reset your password. Click the link below to reset your password:</p>
                            <p><a href='" . get_base_url() . "admin/reset-password.php?token={$reset_token}' style='background: #FFD700; color: #2F2F2F; padding: 1rem 2rem; text-decoration: none; border-radius: 0.5rem;'>Reset Password</a></p>
                            <p>This link will expire in 1 hour.</p>
                            <p>If you did not request this password reset, please ignore this email.</p>
                            <p>Best regards,<br>AI-Solutions Team</p>
                        ";
                        
                        $mail->send();
                        
                        $success_message = "Password reset instructions have been sent to your email address.";
                        $show_form = false;
                        
                    } catch (Exception $e) {
                        $error_message = "Failed to send reset email. Please try again later.";
                    }
                } else {
                    // PHPMailer not available - show manual instructions
                    $success_message = "Password reset requested. Please contact your administrator to reset your password. Your user ID is: {$user['id']}";
                    $show_form = false;
                }
                
                // Log the activity
                $logSql = "INSERT INTO activity_log (user_id, activity, ip_address)
                          VALUES (:user_id, :activity, :ip_address)";
                $logStmt = $pdo->prepare($logSql);
                $activity = "Password reset requested";
                $logStmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
                $logStmt->bindParam(':activity', $activity, PDO::PARAM_STR);
                $logStmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
                $logStmt->execute();
                
            } else {
                $error_message = "No account found with that email address.";
            }
        } catch (PDOException $e) {
            error_log("Forgot password error: " . $e->getMessage());
            $error_message = "An error occurred. Please try again later.";
        }
    }
}

// Function to get base URL
function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    $path = dirname($script_name);
    return $protocol . '://' . $host . $path . '/';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - AI-Solutions Admin</title>

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

        .forgot-password-container {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 50rem;
            padding: 4rem;
        }

        .forgot-password-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .forgot-password-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .forgot-password-logo img {
            height: 6rem;
            width: auto;
        }

        .forgot-password-title {
            font-size: 2.8rem;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }

        .forgot-password-subtitle {
            font-size: 1.6rem;
            color: var(--gray-color);
            line-height: 1.6;
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

        .alert-info {
            background-color: rgba(255, 215, 0, 0.1);
            color: var(--info-color);
            border: 1px solid var(--info-color);
        }

        .forgot-password-form .form-group {
            margin-bottom: 2rem;
        }

        .forgot-password-form label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .forgot-password-form .input-group {
            position: relative;
        }

        .forgot-password-form .input-group i {
            position: absolute;
            top: 50%;
            left: 1.5rem;
            transform: translateY(-50%);
            color: var(--gray-color);
        }

        .forgot-password-form input {
            width: 100%;
            padding: 1.2rem 1.2rem 1.2rem 4rem;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1.6rem;
        }

        .forgot-password-form input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .forgot-password-form button {
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

        .forgot-password-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(255, 215, 0, 0.3);
        }

        .forgot-password-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-light);
        }

        .forgot-password-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .forgot-password-footer a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        .back-to-login {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            margin-bottom: 2rem;
        }

        .back-to-login a {
            color: var(--gray-color);
            text-decoration: none;
            font-size: 1.4rem;
            transition: var(--transition);
        }

        .back-to-login a:hover {
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .forgot-password-container {
                padding: 2rem;
            }
            
            .forgot-password-title {
                font-size: 2.4rem;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <div class="forgot-password-header">
            <div class="forgot-password-logo">
                <img src="../images/logo.png" alt="AI-Solutions Logo">
            </div>
            <h1 class="forgot-password-title">Forgot Password?</h1>
            <p class="forgot-password-subtitle">
                <?php if ($phpmailer_available): ?>
                    Enter your email address and we'll send you a link to reset your password.
                <?php else: ?>
                    Enter your email address and we'll provide instructions to reset your password.
                <?php endif; ?>
            </p>
        </div>

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

        <?php if (!$phpmailer_available): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Note:</strong> Email Smtp functionality is not available on this Free Hosting server. Please contact your administrator for password reset assistance.
            </div>
        <?php endif; ?>

        <?php if ($show_form): ?>
            <form class="forgot-password-form" method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" 
                               placeholder="Enter your email address" required>
                    </div>
                </div>

                <button type="submit">
                    <i class="fas fa-paper-plane"></i>
                    Send Reset Instructions
                </button>
            </form>
        <?php endif; ?>

        <div class="forgot-password-footer">
            <div class="back-to-login">
                <a href="login.php">
                    <i class="fas fa-arrow-left"></i>
                    Back to Login
                </a>
            </div>
            <p>Remember your password? <a href="login.php">Sign in here</a></p>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/main.js"></script>
</body>
</html>
