<?php
/**
 * Password Management for AI-Solution Admin Panel
 */

// Start session
session_start();

// Include database configuration
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Redirect to login page
    header("Location: login.php");
    exit;
}

// Current admin user
$admin_username = $_SESSION['admin_username'];
$admin_id = $_SESSION['admin_id'] ?? 0;

// Initialize message variable
$message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate input
    if (empty($current_password)) {
        $message = '<div class="alert alert-danger">Please enter your current password.</div>';
    } elseif (empty($new_password)) {
        $message = '<div class="alert alert-danger">Please enter a new password.</div>';
    } elseif (empty($confirm_password)) {
        $message = '<div class="alert alert-danger">Please confirm your new password.</div>';
    } elseif ($new_password !== $confirm_password) {
        $message = '<div class="alert alert-danger">New password and confirmation do not match.</div>';
    } elseif (strlen($new_password) < 8) {
        $message = '<div class="alert alert-danger">Password must be at least 8 characters long.</div>';
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

                    // Log password change
                    $log_sql = "INSERT INTO activity_log (user_id, activity, ip_address) VALUES (?, ?, ?)";
                    $log_stmt = $pdo->prepare($log_sql);
                    $activity = "Password changed";
                    $log_stmt->execute([$admin_id, $activity, $_SERVER['REMOTE_ADDR']]);

                    $message = '<div class="alert alert-success">Password has been updated successfully.</div>';

                    // Clear form fields
                    $current_password = '';
                    $new_password = '';
                    $confirm_password = '';
                } else {
                    $message = '<div class="alert alert-danger">Current password is incorrect.</div>';
                }
            } else {
                $message = '<div class="alert alert-danger">User account not found.</div>';
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Database error: ' . $e->getMessage() . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - AI-Solution Admin</title>

    <!-- CSS Files -->
    <link rel="stylesheet" href="../css/styles.css">
    
    <style>
        :root {
            --sidebar-width: 26rem;
            --header-height: 6rem;
        }

        body {
            background-color: #f3f4f6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Admin Layout */
        .admin-layout {
            display: flex;
            flex: 1;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--dark-color);
            color: var(--light-color);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            transition: var(--transition);
            z-index: 1000;
        }

        .sidebar-header {
            padding: 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
        }

        .sidebar-logo img {
            height: 4rem;
            width: auto;
            margin-right: 1rem;
        }

        .sidebar-logo span {
            font-weight: 700;
            font-size: 1.8rem;
            color: var(--light-color);
        }

        .sidebar-menu {
            padding: 2rem 0;
        }

        .menu-section {
            margin-bottom: 3rem;
        }

        .menu-section-title {
            font-size: 1.2rem;
            text-transform: uppercase;
            color: var(--gray-color);
            padding: 0 2rem;
            margin-bottom: 1.5rem;
            letter-spacing: 0.1em;
        }

        .menu-list {
            list-style: none;
        }

        .menu-item {
            margin-bottom: 0.5rem;
        }

        .menu-link {
            display: flex;
            align-items: center;
            padding: 1rem 2rem;
            color: var(--gray-light);
            transition: var(--transition);
            font-size: 1.4rem;
        }

        .menu-link:hover,
        .menu-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--light-color);
        }

        .menu-icon {
            margin-right: 1.5rem;
            width: 1.8rem;
            text-align: center;
            font-size: 1.6rem;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 2rem;
            transition: var(--transition);
        }

        /* Admin Header */
        .admin-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: var(--header-height);
            padding: 0 2rem;
            background-color: var(--light-color);
            border-bottom: 1px solid var(--gray-light);
            margin-bottom: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .header-title h1 {
            font-size: 2.4rem;
            margin-bottom: 0;
        }

        .header-actions {
            display: flex;
            align-items: center;
        }

        .user-dropdown {
            position: relative;
            margin-left: 2rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 0.8rem 1.2rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .user-info:hover {
            background-color: var(--gray-light);
        }

        .user-name {
            margin-right: 1rem;
            font-weight: 500;
        }

        .user-avatar {
            width: 3.6rem;
            height: 3.6rem;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 1rem;
        }

        /* Dashboard Cards */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(25rem, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .dashboard-card {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            transition: var(--transition);
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .card-icon {
            font-size: 2.4rem;
            width: 5rem;
            height: 5rem;
            background-color: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-title {
            font-size: 1.6rem;
            color: var(--gray-color);
            margin-bottom: 0.5rem;
        }

        .card-value {
            font-size: 3.2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0;
        }

        .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-light);
        }

        .card-change {
            font-size: 1.4rem;
            display: flex;
            align-items: center;
        }

        .card-change.positive {
            color: var(--success-color);
        }

        .card-change.negative {
            color: var(--danger-color);
        }

        .card-period {
            font-size: 1.4rem;
            color: var(--gray-color);
        }

        /* Recent Activity Section */
        .activity-section {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-bottom: 3rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 2rem;
            margin-bottom: 0;
        }

        .activity-list {
            list-style: none;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 1.5rem 0;
            border-bottom: 1px solid var(--gray-light);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            font-size: 1.6rem;
            width: 3.6rem;
            height: 3.6rem;
            background-color: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.5rem;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-size: 1.6rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .activity-text {
            font-size: 1.4rem;
            color: var(--gray-color);
            margin-bottom: 0;
        }

        .activity-time {
            font-size: 1.2rem;
            color: var(--gray-color);
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .sidebar {
                width: 0;
                transform: translateX(-100%);
            }

            .sidebar.active {
                width: var(--sidebar-width);
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .menu-toggle {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .admin-header {
                flex-direction: column;
                height: auto;
                padding: 1.5rem;
            }

            .header-title {
                margin-bottom: 1.5rem;
            }
        }
        /* Admin Form Styles */
        .admin-form {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-bottom: 3rem;
        }

        .admin-form h3 {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: flex;
            margin-left: -1rem;
            margin-right: -1rem;
        }

        .form-row > .form-group {
            padding: 0 1rem;
            flex: 1;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1.6rem;
        }

        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .form-check-input {
            margin-right: 1rem;
            width: 1.8rem;
            height: 1.8rem;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .image-preview {
            margin-top: 1rem;
            text-align: center;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 20rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        /* Table Styles */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            margin-bottom: 3rem;
        }

        .admin-table th,
        .admin-table td {
            padding: 1.5rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-light);
        }

        .admin-table th {
            background-color: var(--primary-color);
            color: var(--light-color);
            font-weight: 600;
        }

        .admin-table tr:last-child td {
            border-bottom: none;
        }

        .admin-table tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .table-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-size: 1.4rem;
            white-space: nowrap;
        }

        .btn-edit {
            background-color: var(--primary-color);
            color: var(--light-color);
        }

        .btn-delete {
            background-color: var(--danger-color);
            color: var(--light-color);
        }

        .btn-feature, .btn-unfeature {
            background-color: var(--success-color);
            color: var(--light-color);
        }

        .btn-unfeature {
            background-color: var(--warning-color);
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 1.2rem;
            font-weight: 500;
        }

        .status-upcoming {
            background-color: var(--success-color);
            color: var(--light-color);
        }

        .status-past {
            background-color: var(--gray-light);
            color: var(--dark-color);
        }

        .status-featured {
            background-color: var(--primary-color);
            color: var(--light-color);
        }
    </style>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" href="../images/logo.png" type="image/png">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <img src="../images/logo.png" alt="AI-Solution Logo">
                    <span>AI-Solution</span>
                </div>
            </div>

            <div class="sidebar-menu">
                <div class="menu-section">
                    <h4 class="menu-section-title">Main</h4>
                    <ul class="menu-list">
                        <li class="menu-item">
                            <a href="index.php" class="menu-link">
                                <i class="menu-icon fas fa-tachometer-alt"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="inquiries.php" class="menu-link">
                                <i class="menu-icon fas fa-envelope"></i>
                                <span>Manage Inquires</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="analytics.php" class="menu-link">
                                <i class="menu-icon fas fa-chart-bar"></i>
                                <span>Visitor Analytics</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="menu-section">
                    <h4 class="menu-section-title">Content</h4>
                    <ul class="menu-list">
                        <li class="menu-item">
                            <a href="logo.php" class="menu-link">
                                <i class="menu-icon fas fa-image"></i>
                                <span>Logo Management</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="events.php" class="menu-link">
                                <i class="menu-icon fas fa-calendar"></i>
                                <span>Events Management</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="blogs.php" class="menu-link">
                                <i class="menu-icon fas fa-blog"></i>
                                <span>Blog Management</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="gallery.php" class="menu-link">
                                <i class="menu-icon fas fa-images"></i>
                                <span>Manage Gallery</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="menu-section">
                    <h4 class="menu-section-title">User</h4>
                    <ul class="menu-list">
                        <li class="menu-item">
                            <a href="users.php" class="menu-link">
                                <i class="menu-icon fas fa-users"></i>
                                <span>Manage Users</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="password.php" class="menu-link active">
                                <i class="menu-icon fas fa-key"></i>
                                <span>Change Password</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="assistant.php" class="menu-link">
                                <i class="menu-icon fas fa-robot"></i>
                                <span>AI Assistant</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="index.php?logout=true" class="menu-link">
                                <i class="menu-icon fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Admin Header -->
            <header class="admin-header">
                <div class="header-title">
                    <h1>Change Password</h1>
                </div>

                <div class="header-actions">
                    <div class="user-dropdown">
                        <div class="user-info">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
                            </div>
                            <span class="user-name"><?php echo htmlspecialchars($admin_username); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Messages -->
            <?php if (!empty($message)): ?>
                <?php echo $message; ?>
            <?php endif; ?>

            <!-- Password Change Form -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Update Your Password</h3>
                        </div>
                        <div class="card-body">
                            <form action="" method="POST" id="password-form">
                                <div class="form-group">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="current_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" id="new_password" name="new_password" class="form-control" minlength="8" required>
                                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="new_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength mt-2" id="password-strength">
                                        <div class="strength-bar">
                                            <div class="strength-indicator" id="strength-indicator"></div>
                                        </div>
                                        <div class="strength-text" id="strength-text">Password strength</div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="8" required>
                                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="confirm_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div id="password-match" class="mt-2"></div>
                                </div>

                                <div class="form-group mt-4">
                                    <button type="submit" class="btn btn-primary">Update Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Password Guidelines</h3>
                        </div>
                        <div class="card-body">
                            <p>For a strong password, include:</p>
                            <ul>
                                <li>At least 8 characters</li>
                                <li>Uppercase letters (A-Z)</li>
                                <li>Lowercase letters (a-z)</li>
                                <li>Numbers (0-9)</li>
                                <li>Special characters (!@#$%^&*)</li>
                            </ul>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Never share your password with anyone else!
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
        .input-group {
            display: flex;
            align-items: stretch;
        }

        .input-group .form-control {
            flex: 1;
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .input-group .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

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
            // Toggle password visibility
            const toggleButtons = document.querySelectorAll('.toggle-password');
            toggleButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const passwordInput = document.getElementById(targetId);
                    const icon = this.querySelector('i');

                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        passwordInput.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });

            // Password match validation
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const passwordMatch = document.getElementById('password-match');

            function checkPasswordMatch() {
                if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
                    passwordMatch.innerHTML = '<div class="text-danger">Passwords do not match</div>';
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    passwordMatch.innerHTML = confirmPassword.value ? '<div class="text-success">Passwords match</div>' : '';
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

            // Form submission validation
            const form = document.getElementById('password-form');
            form.addEventListener('submit', function(event) {
                if (newPassword.value !== confirmPassword.value) {
                    event.preventDefault();
                    passwordMatch.innerHTML = '<div class="text-danger">Passwords do not match</div>';
                }
            });
        });
    </script>
</body>
</html>
