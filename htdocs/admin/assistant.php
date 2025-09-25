<?php
/**
 * AI Assistant for AI-Solution Admin Panel
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

// Initialize chat history if not exists
if (!isset($_SESSION['assistant_chat'])) {
    $_SESSION['assistant_chat'] = [];

    // Add welcome message
    $_SESSION['assistant_chat'][] = [
        'sender' => 'assistant',
        'message' => "Hello $admin_username! I'm your AI assistant for the admin panel. How can I help you today?",
        'time' => time()
    ];
}

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_message']) && !empty($_POST['user_message'])) {
    $user_message = trim($_POST['user_message']);

    // Add user message to chat history
    $_SESSION['assistant_chat'][] = [
        'sender' => 'user',
        'message' => $user_message,
        'time' => time()
    ];

    // Generate AI response
    $response = generateResponse($user_message);

    // Add assistant response to chat history
    $_SESSION['assistant_chat'][] = [
        'sender' => 'assistant',
        'message' => $response,
        'time' => time()
    ];

    // Optional: Log conversation to database
    try {
        $log_sql = "INSERT INTO admin_chat_logs (user_id, username, user_message, ai_response, created_at)
                   VALUES (?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($log_sql);
        $user_id = $_SESSION['admin_id'] ?? 0;
        $stmt->execute([$user_id, $admin_username, $user_message, $response]);
    } catch (PDOException $e) {
        // Silently fail if logging fails
    }

    // Redirect to prevent form resubmission
    header("Location: assistant.php");
    exit;
}

// Clear chat history if requested
if (isset($_GET['clear']) && $_GET['clear'] == 1) {
    $_SESSION['assistant_chat'] = [];

    // Add welcome message
    $_SESSION['assistant_chat'][] = [
        'sender' => 'assistant',
        'message' => "Chat history cleared. How can I help you today?",
        'time' => time()
    ];

    // Redirect to prevent form resubmission
    header("Location: assistant.php");
    exit;
}

/**
 * Generate AI response based on user query
 * This is a simple rule-based implementation. In a real-world scenario,
 * this would likely call an external AI API (OpenAI, Azure, etc.)
 */
