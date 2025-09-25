<?php
/**
 * Analytics Dashboard for AI-Solution Admin Panel
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

// Date range for analytics
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));

// Validate dates
if (!strtotime($start_date) || !strtotime($end_date)) {
    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime('-30 days'));
}

// Ensure start_date is before end_date
if (strtotime($start_date) > strtotime($end_date)) {
    $temp = $start_date;
    $start_date = $end_date;
    $end_date = $temp;
}

// Get page views by date
try {
    $sql = "SELECT DATE(visit_time) as date, COUNT(*) as count
            FROM visitors
            WHERE visit_time BETWEEN ? AND ?
            GROUP BY DATE(visit_time)
            ORDER BY date ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date . ' 23:59:59']);
    $page_views_by_date = $stmt->fetchAll();
} catch (PDOException $e) {
    $page_views_by_date = [];
}

// Get total page views
try {
    $sql = "SELECT COUNT(*) FROM visitors WHERE visit_time BETWEEN ? AND ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date . ' 23:59:59']);
    $total_page_views = $stmt->fetchColumn();
} catch (PDOException $e) {
    $total_page_views = 0;
}

// Get unique visitors (by IP)
try {
    $sql = "SELECT COUNT(DISTINCT ip_address) FROM visitors WHERE visit_time BETWEEN ? AND ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date . ' 23:59:59']);
    $unique_visitors = $stmt->fetchColumn();
} catch (PDOException $e) {
    $unique_visitors = 0;
}

// Get most visited pages
try {
    $sql = "SELECT page_visited, COUNT(*) as count
            FROM visitors
            WHERE visit_time BETWEEN ? AND ?
            GROUP BY page_visited
            ORDER BY count DESC
            LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date . ' 23:59:59']);
    $top_pages = $stmt->fetchAll();
} catch (PDOException $e) {
    $top_pages = [];
}

// Get referrers
try {
    $sql = "SELECT referrer, COUNT(*) as count
            FROM visitors
            WHERE visit_time BETWEEN ? AND ? AND referrer IS NOT NULL AND referrer != ''
            GROUP BY referrer
            ORDER BY count DESC
            LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date . ' 23:59:59']);
    $top_referrers = $stmt->fetchAll();
} catch (PDOException $e) {
    $top_referrers = [];
}

// Prepare data for charts
$dates = [];
$views = [];

foreach ($page_views_by_date as $item) {
    $dates[] = date('M d', strtotime($item['date']));
    $views[] = (int)$item['count'];
}

// Calculate bounce rate (example - this would typically be based on more complex analytics)
$bounce_rate = 35; // Placeholder value

// Calculate average time on site (placeholder)
$avg_time = "2:45"; // Placeholder value
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - AI-Solution Admin</title>

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

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
                            <a href="analytics.php" class="menu-link active">
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
                    <h1>Website Analytics</h1>
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

            <!-- Date Range Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form action="" method="GET" class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-group w-100">
                                <button type="submit" class="btn btn-primary">Apply Filter</button>
                                <a href="analytics.php" class="btn btn-secondary ml-2">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Stats Summary -->
            <div class="row mb-4">
                <div class="col">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h5>Total Page Views</h5>
                            <h2 class="mb-0"><?php echo number_format($total_page_views); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h5>Unique Visitors</h5>
                            <h2 class="mb-0"><?php echo number_format($unique_visitors); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h5>Avg. Time on Site</h5>
                            <h2 class="mb-0"><?php echo $avg_time; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h5>Bounce Rate</h5>
                            <h2 class="mb-0"><?php echo $bounce_rate; ?>%</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Traffic Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Traffic Overview</h3>
                </div>
                <div class="card-body">
                    <canvas id="trafficChart" height="300"></canvas>
                </div>
            </div>

            <!-- Page Views and Referrers -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Most Visited Pages</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($top_pages) > 0): ?>
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Page</th>
                                            <th>Views</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_pages as $page): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($page['page_visited']); ?></td>
                                                <td><?php echo number_format($page['count']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="alert alert-info">No page view data available.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Top Referrers</h3>
                        </div>
                        <div class="card-body">
                            <?php if (count($top_referrers) > 0): ?>
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Referrer</th>
                                            <th>Visits</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_referrers as $referrer): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($referrer['referrer']); ?></td>
                                                <td><?php echo number_format($referrer['count']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="alert alert-info">No referrer data available.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tracking Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Analytics Information</h3>
                </div>
                <div class="card-body">
                    <p>This analytics dashboard shows basic website traffic metrics. For more comprehensive analytics, consider integrating with Google Analytics or similar service.</p>
                    <p><strong>Current tracking methods:</strong></p>
                    <ul>
                        <li>Page Views: Each time a page is loaded</li>
                        <li>Unique Visitors: Based on IP addresses</li>
                        <li>Referrers: Websites that send traffic to your site</li>
                    </ul>
                    <p><strong>Note:</strong> The data shown here is collected internally and may not match external analytics services due to differences in tracking methodology.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Traffic Chart
            var ctx = document.getElementById('trafficChart').getContext('2d');
            var trafficChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($dates); ?>,
                    datasets: [{
                        label: 'Page Views',
                        data: <?php echo json_encode($views); ?>,
                        backgroundColor: 'rgba(79, 70, 229, 0.2)',
                        borderColor: 'rgba(79, 70, 229, 1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: 'rgba(79, 70, 229, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    return 'Page Views: ' + context.parsed.y;
                                }
                            }
                        },
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

            // Date validation
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');

            startDateInput.addEventListener('change', function() {
                if (this.value > endDateInput.value) {
                    alert('Start date cannot be later than end date.');
                    this.value = endDateInput.value;
                }
            });

            endDateInput.addEventListener('change', function() {
                if (this.value < startDateInput.value) {
                    alert('End date cannot be earlier than start date.');
                    this.value = startDateInput.value;
                }
            });
        });
    </script>
</body>
</html>
