<?php
/**
 * Portfolio Management for AI-Solutions Admin Panel
 */

session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$page_title = "Portfolio Management";

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

include 'includes/header.php';
?>

<!-- Portfolio Form Section -->
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

<!-- Portfolio List Section -->
<div class="admin-form">
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
                            <span class="status-badge <?php echo $item['featured'] ? 'status-featured' : 'status-inactive'; ?>">
                                <?php echo $item['featured'] ? 'Featured' : 'Regular'; ?>
                            </span>
                        </td>
                        <td class="table-actions">
                            <a href="portfolio.php?edit=<?php echo $item['id']; ?>" class="btn btn-primary btn-action">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="portfolio.php?toggle=<?php echo $item['id']; ?>" class="btn <?php echo $item['featured'] ? 'btn-warning' : 'btn-success'; ?> btn-action">
                                <i class="fas fa-star"></i>
                                <?php echo $item['featured'] ? 'Unfeature' : 'Feature'; ?>
                            </a>
                            <a href="portfolio.php?delete=<?php echo $item['id']; ?>" class="btn btn-danger btn-action btn-delete">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No portfolio items found. Add your first portfolio item using the form above.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>