<?php
/**
 * Gallery Management for AI-Solution Admin Panel
 */

session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$page_title = "Gallery Management";

// Process image deletion if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        // Get image filename before deleting
        $img_query = "SELECT image FROM gallery WHERE id = :id";
        $img_stmt = $pdo->prepare($img_query);
        $img_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $img_stmt->execute();
        $image = $img_stmt->fetch()['image'] ?? '';

        // Delete the gallery item
        $delete_sql = "DELETE FROM gallery WHERE id = :id";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $delete_stmt->execute();

        // Delete the image file if it exists
        if (!empty($image) && file_exists("../images/gallery/" . $image)) {
            unlink("../images/gallery/" . $image);
        }

        set_flash_message('success', 'Gallery image deleted successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Error deleting gallery image: ' . $e->getMessage());
    }

    // Redirect to avoid form resubmission
    header("Location: gallery.php");
    exit;
}

// Process gallery form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_gallery'])) {
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    $category = sanitize_input($_POST['category']);
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    try {
        if ($id > 0) {
            // Updating existing gallery item
            $sql = "UPDATE gallery SET
                    title = :title,
                    description = :description,
                    category = :category
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        } else {
            // Validate image upload for new items
            if (empty($_FILES['image']['name'])) {
                set_flash_message('danger', 'Image is required for new gallery items.');
                // Stay on the same page
                header("Location: gallery.php");
                exit;
            }

            // Creating new gallery item
            $sql = "INSERT INTO gallery (title, description, category)
                    VALUES (:title, :description, :category)";
            $stmt = $pdo->prepare($sql);
        }

        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->execute();

        if ($id === 0) {
            $id = $pdo->lastInsertId();
        }

        // Handle image upload if provided
        if (!empty($_FILES['image']['name'])) {
            $image_dir = "../images/gallery/";

            // Create directory if it doesn't exist
            if (!file_exists($image_dir)) {
                mkdir($image_dir, 0755, true);
            }

            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = 'gallery_' . $id . '_' . time() . '.' . $file_extension;
            $target_file = $image_dir . $file_name;

            // Check if file is an image
            $check = getimagesize($_FILES['image']['tmp_name']);
            if ($check !== false) {
                // Delete old image if exists
                $img_query = "SELECT image FROM gallery WHERE id = :id";
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
                    $update_img = "UPDATE gallery SET image = :image WHERE id = :id";
                    $img_stmt = $pdo->prepare($update_img);
                    $img_stmt->bindParam(':image', $file_name, PDO::PARAM_STR);
                    $img_stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $img_stmt->execute();
                } else {
                    set_flash_message('warning', 'Error uploading image, but gallery item was saved.');
                }
            } else {
                set_flash_message('warning', 'Uploaded file is not an image.');

                // If this is a new item without a valid image, delete it
                if ($id > 0 && !$edit_mode) {
                    $delete_sql = "DELETE FROM gallery WHERE id = :id";
                    $delete_stmt = $pdo->prepare($delete_sql);
                    $delete_stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $delete_stmt->execute();
                }

                header("Location: gallery.php");
                exit;
            }
        }

        $action_text = ($id > 0) ? 'updated' : 'created';
        set_flash_message('success', "Gallery item {$action_text} successfully!");

        // Redirect to gallery list
        header("Location: gallery.php");
        exit;

    } catch (PDOException $e) {
        set_flash_message('danger', 'Error saving gallery item: ' . $e->getMessage());
    }
}

// Fetch gallery item for editing if ID provided
$edit_mode = false;
$gallery_item = [
    'id' => '',
    'title' => '',
    'description' => '',
    'category' => '',
    'image' => ''
];

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_query = "SELECT * FROM gallery WHERE id = :id";
    $edit_stmt = $pdo->prepare($edit_query);
    $edit_stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
    $edit_stmt->execute();

    if ($edit_stmt->rowCount() > 0) {
        $gallery_item = $edit_stmt->fetch();
        $edit_mode = true;
    }
}

// Fetch all gallery items for listing
$sql = "SELECT * FROM gallery ORDER BY created_at DESC";
$stmt = $pdo->query($sql);
$gallery_items = $stmt->fetchAll();

// Fetch distinct categories for dropdown
$cat_sql = "SELECT DISTINCT category FROM gallery WHERE category IS NOT NULL ORDER BY category";
$cat_stmt = $pdo->query($cat_sql);
$categories = $cat_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Management - AI-Solution Admin</title>

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
                    <h1>Gallery Management</h1>
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

            <!-- Gallery Form Section -->
            <section class="admin-section">
                <div class="admin-form">
                    <h3><?php echo $edit_mode ? 'Edit Gallery Item' : 'Add New Gallery Item'; ?></h3>
                    <form action="gallery.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $gallery_item['id']; ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="title">Title</label>
                                <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($gallery_item['title']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="category">Category</label>
                                <input type="text" id="category" name="category" class="form-control" value="<?php echo htmlspecialchars($gallery_item['category']); ?>" list="categories" required>
                                <datalist id="categories">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category['category']); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($gallery_item['description']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="image">Image</label>
                            <input type="file" id="image" name="image" class="form-control" accept="image/*" <?php echo $edit_mode ? '' : 'required'; ?>>
                            <?php if (!empty($gallery_item['image'])): ?>
                                <div class="image-preview">
                                    <img src="../images/gallery/<?php echo htmlspecialchars($gallery_item['image']); ?>" alt="Current Image">
                                    <p>Current image: <?php echo htmlspecialchars($gallery_item['image']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="btn-group">
                            <button type="submit" name="save_gallery" class="btn btn-primary">
                                <?php echo $edit_mode ? 'Update Gallery Item' : 'Add to Gallery'; ?>
                            </button>
                            <a href="gallery.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </section>

            <!-- Gallery Grid Section -->
            <section class="admin-section">
                <h3>Gallery Items</h3>

                <?php if (count($gallery_items) > 0): ?>
                    <div class="gallery-grid">
                        <?php foreach ($gallery_items as $item): ?>
                            <div class="gallery-item">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="../images/gallery/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="gallery-img">
                                <?php else: ?>
                                    <img src="../images/gallery-default.jpg" alt="Default Image" class="gallery-img">
                                <?php endif; ?>

                                <div class="gallery-content">
                                    <h3 class="gallery-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                                    <?php if (!empty($item['category'])): ?>
                                        <span class="gallery-category"><?php echo htmlspecialchars($item['category']); ?></span>
                                    <?php endif; ?>

                                    <div class="gallery-actions">
                                        <a href="gallery.php?edit=<?php echo $item['id']; ?>" class="action-btn btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="gallery.php?delete=<?php echo $item['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this gallery item?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No Gallery Items Found</h3>
                        <p>Add your first gallery item using the form above.</p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
