<?php
/**
 * Portfolio Management for AI-Solution Admin Panel
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

// Process portfolio item deletion if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        // Get image filename before deleting portfolio item
        $img_query = "SELECT image FROM portfolio WHERE id = :id";
        $img_stmt = $pdo->prepare($img_query);
        $img_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $img_stmt->execute();
        $image = $img_stmt->fetch()['image'] ?? '';

        // Delete the portfolio item
        $delete_sql = "DELETE FROM portfolio WHERE id = :id";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $delete_stmt->execute();

        // Delete the image file if it exists
        if (!empty($image) && file_exists("../images/portfolio/" . $image)) {
            unlink("../images/portfolio/" . $image);
        }

        set_flash_message('success', 'Portfolio item deleted successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Error deleting portfolio item: ' . $e->getMessage());
    }

    // Redirect to avoid form resubmission
    header("Location: portfolio.php");
    exit;
}

// Toggle featured status if requested
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    try {
        // Get current status
        $status_query = "SELECT featured FROM portfolio WHERE id = :id";
        $status_stmt = $pdo->prepare($status_query);
        $status_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $status_stmt->execute();
        $current_status = $status_stmt->fetch()['featured'];

        // Toggle status
        $new_status = $current_status ? 0 : 1;
        $toggle_sql = "UPDATE portfolio SET featured = :status WHERE id = :id";
        $toggle_stmt = $pdo->prepare($toggle_sql);
        $toggle_stmt->bindParam(':status', $new_status, PDO::PARAM_INT);
        $toggle_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $toggle_stmt->execute();

        $status_text = $new_status ? 'featured' : 'unfeatured';
        set_flash_message('success', "Portfolio item marked as {$status_text} successfully.");
    } catch (PDOException $e) {
        set_flash_message('danger', 'Error updating portfolio item status: ' . $e->getMessage());
    }

    // Redirect to avoid form resubmission
    header("Location: portfolio.php");
    exit;
}

// Process portfolio form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_portfolio'])) {
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    $client = sanitize_input($_POST['client']);
    $category = sanitize_input($_POST['category']);
    $featured = isset($_POST['featured']) ? 1 : 0;
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    try {
        if ($id > 0) {
            // Updating existing portfolio item
            $sql = "UPDATE portfolio SET
                    title = :title,
                    description = :description,
                    client = :client,
                    category = :category,
                    featured = :featured
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        } else {
            // Creating new portfolio item
            $sql = "INSERT INTO portfolio (title, description, client, category, featured)
                    VALUES (:title, :description, :client, :category, :featured)";
            $stmt = $pdo->prepare($sql);
        }

        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':client', $client, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->bindParam(':featured', $featured, PDO::PARAM_INT);
        $stmt->execute();

        if ($id === 0) {
            $id = $pdo->lastInsertId();
        }

        // Handle image upload if provided
        if (!empty($_FILES['image']['name'])) {
            $image_dir = "../images/portfolio/";

            // Create directory if it doesn't exist
            if (!file_exists($image_dir)) {
                mkdir($image_dir, 0755, true);
            }

            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = 'portfolio_' . $id . '_' . time() . '.' . $file_extension;
            $target_file = $image_dir . $file_name;

            // Check if file is an image
            $check = getimagesize($_FILES['image']['tmp_name']);
            if ($check !== false) {
                // Delete old image if exists
                $img_query = "SELECT image FROM portfolio WHERE id = :id";
                $img_stmt = $pdo->prepare($img_query);
                $img_stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $img_stmt->execute();
                $old_image = $img_stmt->fetch()['image'] ?? '';

                if (!empty($old_image) && file_exists($image_dir . $old_image)) {
                    unlink($image_dir . $old_image);
                }

                // Upload new image
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    // Update image field in database
                    $update_img = "UPDATE portfolio SET image = :image WHERE id = :id";
                    $img_stmt = $pdo->prepare($update_img);
                    $img_stmt->bindParam(':image', $file_name, PDO::PARAM_STR);
                    $img_stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $img_stmt->execute();
                } else {
                    set_flash_message('warning', 'Error uploading image, but portfolio item was saved.');
                }
            } else {
                set_flash_message('warning', 'Uploaded file is not an image, but portfolio item was saved.');
            }
        }

        $action_text = ($id > 0) ? 'updated' : 'created';
        set_flash_message('success', "Portfolio item {$action_text} successfully!");

        // Redirect to portfolio list
        header("Location: portfolio.php");
        exit;

    } catch (PDOException $e) {
        set_flash_message('danger', 'Error saving portfolio item: ' . $e->getMessage());
    }
}

// Fetch portfolio item for editing if ID provided
$edit_mode = false;
$portfolio_item = [
    'id' => '',
    'title' => '',
    'description' => '',
    'client' => '',
    'category' => '',
    'image' => '',
    'featured' => 0
];

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_query = "SELECT * FROM portfolio WHERE id = :id";
    $edit_stmt = $pdo->prepare($edit_query);
    $edit_stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
    $edit_stmt->execute();

    if ($edit_stmt->rowCount() > 0) {
        $portfolio_item = $edit_stmt->fetch();
        $edit_mode = true;
    }
}

// Fetch all portfolio items for listing
$sql = "SELECT * FROM portfolio ORDER BY created_at DESC";
$stmt = $pdo->query($sql);
$portfolio_items = $stmt->fetchAll();

// Fetch distinct categories for dropdown
$cat_sql = "SELECT DISTINCT category FROM portfolio WHERE category IS NOT NULL ORDER BY category";
$cat_stmt = $pdo->query($cat_sql);
$categories = $cat_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Management - AI-Solution Admin</title>

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
            background-color: var(--dark-color);
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
            margin-top: 0.5rem;
        }

        .status-featured {
            background-color: var(--primary-color);
            color: var(--light-color);
        }

        .status-regular {
            background-color: var(--gray-light);
            color: var(--dark-color);
        }

        .thumbnail {
            width: 6rem;
            height: 6rem;
            object-fit: cover;
            border-radius: var(--border-radius);
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
                            <a href="portfolio.php" class="menu-link active">
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
                    <h1>Portfolio Management</h1>
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

            <!-- Portfolio Form Section -->
            <section class="admin-section">
                <div class="admin-form">
                    <h3><?php echo $edit_mode ? 'Edit Portfolio Item' : 'Add New Portfolio Item'; ?></h3>
                    <form action="portfolio.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $portfolio_item['id']; ?>">

                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($portfolio_item['title']); ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="client">Client Name</label>
                                <input type="text" id="client" name="client" class="form-control" value="<?php echo htmlspecialchars($portfolio_item['client']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="category">Category</label>
                                <input type="text" id="category" name="category" class="form-control" value="<?php echo htmlspecialchars($portfolio_item['category']); ?>" list="categories" required>
                                <datalist id="categories">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category['category']); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="6" required><?php echo htmlspecialchars($portfolio_item['description']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="image">Featured Image</label>
                            <input type="file" id="image" name="image" class="form-control" accept="image/*">
                            <?php if (!empty($portfolio_item['image'])): ?>
                                <div class="image-preview">
                                    <img src="../images/portfolio/<?php echo htmlspecialchars($portfolio_item['image']); ?>" alt="Current Portfolio Image">
                                    <p>Current image: <?php echo htmlspecialchars($portfolio_item['image']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" id="featured" name="featured" class="form-check-input" <?php echo $portfolio_item['featured'] ? 'checked' : ''; ?>>
                            <label for="featured" class="form-check-label">Feature this portfolio item on homepage</label>
                        </div>

                        <div class="btn-group">
                            <button type="submit" name="save_portfolio" class="btn btn-primary">
                                <?php echo $edit_mode ? 'Update Portfolio Item' : 'Add Portfolio Item'; ?>
                            </button>
                            <a href="portfolio.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Portfolio List Section -->
            <section class="admin-section">
                <h3>All Portfolio Items</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Client</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($portfolio_items) > 0): ?>
                            <?php foreach ($portfolio_items as $item): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="../images/portfolio/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="thumbnail">
                                        <?php else: ?>
                                            <div class="thumbnail" style="background-color: #eee; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-image" style="color: #aaa; font-size: 2rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['title']); ?></td>
                                    <td><?php echo htmlspecialchars($item['client']); ?></td>
                                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $item['featured'] ? 'status-featured' : 'status-regular'; ?>">
                                            <?php echo $item['featured'] ? 'Featured' : 'Regular'; ?>
                                        </span>
                                    </td>
                                    <td class="table-actions">
                                        <a href="portfolio.php?edit=<?php echo $item['id']; ?>" class="action-btn btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="portfolio.php?toggle=<?php echo $item['id']; ?>" class="action-btn <?php echo $item['featured'] ? 'btn-unfeature' : 'btn-feature'; ?>">
                                            <i class="fas <?php echo $item['featured'] ? 'fa-star' : 'fa-star'; ?>"></i>
                                            <?php echo $item['featured'] ? 'Unfeature' : 'Feature'; ?>
                                        </a>
                                        <a href="portfolio.php?delete=<?php echo $item['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this portfolio item?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No portfolio items found. Add your first portfolio item using the form above.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>
