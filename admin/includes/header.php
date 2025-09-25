<?php
/**
 * Admin Layout Header
 * Main header file that includes all components
 */

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Get user info
$user_id = $_SESSION['admin_id'];
$username = $_SESSION['admin_username'];
$user_role = $_SESSION['admin_role'];

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Admin Panel - AI-Solutions</title>

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
        :root {
            --primary-color: #FFD700;
            --secondary-color: #FFA500;
            --success-color: #32CD32;
            --danger-color: #DC143C;
            --warning-color: #FF6347;
            --info-color: #FFD700;
            --dark-color: #2F2F2F;
            --gray-color: #808080;
            --gray-light: #D3D3D3;
            --light-color: #ffffff;
            --body-bg: #FAFAFA;
            --sidebar-width: 28rem;
        }

        /* Dark mode variables */
        [data-theme="dark"] {
            --body-bg: #1a1a1a;
            --light-color: #2d2d2d;
            --dark-color: #ffffff;
            --gray-color: #b0b0b0;
            --gray-light: #404040;
        }

        body {
            font-family: 'Roboto', sans-serif;
            font-size: 1.6rem;
            line-height: 1.6;
            color: var(--dark-color);
            background-color: var(--body-bg);
            transition: all 0.3s ease;
            margin: 0;
            padding: 0;
        }

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        /* Content Area */
        .content-area {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        /* Flash Messages */
        .flash-message {
            padding: 1rem 2rem;
            margin-bottom: 2rem;
            border-radius: 0.5rem;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }

        .flash-message.success {
            background-color: rgba(50, 205, 50, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .flash-message.error {
            background-color: rgba(220, 20, 60, 0.1);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }

        .flash-message.warning {
            background-color: rgba(255, 99, 71, 0.1);
            color: var(--warning-color);
            border: 1px solid var(--warning-color);
        }

        .flash-message.info {
            background-color: rgba(255, 215, 0, 0.1);
            color: var(--info-color);
            border: 1px solid var(--info-color);
        }

        /* Mobile Responsive */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .content-area {
                padding: 1rem;
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-2rem);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Include Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Include Admin Header -->
            <?php include 'includes/admin-header.php'; ?>

            <!-- Content Area -->
            <div class="content-area">
                <?php display_flash_message(); ?>