function generateResponse($query) {
    // Convert to lowercase for easier matching
    $query = strtolower($query);

    // Greeting patterns
    if (preg_match('/(hello|hi|hey|greetings)/i', $query)) {
        return "Hello! I'm your admin assistant. How can I help you with the admin panel today?";
    }

    // Help with different admin sections
    if (strpos($query, 'dashboard') !== false) {
        return "The Dashboard provides an overview of your website's performance. You can see recent inquiries, user registrations, and website traffic statistics. The cards at the top show key metrics, and the activity feed below shows recent actions.";
    }

    if (strpos($query, 'logo') !== false || strpos($query, 'brand') !== false) {
        return "In the Logo Management section, you can update your website's logo and favicon. For best results, upload a PNG logo with transparent background, ideally around 200px × 60px. For the favicon, a 32px × 32px image is recommended.";
    }

    if (strpos($query, 'inquir') !== false || strpos($query, 'contact') !== false || strpos($query, 'message') !== false) {
        return "The Inquiries section lets you manage all contact form submissions. You can view details, mark messages as read, archive them, or delete them. Use the filters at the top to sort by status or search for specific inquiries.";
    }

    if (strpos($query, 'blog') !== false || strpos($query, 'post') !== false || strpos($query, 'article') !== false) {
        return "In the Blog Management section, you can create, edit, and publish blog posts. Each post can have a title, content, featured image, and category. You can save posts as drafts or publish them immediately. Use the rich text editor to format your content.";
    }

    if (strpos($query, 'event') !== false) {
        return "The Events Management section allows you to create and manage events. For each event, you can specify a title, description, date, time, location, and featured image. Events can be marked as featured to appear prominently on the homepage.";
    }

    if (strpos($query, 'galler') !== false || strpos($query, 'image') !== false || strpos($query, 'photo') !== false) {
        return "The Gallery section lets you upload and manage images for your site. You can organize images by category and add descriptions. For best quality, upload high-resolution images, and the system will automatically create optimized versions.";
    }

    if (strpos($query, 'portfolio') !== false || strpos($query, 'project') !== false || strpos($query, 'work') !== false) {
        return "In the Portfolio section, you can showcase your projects. Add a title, description, client name, category, and featured image for each portfolio item. You can mark certain items as featured to highlight them on your homepage.";
    }

    if (strpos($query, 'analytic') !== false || strpos($query, 'statistic') !== false || strpos($query, 'traffic') !== false || strpos($query, 'visitor') !== false) {
        return "The Analytics section shows website traffic data including page views, unique visitors, and referrers. Use the date range selector at the top to view data for specific periods. This helps you understand which pages are most popular and where your traffic is coming from.";
    }

    if (strpos($query, 'password') !== false) {
        return "You can change your admin password in the Password section. Make sure to use a strong password with at least 8 characters, including uppercase letters, lowercase letters, numbers, and special characters. Never share your password with anyone.";
    }

    if (strpos($query, 'user') !== false || strpos($query, 'admin') !== false || strpos($query, 'account') !== false) {
        return "In the Users section, administrators can manage user accounts, including creating new admin users, editing existing accounts, or deactivating users. Only superadmins can access this functionality.";
    }

    // General help request
    if (strpos($query, 'help') !== false || strpos($query, 'guide') !== false || strpos($query, 'how to') !== false) {
        return "I can help you with any aspect of the admin panel. You can ask about specific features like 'How do I add a blog post?' or 'How do I change the logo?' I'm here to guide you through using this admin dashboard effectively.";
    }

    // Thank you responses
    if (preg_match('/(thank|thanks|gracias|merci)/i', $query)) {
        return "You're welcome! Feel free to ask if you need help with anything else.";
    }

    // Default response for unrecognized queries
    return "I'm not sure I understand your question. Could you please be more specific? You can ask about the dashboard, logo management, inquiries, blogs, events, gallery, portfolio, analytics, or password management.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Assistant - AI-Solution Admin</title>

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
                            <a href="password.php" class="menu-link">
                                <i class="menu-icon fas fa-key"></i>
                                <span>Change Password</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="assistant.php" class="menu-link active">
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
                    <h1>AI Assistant</h1>
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

            <div class="row">
                <div class="col-md-8">
                    <!-- Chat Interface -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-robot mr-2"></i> AI Assistant
                            </h3>
                            <a href="assistant.php?clear=1" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to clear the chat history?')">
                                <i class="fas fa-trash"></i> Clear Chat
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="chat-container" id="chat-container">
                                <?php foreach ($_SESSION['assistant_chat'] as $message): ?>
                                    <div class="chat-message <?php echo $message['sender']; ?>">
                                        <div class="chat-bubble">
                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                        </div>
                                        <div class="chat-time">
                                            <?php echo date('h:i A', $message['time']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <form action="assistant.php" method="POST" class="chat-form">
                                <div class="chat-input-container">
                                    <textarea name="user_message" class="chat-input" placeholder="Type your question here..." required></textarea>
                                    <button type="submit" class="chat-send-btn">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Quick Help Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Quick Help</h3>
                        </div>
                        <div class="card-body">
                            <div class="quick-help-list">
                                <div class="quick-help-item" data-query="How do I add a new blog post?">
                                    <i class="fas fa-blog"></i>
                                    <span>How do I add a new blog post?</span>
                                </div>
                                <div class="quick-help-item" data-query="How do I change the logo?">
                                    <i class="fas fa-image"></i>
                                    <span>How do I change the logo?</span>
                                </div>
                                <div class="quick-help-item" data-query="How do I view contact inquiries?">
                                    <i class="fas fa-envelope"></i>
                                    <span>How do I view contact inquiries?</span>
                                </div>
                                <div class="quick-help-item" data-query="How do I add an event?">
                                    <i class="fas fa-calendar"></i>
                                    <span>How do I add an event?</span>
                                </div>
                                <div class="quick-help-item" data-query="How do I upload images to the gallery?">
                                    <i class="fas fa-images"></i>
                                    <span>How do I upload gallery images?</span>
                                </div>
                                <div class="quick-help-item" data-query="How do I change my password?">
                                    <i class="fas fa-key"></i>
                                    <span>How do I change my password?</span>
                                </div>
                                <div class="quick-help-item" data-query="How do I add a portfolio project?">
                                    <i class="fas fa-briefcase"></i>
                                    <span>How do I add a portfolio project?</span>
                                </div>
                                <div class="quick-help-item" data-query="How do I view website analytics?">
                                    <i class="fas fa-chart-bar"></i>
                                    <span>How do I view website analytics?</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tips Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Tips</h3>
                        </div>
                        <div class="card-body">
                            <ul class="tips-list">
                                <li>Ask specific questions for better answers</li>
                                <li>You can ask about any admin panel feature</li>
                                <li>Use quick help buttons for common questions</li>
                                <li>The AI assistant remembers your conversation</li>
                                <li>Clear the chat if you want to start fresh</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
        /* Chat Styles */
        .chat-container {
            height: 400px;
            overflow-y: auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-bottom: 1px solid #e9ecef;
        }

        .chat-message {
            display: flex;
            flex-direction: column;
            max-width: 75%;
            margin-bottom: 20px;
        }

        .chat-message.user {
            align-self: flex-end;
            margin-left: auto;
        }

        .chat-message.assistant {
            align-self: flex-start;
            margin-right: auto;
        }

        .chat-bubble {
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
            word-break: break-word;
        }

        .chat-message.user .chat-bubble {
            background-color: #4f46e5;
            color: white;
            border-bottom-right-radius: 4px;
        }

        .chat-message.assistant .chat-bubble {
            background-color: #e5e7eb;
            color: #111827;
            border-bottom-left-radius: 4px;
        }

        .chat-time {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
            align-self: flex-end;
        }

        .chat-message.user .chat-time {
            margin-right: 10px;
        }

        .chat-message.assistant .chat-time {
            margin-left: 10px;
        }

        .chat-input-container {
            display: flex;
            border-top: 1px solid #e9ecef;
            padding: 10px;
        }

        .chat-input {
            flex: 1;
            border: 1px solid #d1d5db;
            border-radius: 20px;
            padding: 12px 20px;
            resize: none;
            height: 50px;
            max-height: 150px;
            font-size: 16px;
            margin-right: 10px;
            transition: border-color 0.3s;
        }

        .chat-input:focus {
            outline: none;
            border-color: #4f46e5;
        }

        .chat-send-btn {
            background-color: #4f46e5;
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .chat-send-btn:hover {
            background-color: #4338ca;
        }

        /* Quick Help Styles */
        .quick-help-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .quick-help-item {
            display: flex;
            align-items: center;
            padding: 12px;
            background-color: #f3f4f6;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .quick-help-item:hover {
            background-color: #e5e7eb;
        }

        .quick-help-item i {
            margin-right: 12px;
            color: #4f46e5;
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .tips-list {
            padding-left: 20px;
        }

        .tips-list li {
            margin-bottom: 10px;
            color: #4b5563;
        }

        /* Layout */
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -15px;
        }

        .col-md-8 {
            width: 66.666667%;
            padding: 0 15px;
        }

        .col-md-4 {
            width: 33.333333%;
            padding: 0 15px;
        }

        @media (max-width: 768px) {
            .col-md-8, .col-md-4 {
                width: 100%;
            }
        }

        .d-flex {
            display: flex;
        }

        .justify-content-between {
            justify-content: space-between;
        }

        .align-items-center {
            align-items: center;
        }

        .mb-0 {
            margin-bottom: 0;
        }

        .mb-4 {
            margin-bottom: 1.5rem;
        }

        .mr-2 {
            margin-right: 0.5rem;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll chat to bottom
            const chatContainer = document.getElementById('chat-container');
            chatContainer.scrollTop = chatContainer.scrollHeight;

            // Auto-resize textarea
            const textarea = document.querySelector('.chat-input');
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                const newHeight = Math.min(this.scrollHeight, 150);
                this.style.height = newHeight + 'px';
            });

            // Quick help items
            const quickHelpItems = document.querySelectorAll('.quick-help-item');
            quickHelpItems.forEach(item => {
                item.addEventListener('click', function() {
                    const query = this.getAttribute('data-query');
                    textarea.value = query;
                    textarea.focus();

                    // Trigger the height resize
                    const event = new Event('input');
                    textarea.dispatchEvent(event);
                });
            });

            // Submit form on Ctrl+Enter
            textarea.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && e.ctrlKey) {
                    e.preventDefault();
                    this.form.submit();
                }
            });
        });
    </script>
</body>
</html>
