<?php
/**
 * Advanced Inquiries Management for AI-Solution Admin Panel
 * Features: Email replies, popup modals, status management, reply history
 */

// Start session
session_start();

// Include database configuration
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Current admin user
$admin_username = $_SESSION['admin_username'];

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_inquiry_details':
            $inquiry_id = (int)$_POST['inquiry_id'];
            
            try {
                // Get inquiry details
                $inquiry_sql = "SELECT * FROM inquiries WHERE id = :id";
                $inquiry_stmt = $pdo->prepare($inquiry_sql);
                $inquiry_stmt->bindParam(':id', $inquiry_id, PDO::PARAM_INT);
                $inquiry_stmt->execute();
                $inquiry = $inquiry_stmt->fetch();
                
                if (!$inquiry) {
                    echo json_encode(['success' => false, 'message' => 'Inquiry not found']);
                    exit;
                }
                
                // Get replies for this inquiry
                $replies_sql = "SELECT * FROM inquiry_replies WHERE inquiry_id = :inquiry_id ORDER BY created_at DESC";
                $replies_stmt = $pdo->prepare($replies_sql);
                $replies_stmt->bindParam(':inquiry_id', $inquiry_id, PDO::PARAM_INT);
                $replies_stmt->execute();
                $replies = $replies_stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'inquiry' => $inquiry,
                    'replies' => $replies
                ]);
                
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'send_reply':
            $inquiry_id = (int)$_POST['inquiry_id'];
            $subject = sanitize_input($_POST['subject']);
            $message = sanitize_input($_POST['message']);
            
            try {
                // Get inquiry details
                $inquiry_sql = "SELECT * FROM inquiries WHERE id = :id";
                $inquiry_stmt = $pdo->prepare($inquiry_sql);
                $inquiry_stmt->bindParam(':id', $inquiry_id, PDO::PARAM_INT);
                $inquiry_stmt->execute();
                $inquiry = $inquiry_stmt->fetch();
                
                if (!$inquiry) {
                    echo json_encode(['success' => false, 'message' => 'Inquiry not found']);
                    exit;
                }
                
                // Save reply to database
                $reply_sql = "INSERT INTO inquiry_replies (inquiry_id, admin_user, subject, message) 
                             VALUES (:inquiry_id, :admin_user, :subject, :message)";
                $reply_stmt = $pdo->prepare($reply_sql);
                $reply_stmt->bindParam(':inquiry_id', $inquiry_id, PDO::PARAM_INT);
                $reply_stmt->bindParam(':admin_user', $admin_username, PDO::PARAM_STR);
                $reply_stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
                $reply_stmt->bindParam(':message', $message, PDO::PARAM_STR);
                $reply_stmt->execute();
                
                $reply_id = $pdo->lastInsertId();
                
                // Update inquiry status and last reply time
                $inquiry_update = "UPDATE inquiries SET status = 'replied', last_reply_at = NOW() WHERE id = :id";
                $inquiry_update_stmt = $pdo->prepare($inquiry_update);
                $inquiry_update_stmt->bindParam(':id', $inquiry_id, PDO::PARAM_INT);
                $inquiry_update_stmt->execute();
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Reply saved successfully!',
                    'reply_id' => $reply_id
                ]);
                
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
    }
}

// Process status update if requested
if (isset($_GET['update_status'])) {
    $id = (int)$_GET['id'];
    $status = sanitize_input($_GET['status']);

    try {
        $sql = "UPDATE inquiries SET status = :status WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        set_flash_message('success', 'Inquiry status updated successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Error updating inquiry status: ' . $e->getMessage());
    }

    header("Location: inquiries.php");
    exit;
}

// Process inquiry deletion if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    try {
        $sql = "DELETE FROM inquiries WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        set_flash_message('success', 'Inquiry deleted successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Error deleting inquiry: ' . $e->getMessage());
    }

    header("Location: inquiries.php");
    exit;
}

// Fetch all inquiries with reply count
$sql = "SELECT i.*, 
        COUNT(r.id) as reply_count,
        MAX(r.created_at) as last_reply_date
        FROM inquiries i 
        LEFT JOIN inquiry_replies r ON i.id = r.inquiry_id 
        GROUP BY i.id 
        ORDER BY i.created_at DESC";
