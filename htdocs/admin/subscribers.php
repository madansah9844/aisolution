<?php
/**
 * Newsletter Subscribers Management for AI-Solution Admin Panel
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

// Ensure subscribers table exists
try {
    $check_table = $pdo->query("SHOW TABLES LIKE 'subscribers'");
    if ($check_table->rowCount() == 0) {
        // Create the table if it doesn't exist
        $create_table = "CREATE TABLE subscribers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) NOT NULL UNIQUE,
            name VARCHAR(100) DEFAULT NULL,
            status ENUM('active', 'unsubscribed') DEFAULT 'active',
            source VARCHAR(50) DEFAULT NULL,
            subscription_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($create_table);
        set_flash_message('success', 'Subscribers table created successfully.');
    }
} catch (PDOException $e) {
    set_flash_message('danger', 'Database error: ' . $e->getMessage());
}

// Process subscriber deletion if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        // Delete the subscriber
        $delete_sql = "DELETE FROM subscribers WHERE id = :id";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $delete_stmt->execute();

        set_flash_message('success', 'Subscriber deleted successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Error deleting subscriber: ' . $e->getMessage());
    }

    // Redirect to avoid form resubmission
    header("Location: subscribers.php");
    exit;
}

// Toggle subscription status if requested
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    try {
        // Get current status
        $status_query = "SELECT status FROM subscribers WHERE id = :id";
        $status_stmt = $pdo->prepare($status_query);
        $status_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $status_stmt->execute();
        $current_status = $status_stmt->fetch()['status'];

        // Toggle status
        $new_status = ($current_status === 'active') ? 'unsubscribed' : 'active';
        $toggle_sql = "UPDATE subscribers SET status = :status WHERE id = :id";
        $toggle_stmt = $pdo->prepare($toggle_sql);
        $toggle_stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
        $toggle_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $toggle_stmt->execute();

        set_flash_message('success', "Subscriber marked as {$new_status} successfully.");
    } catch (PDOException $e) {
        set_flash_message('danger', 'Error updating subscriber status: ' . $e->getMessage());
    }

    // Redirect to avoid form resubmission
    header("Location: subscribers.php");
    exit;
}

// Process subscriber form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_subscriber'])) {
    $email = sanitize_input($_POST['email']);
    $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : null;
    $status = sanitize_input($_POST['status']);
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    try {
        if ($id > 0) {
            // Updating existing subscriber
            $sql = "UPDATE subscribers SET
                    email = :email,
                    name = :name,
                    status = :status
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        } else {
            // Creating new subscriber
            $sql = "INSERT INTO subscribers (email, name, status)
                    VALUES (:email, :name, :status)";
            $stmt = $pdo->prepare($sql);
        }

        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->execute();

        $action_text = ($id > 0) ? 'updated' : 'added';
        set_flash_message('success', "Subscriber {$action_text} successfully!");

        // Redirect to subscribers list
        header("Location: subscribers.php");
        exit;

    } catch (PDOException $e) {
        // Check for duplicate email error
        if ($e->getCode() == 23000) {
            set_flash_message('danger', 'Email address already exists.');
        } else {
            set_flash_message('danger', 'Error saving subscriber: ' . $e->getMessage());
        }
    }
}

// Export subscribers to CSV if requested
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    try {
        // Query to get all active subscribers
        $export_sql = "SELECT email, name, status, subscription_date FROM subscribers WHERE status = 'active' ORDER BY subscription_date DESC";
        $export_stmt = $pdo->query($export_sql);
        $subscribers = $export_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="subscribers_' . date('Y-m-d') . '.csv"');

        // Create a file pointer connected to the output stream
        $output = fopen('php://output', 'w');

        // Output the column headings
        fputcsv($output, array_keys($subscribers[0]));

        // Output each row of the data
        foreach ($subscribers as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;

    } catch (PDOException $e) {
        set_flash_message('danger', 'Error exporting subscribers: ' . $e->getMessage());
        header("Location: subscribers.php");
        exit;
    }
}

// Fetch subscriber for editing if ID provided
$edit_mode = false;
$subscriber = [
    'id' => '',
    'email' => '',
    'name' => '',
    'status' => 'active',
    'source' => 'manual',
    'subscription_date' => date('Y-m-d H:i:s')
];

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_query = "SELECT * FROM subscribers WHERE id = :id";
    $edit_stmt = $pdo->prepare($edit_query);
    $edit_stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
    $edit_stmt->execute();

    if ($edit_stmt->rowCount() > 0) {
        $subscriber = $edit_stmt->fetch();
        $edit_mode = true;
    }
}

// Get count of total and active subscribers
$count_sql = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed
FROM subscribers";
$count_stmt = $pdo->query($count_sql);
$counts = $count_stmt->fetch();

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch all subscribers for listing with filtering
$sql = "SELECT * FROM subscribers WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $sql .= " AND status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $sql .= " AND (email LIKE :search OR name LIKE :search OR source LIKE :search)";
    $params[':search'] = "%{$search}%";
}

$sql .= " ORDER BY subscription_date DESC";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$subscribers_list = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter Subscribers - AI-Solution Admin</title>

    <!-- CSS Files -->
    <link rel="stylesheet" href="../css/styles.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" href="../images/logo.png" type="image/png">

    <style>
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

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
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

        .action-btn {
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

        .btn-activate {
            background-color: var(--success-color);
            color: var(--light-color);
        }

        .btn-unsubscribe {
            background-color: var(--warning-color);
            color: var(--dark-color);
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 1.2rem;
            font-weight: 500;
        }

        .status-active {
            background-color: var(--success-color);
            color: var(--light-color);
        }

        .status-unsubscribed {
            background-color: var(--gray-light);
            color: var(--dark-color);
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(20rem, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            text-align: center;
        }

        .stat-card.active {
            border-top: 4px solid var(--success-color);
        }

        .stat-card.unsubscribed {
            border-top: 4px solid var(--warning-color);
        }

        .stat-card.total {
            border-top: 4px solid var(--primary-color);
        }

        .stat-number {
            font-size: 3.6rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .stat-label {
            font-size: 1.6rem;
            color: var(--gray-color);
        }

        /* Filter Bar */
        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .search-form {
            display: flex;
            gap: 1rem;
            flex: 1;
            max-width: 40rem;
        }

        .search-form input {
            flex: 1;
            padding: 0.8rem 1rem;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1.6rem;
        }

        .action-bar {
            display: flex;
            gap: 1rem;
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
                        <li class="menu-item">
                            <a href="subscribers.php" class="menu-link active">
                                <i class="menu-icon fas fa-envelope-open-text"></i>
                                <span>Newsletter Subscribers</span>
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
                    <h1>Newsletter Subscribers</h1>
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
                <div class="stats-container">
                    <div class="stat-card total">
                        <div class="stat-number"><?php echo $counts['total']; ?></div>
                        <div class="stat-label">Total Subscribers</div>
                    </div>

                    <div class="stat-card active">
                        <div class="stat-number"><?php echo $counts['active']; ?></div>
                        <div class="stat-label">Active Subscribers</div>
                    </div>

                    <div class="stat-card unsubscribed">
                        <div class="stat-number"><?php echo $counts['unsubscribed']; ?></div>
                        <div class="stat-label">Unsubscribed</div>
                    </div>
                </div>
            </section>

            <!-- Subscriber Form Section -->
            <section class="admin-section">
                <div class="admin-form">
                    <h3><?php echo $edit_mode ? 'Edit Subscriber' : 'Add New Subscriber'; ?></h3>
                    <form action="subscribers.php" method="POST">
                        <input type="hidden" name="id" value="<?php echo $subscriber['id']; ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($subscriber['email']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($subscriber['name']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="active" <?php echo ($subscriber['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="unsubscribed" <?php echo ($subscriber['status'] === 'unsubscribed') ? 'selected' : ''; ?>>Unsubscribed</option>
                                </select>
                            </div>
                        </div>

                        <div class="btn-group">
                            <button type="submit" name="save_subscriber" class="btn btn-primary">
                                <?php echo $edit_mode ? 'Update Subscriber' : 'Add Subscriber'; ?>
                            </button>
                            <a href="subscribers.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Filter Bar -->
            <section class="admin-section">
                <div class="filter-bar">
                    <div class="filter-group">
                        <form action="subscribers.php" method="GET" class="search-form">
                            <input type="text" name="search" placeholder="Search by email or name" value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <?php if (!empty($search) || !empty($status_filter)): ?>
                                <a href="subscribers.php" class="btn btn-secondary">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="action-bar">
                        <a href="subscribers.php?status=active" class="btn <?php echo ($status_filter === 'active') ? 'btn-primary' : 'btn-secondary'; ?>">Active</a>
                        <a href="subscribers.php?status=unsubscribed" class="btn <?php echo ($status_filter === 'unsubscribed') ? 'btn-primary' : 'btn-secondary'; ?>">Unsubscribed</a>
                        <a href="subscribers.php?export=csv" class="btn btn-success">Export CSV</a>
                    </div>
                </div>
            </section>

            <!-- Subscribers List Section -->
            <section class="admin-section">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Source</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($subscribers_list) > 0): ?>
                            <?php foreach ($subscribers_list as $sub): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sub['email']); ?></td>
                                    <td><?php echo htmlspecialchars($sub['name']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $sub['status']; ?>">
                                            <?php echo ucfirst($sub['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($sub['source'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($sub['subscription_date'])); ?></td>
                                    <td class="table-actions">
                                        <a href="subscribers.php?edit=<?php echo $sub['id']; ?>" class="action-btn btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <?php if ($sub['status'] === 'active'): ?>
                                            <a href="subscribers.php?toggle=<?php echo $sub['id']; ?>" class="action-btn btn-unsubscribe">
                                                <i class="fas fa-ban"></i> Unsubscribe
                                            </a>
                                        <?php else: ?>
                                            <a href="subscribers.php?toggle=<?php echo $sub['id']; ?>" class="action-btn btn-activate">
                                                <i class="fas fa-check"></i> Activate
                                            </a>
                                        <?php endif; ?>
                                        <a href="subscribers.php?delete=<?php echo $sub['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this subscriber?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No subscribers found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>
