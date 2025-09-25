<?php
/**
 * Gallery Management for AI-Solutions Admin Panel
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

include 'includes/header.php';
?>

<!-- Gallery Form Section -->
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

<!-- Gallery Grid Section -->
<div class="admin-form">
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
                            <a href="gallery.php?edit=<?php echo $item['id']; ?>" class="btn btn-primary btn-action">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="gallery.php?delete=<?php echo $item['id']; ?>" class="btn btn-danger btn-action btn-delete">
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
</div>

<?php include 'includes/footer.php'; ?>