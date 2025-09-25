<?php
/**
 * User Management for AI-Solution Admin Panel
 */

session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$admin_username = $_SESSION['admin_username'];

// Delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $delete_sql = "DELETE FROM users WHERE id = :id";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $delete_stmt->execute();
        set_flash_message('success', 'User deleted successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Error deleting user: ' . $e->getMessage());
    }
    header("Location: users.php");
    exit;
}

// Save user (add/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $role = sanitize_input($_POST['role']);
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    try {
        if ($id > 0) {
            $sql = "UPDATE users SET username = :username, email = :email, role = :role WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        } else {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
        }

        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        $stmt->execute();

        $msg = ($id > 0) ? 'updated' : 'created';
        set_flash_message('success', "User {$msg} successfully.");
        header("Location: users.php");
        exit;

    } catch (PDOException $e) {
        set_flash_message('danger', 'Error saving user: ' . $e->getMessage());
    }
}

// Edit user
$edit_mode = false;
$user = ['id' => '', 'username' => '', 'email' => '', 'role' => ''];

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $edit_stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
    $edit_stmt->execute();

    if ($edit_stmt->rowCount() > 0) {
        $user = $edit_stmt->fetch();
        $edit_mode = true;
    }
}

// Fetch all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - AI-Solution Admin</title>

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

        /* Gallery Grid Styles */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(20rem, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .gallery-item {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
            position: relative;
        }

        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .gallery-img {
            height: 15rem;
            width: 100%;
            object-fit: cover;
            display: block;
        }

        .gallery-content {
            padding: 1.5rem;
        }

        .gallery-title {
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .gallery-category {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            background-color: var(--gray-light);
            border-radius: 2rem;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .gallery-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            border-top: 1px solid var(--gray-light);
            padding-top: 1rem;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            font-size: 1.4rem;
        }

        .btn-edit {
            background-color: var(--primary-color);
            color: var(--light-color);
        }

        .btn-delete {
            background-color: var(--danger-color);
            color: var(--light-color);
        }

        /* Empty State */
        .empty-state {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            padding: 4rem 2rem;
            text-align: center;
            box-shadow: var(--box-shadow);
        }

        .empty-state h3 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }

        .empty-state p {
            color: var(--gray-color);
            margin-bottom: 2rem;
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
                            <a href="gallery.php" class="menu-link active">
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
                    <h1>User Management</h1>
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

<!-- Manage Users Section -->
<section class="admin-section">
    <div class="admin-form">
        <h3><?php echo $edit_mode ? 'Edit User' : 'Add New User'; ?></h3>
        <form action="users.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password <?php echo $edit_mode ? '(Leave blank to keep current)' : ''; ?></label>
                    <input type="password" id="password" name="password" class="form-control" <?php echo $edit_mode ? '' : 'required'; ?>>
                </div>
            </div>

            <div class="btn-group">
                <button type="submit" name="save_user" class="btn btn-primary">
                    <?php echo $edit_mode ? 'Update User' : 'Add User'; ?>
                </button>
                <a href="users.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</section>

<!-- Users List Section -->
<section class="admin-section">
    <h3>User Accounts</h3>

    <?php if (count($users) > 0): ?>
        <div class="gallery-grid">
            <?php foreach ($users as $item): ?>
                <div class="gallery-item">
                    <div class="gallery-content">
                        <h3 class="gallery-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p class="gallery-category"><?php echo htmlspecialchars($item['username']); ?></p>
                        <p><?php echo htmlspecialchars($item['email']); ?></p>

                        <div class="gallery-actions">
                            <a href="users.php?edit=<?php echo $item['id']; ?>" class="action-btn btn-edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="users.php?delete=<?php echo $item['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this user?');">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <h3>No Users Found</h3>
            <p>Add your first user using the form above.</p>
        </div>
    <?php endif; ?>
</section>


        </main>
    </div>
</body>
</html>