$stmt = $pdo->query($sql);
$inquiries = $stmt->fetchAll();

// Status options
$status_options = [
    'new' => 'New',
    'read' => 'Read',
    'replied' => 'Replied',
    'archived' => 'Archived'
];

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
    SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
    SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_count,
    SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived_count
    FROM inquiries";
$stats_stmt = $pdo->query($stats_sql);
$stats = $stats_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiries Management - AI-Solution Admin</title>

    <!-- CSS Files -->
    <link rel="stylesheet" href="../css/styles.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" href="../images/logo.png" type="image/png">

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
        /* Inquiry specific styles */
        .inquiry-card {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .inquiry-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .inquiry-card.new {
            border-left-color: var(--info-color);
        }

        .inquiry-card.read {
            border-left-color: var(--warning-color);
        }

        .inquiry-card.replied {
            border-left-color: var(--success-color);
        }

        .inquiry-card.archived {
            border-left-color: var(--gray-color);
        }

        .inquiry-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .inquiry-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
        }

        .inquiry-meta {
            display: flex;
            gap: 1rem;
            font-size: 1.4rem;
            color: var(--gray-color);
        }

        .inquiry-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .inquiry-body {
            margin-bottom: 1.5rem;
        }

        .inquiry-message {
            white-space: pre-wrap;
            line-height: 1.6;
        }

        .inquiry-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-light);
        }

        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 2rem;
            font-size: 1.2rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-new {
            background-color: var(--info-light);
            color: var(--info-dark);
        }

        .status-read {
            background-color: var(--warning-light);
            color: var(--warning-dark);
        }

        .status-replied {
            background-color: var(--success-light);
            color: var(--success-dark);
        }

        .status-archived {
            background-color: var(--gray-light);
            color: var(--gray-dark);
        }

        /* Stats cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(20rem, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            text-align: center;
        }

        .stat-card h3 {
            font-size: 1.4rem;
            text-transform: uppercase;
            color: var(--gray-color);
            margin-bottom: 1rem;
        }

        .stat-card .stat-value {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 2000;
            overflow-y: auto;
        }

        .modal-content {
            background-color: var(--light-color);
            margin: 5% auto;
            width: 80%;
            max-width: 800px;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 2rem;
            margin: 0;
        }

        .modal-close {
            font-size: 2.5rem;
            cursor: pointer;
            color: var(--gray-color);
            background: none;
            border: none;
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--gray-light);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        /* Reply list styles */
        .reply-list {
            margin-top: 2rem;
        }

        .reply-item {
            background-color: var(--gray-extra-light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .reply-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 1.4rem;
        }

        .reply-user {
            font-weight: 600;
        }

        .reply-date {
            color: var(--gray-color);
        }

        .reply-message {
            white-space: pre-wrap;
            line-height: 1.6;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .inquiry-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .modal-content {
                width: 95%;
                margin: 2% auto;
            }
        }
    </style>
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
                            <a href="inquiries.php" class="menu-link active">
                                <i class="menu-icon fas fa-envelope"></i>
                                <span>Manage Inquiries</span>
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
                            <a href="portfolio.php" class="menu-link">
                                <i class="menu-icon fas fa-briefcase"></i>
                                <span>Portfolio Management</span>
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
                            <a href="password.php" class="menu-link">
                                <i class="menu-icon fas fa-key"></i>
                                <span>Change Password</span>
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
                    <h1>Inquiries Management</h1>
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

            <!-- Flash Messages -->
            <?php display_flash_message(); ?>

            <!-- Stats Section -->
            <section class="admin-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Inquiries</h3>
                        <div class="stat-value"><?php echo $stats['total']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>New</h3>
                        <div class="stat-value"><?php echo $stats['new_count']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Read</h3>
                        <div class="stat-value"><?php echo $stats['read_count']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Replied</h3>
                        <div class="stat-value"><?php echo $stats['replied_count']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Archived</h3>
                        <div class="stat-value"><?php echo $stats['archived_count']; ?></div>
                    </div>
                </div>
            </section>

            <!-- Inquiries List Section -->
            <section class="admin-section">
                <h3>All Inquiries</h3>

                <?php if (count($inquiries) > 0): ?>
                    <?php foreach ($inquiries as $inquiry): ?>
                        <div class="inquiry-card <?php echo $inquiry['status']; ?>">
                            <div class="inquiry-header">
                                <h4 class="inquiry-title"><?php echo htmlspecialchars($inquiry['name']); ?></h4>
                                <div class="inquiry-meta">
                                    <span class="inquiry-meta-item">
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($inquiry['email']); ?>
                                    </span>
                                    <span class="inquiry-meta-item">
                                        <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($inquiry['created_at'])); ?>
                                    </span>
                                    <span class="status-badge status-<?php echo $inquiry['status']; ?>">
                                        <?php echo $status_options[$inquiry['status']]; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="inquiry-body">
                                <?php if (!empty($inquiry['company'])): ?>
                                    <p><strong>Company:</strong> <?php echo htmlspecialchars($inquiry['company']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($inquiry['job_title'])): ?>
                                    <p><strong>Job Title:</strong> <?php echo htmlspecialchars($inquiry['job_title']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($inquiry['country'])): ?>
                                    <p><strong>Country:</strong> <?php echo htmlspecialchars($inquiry['country']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($inquiry['phone'])): ?>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($inquiry['phone']); ?></p>
                                <?php endif; ?>
                                
                                <h5>Message:</h5>
                                <div class="inquiry-message"><?php echo htmlspecialchars($inquiry['message']); ?></div>
                            </div>

                            <div class="inquiry-actions">
                                <div>
                                    <?php if ($inquiry['reply_count'] > 0): ?>
                                        <span class="inquiry-meta-item">
                                            <i class="fas fa-reply"></i> <?php echo $inquiry['reply_count']; ?> replies
                                        </span>
                                        <span class="inquiry-meta-item">
                                            <i class="fas fa-clock"></i> Last reply: <?php echo date('M d, Y', strtotime($inquiry['last_reply_date'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inquiry-meta-item">
                                            <i class="fas fa-info-circle"></i> No replies yet
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <button class="btn btn-primary btn-reply" data-id="<?php echo $inquiry['id']; ?>">
                                        <i class="fas fa-reply"></i> Reply
                                    </button>
                                    <button class="btn btn-info btn-view" data-id="<?php echo $inquiry['id']; ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <div class="dropdown" style="display: inline-block;">
                                        <button class="btn btn-secondary dropdown-toggle" type="button" id="statusDropdown<?php echo $inquiry['id']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fas fa-cog"></i> Status
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="statusDropdown<?php echo $inquiry['id']; ?>">
                                            <?php foreach ($status_options as $value => $label): ?>
                                                <a class="dropdown-item" href="inquiries.php?update_status&id=<?php echo $inquiry['id']; ?>&status=<?php echo $value; ?>">
                                                    <?php echo $label; ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <a href="inquiries.php?delete=<?php echo $inquiry['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this inquiry?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No Inquiries Found</h3>
                        <p>There are no inquiries to display at this time.</p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Inquiry Modal -->
    <div id="inquiryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Inquiry Details</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body" id="inquiryModalBody">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-close">Close</button>
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <div id="replyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Reply to Inquiry</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form id="replyForm">
                <div class="modal-body">
                    <input type="hidden" id="replyInquiryId" name="inquiry_id">
                    
                    <div class="form-group">
                        <label for="replySubject">Subject</label>
                        <input type="text" id="replySubject" name="subject" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="replyMessage">Message</label>
                        <textarea id="replyMessage" name="message" class="form-control" rows="8" required></textarea>
                    </div>
                    
                    <div id="replyHistory" class="reply-list">
                        <!-- Previous replies will be shown here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Reply</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Modal functionality
            const inquiryModal = $('#inquiryModal');
            const replyModal = $('#replyModal');
            
            // Open inquiry modal
            $('.btn-view').click(function() {
                const inquiryId = $(this).data('id');
                
                $.ajax({
                    url: 'inquiries.php',
                    method: 'POST',
                    data: {
                        action: 'get_inquiry_details',
                        inquiry_id: inquiryId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const inquiry = response.inquiry;
                            let html = `
                                <div class="inquiry-details">
                                    <h4>${inquiry.name}</h4>
                                    <p><strong>Email:</strong> ${inquiry.email}</p>
                                    ${inquiry.phone ? `<p><strong>Phone:</strong> ${inquiry.phone}</p>` : ''}
                                    ${inquiry.company ? `<p><strong>Company:</strong> ${inquiry.company}</p>` : ''}
                                    ${inquiry.job_title ? `<p><strong>Job Title:</strong> ${inquiry.job_title}</p>` : ''}
                                    ${inquiry.country ? `<p><strong>Country:</strong> ${inquiry.country}</p>` : ''}
                                    <p><strong>Date:</strong> ${new Date(inquiry.created_at).toLocaleDateString()}</p>
                                    <p><strong>Status:</strong> <span class="status-badge status-${inquiry.status}">${inquiry.status.charAt(0).toUpperCase() + inquiry.status.slice(1)}</span></p>
                                    
                                    <h5>Message:</h5>
                                    <div class="inquiry-message">${inquiry.message.replace(/\n/g, '<br>')}</div>
                                </div>
                            `;
                            
                            if (response.replies.length > 0) {
                                html += `<div class="reply-list"><h5>Replies (${response.replies.length}):</h5>`;
                                
                                response.replies.forEach(reply => {
                                    html += `
                                        <div class="reply-item">
                                            <div class="reply-header">
                                                <span class="reply-user">${reply.admin_user}</span>
                                                <span class="reply-date">${new Date(reply.created_at).toLocaleString()}</span>
                                            </div>
                                            <h6>${reply.subject}</h6>
                                            <div class="reply-message">${reply.message.replace(/\n/g, '<br>')}</div>
                                        </div>
                                    `;
                                });
                                
                                html += `</div>`;
                            }
                            
                            $('#inquiryModalBody').html(html);
                            inquiryModal.show();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error loading inquiry details: ' + error);
                    }
                });
            });
            
            // Open reply modal
            $('.btn-reply').click(function() {
                const inquiryId = $(this).data('id');
                $('#replyInquiryId').val(inquiryId);
                
                $.ajax({
                    url: 'inquiries.php',
                    method: 'POST',
                    data: {
                        action: 'get_inquiry_details',
                        inquiry_id: inquiryId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const inquiry = response.inquiry;
                            $('#replySubject').val('Re: Your inquiry from ' + new Date(inquiry.created_at).toLocaleDateString());
                            
                            let replyHistoryHtml = '';
                            if (response.replies.length > 0) {
                                replyHistoryHtml = '<h5>Previous Replies:</h5>';
                                
                                response.replies.forEach(reply => {
                                    replyHistoryHtml += `
                                        <div class="reply-item">
                                            <div class="reply-header">
                                                <span class="reply-user">${reply.admin_user}</span>
                                                <span class="reply-date">${new Date(reply.created_at).toLocaleString()}</span>
                                            </div>
                                            <h6>${reply.subject}</h6>
                                            <div class="reply-message">${reply.message.replace(/\n/g, '<br>')}</div>
                                        </div>
                                    `;
                                });
                            }
                            
                            $('#replyHistory').html(replyHistoryHtml);
                            replyModal.show();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error loading inquiry details: ' + error);
                    }
                });
            });
            
            // Submit reply form
            $('#replyForm').submit(function(e) {
                e.preventDefault();
                
                const formData = {
                    action: 'send_reply',
                    inquiry_id: $('#replyInquiryId').val(),
                    subject: $('#replySubject').val(),
                    message: $('#replyMessage').val()
                };
                
                $.ajax({
                    url: 'inquiries.php',
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            replyModal.hide();
                            location.reload(); // Refresh to show the new reply
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error sending reply: ' + error);
                    }
                });
            });
            
            // Close modals
            $('.modal-close').click(function() {
                inquiryModal.hide();
                replyModal.hide();
            });
            
            // Close modal when clicking outside
            $(window).click(function(e) {
                if (e.target === inquiryModal[0]) {
                    inquiryModal.hide();
                }
                if (e.target === replyModal[0]) {
                    replyModal.hide();
                }
            });
        });
    </script>
</body>
</html>