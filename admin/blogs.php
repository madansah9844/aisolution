<?php
/**
 * Blog Management for AI-Solutions Admin Panel
 */

session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$page_title = "Blog Management";

// Process post deletion if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        // Get image filename before deleting post
        $img_query = "SELECT image FROM blogs WHERE id = :id";
        $img_stmt = $pdo->prepare($img_query);
        $img_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $img_stmt->execute();
        $image = $img_stmt->fetch()['image'] ?? '';

        // Delete the post
        $delete_sql = "DELETE FROM blogs WHERE id = :id";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $delete_stmt->execute();

        // Delete the image file if it exists
        if (!empty($image) && file_exists("../images/blogs/" . $image)) {
            unlink("../images/blogs/" . $image);
        }

        set_flash_message('success', 'Blog post deleted successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Error deleting blog post: ' . $e->getMessage());
    }

    header("Location: blogs.php");
    exit;
}

// Toggle publish status if requested
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    try {
        // Get current status
        $status_query = "SELECT published FROM blogs WHERE id = :id";
        $status_stmt = $pdo->prepare($status_query);
        $status_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $status_stmt->execute();
        $current_status = $status_stmt->fetch()['published'];

        // Toggle status
        $new_status = $current_status ? 0 : 1;
        $toggle_sql = "UPDATE blogs SET published = :status WHERE id = :id";
        $toggle_stmt = $pdo->prepare($toggle_sql);
        $toggle_stmt->bindParam(':status', $new_status, PDO::PARAM_INT);
        $toggle_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $toggle_stmt->execute();

        $status_text = $new_status ? 'published' : 'unpublished';
        set_flash_message('success', "Blog post {$status_text} successfully.");
    } catch (PDOException $e) {
        set_flash_message('danger', 'Error updating blog post status: ' . $e->getMessage());
    }

    header("Location: blogs.php");
    exit;
}

// Process blog post form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_blog'])) {
    $title = sanitize_input($_POST['title']);
    $content = $_POST['content']; // Don't sanitize rich text content
    $excerpt = sanitize_input($_POST['excerpt']);
    $author = sanitize_input($_POST['author']);
    $category = sanitize_input($_POST['category']);
    $published = isset($_POST['published']) ? 1 : 0;
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    try {
        if ($id > 0) {
            // Updating existing blog post
            $sql = "UPDATE blogs SET
                    title = :title,
                    content = :content,
                    excerpt = :excerpt,
                    author = :author,
                    category = :category,
                    published = :published
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        } else {
            // Creating new blog post
            $sql = "INSERT INTO blogs (title, content, excerpt, author, category, published)
                    VALUES (:title, :content, :excerpt, :author, :category, :published)";
            $stmt = $pdo->prepare($sql);
        }

        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':content', $content, PDO::PARAM_STR);
        $stmt->bindParam(':excerpt', $excerpt, PDO::PARAM_STR);
        $stmt->bindParam(':author', $author, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->bindParam(':published', $published, PDO::PARAM_INT);
        $stmt->execute();

        if ($id === 0) {
            $id = $pdo->lastInsertId();
        }

        // Handle image upload if provided
        if (!empty($_FILES['image']['name'])) {
            $image_dir = "../images/blogs/";

            // Create directory if it doesn't exist
            if (!file_exists($image_dir)) {
                mkdir($image_dir, 0755, true);
            }

            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = 'blog_' . $id . '_' . time() . '.' . $file_extension;
            $target_file = $image_dir . $file_name;

            // Check if file is an image
            $check = getimagesize($_FILES['image']['tmp_name']);
            if ($check !== false) {
                // Delete old image if exists
                $img_query = "SELECT image FROM blogs WHERE id = :id";
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
                    $update_img = "UPDATE blogs SET image = :image WHERE id = :id";
                    $img_stmt = $pdo->prepare($update_img);
                    $img_stmt->bindParam(':image', $file_name, PDO::PARAM_STR);
                    $img_stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $img_stmt->execute();
                } else {
                    set_flash_message('warning', 'Error uploading image, but blog post was saved.');
                }
            } else {
                set_flash_message('warning', 'Uploaded file is not an image, but blog post was saved.');
            }
        }

        $action_text = ($id > 0) ? 'updated' : 'created';
        set_flash_message('success', "Blog post {$action_text} successfully!");

        header("Location: blogs.php");
        exit;

    } catch (PDOException $e) {
        set_flash_message('danger', 'Error saving blog post: ' . $e->getMessage());
    }
}

// Fetch blog post for editing if ID provided
$edit_mode = false;
$blog_post = [
    'id' => '',
    'title' => '',
    'content' => '',
    'excerpt' => '',
    'author' => '',
    'category' => '',
    'image' => '',
    'published' => 0
];

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_query = "SELECT * FROM blogs WHERE id = :id";
    $edit_stmt = $pdo->prepare($edit_query);
    $edit_stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
    $edit_stmt->execute();

    if ($edit_stmt->rowCount() > 0) {
        $blog_post = $edit_stmt->fetch();
        $edit_mode = true;
    }
}

// Fetch all blog posts for listing
$sql = "SELECT * FROM blogs ORDER BY created_at DESC";
$stmt = $pdo->query($sql);
$blog_posts = $stmt->fetchAll();

// Fetch distinct categories for dropdown
$cat_sql = "SELECT DISTINCT category FROM blogs WHERE category IS NOT NULL ORDER BY category";
$cat_stmt = $pdo->query($cat_sql);
$categories = $cat_stmt->fetchAll();

include 'includes/header.php';
?>

<!-- TinyMCE -->
<script src="https://cdn.tiny.cloud/1/1ksxgzgtonnc1k4kpdno306cy0aydw8tlezaq33570xtg6y9/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>

<!-- Blog Form Section -->
<div class="admin-form">
    <h3><?php echo $edit_mode ? 'Edit Blog Post' : 'Add New Blog Post'; ?></h3>
    <form action="blogs.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $blog_post['id']; ?>">

        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($blog_post['title']); ?>" required>
        </div>

        <div class="form-group">
            <label for="excerpt">Excerpt (Short Description)</label>
            <textarea id="excerpt" name="excerpt" class="form-control" rows="3" required><?php echo htmlspecialchars($blog_post['excerpt']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="content">Content</label>
            <textarea id="content" name="content" class="form-control" rows="10"><?php echo htmlspecialchars($blog_post['content']); ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="author">Author</label>
                <input type="text" id="author" name="author" class="form-control" value="<?php echo htmlspecialchars($blog_post['author']); ?>" required>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" class="form-control" value="<?php echo htmlspecialchars($blog_post['category']); ?>" list="categories" required>
                <datalist id="categories">
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['category']); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
        </div>

        <div class="form-group">
            <label for="image">Featured Image</label>
            <input type="file" id="image" name="image" class="form-control" accept="image/*">
            <?php if (!empty($blog_post['image'])): ?>
                <div class="image-preview">
                    <img src="../images/blogs/<?php echo htmlspecialchars($blog_post['image']); ?>" alt="Current Image">
                    <p>Current image: <?php echo htmlspecialchars($blog_post['image']); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-check">
            <input type="checkbox" id="published" name="published" class="form-check-input" <?php echo $blog_post['published'] ? 'checked' : ''; ?>>
            <label for="published" class="form-check-label">Publish this blog post</label>
        </div>

        <div class="btn-group">
            <button type="submit" name="save_blog" class="btn btn-primary">
                <?php echo $edit_mode ? 'Update Blog Post' : 'Add Blog Post'; ?>
            </button>
            <a href="blogs.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<!-- Blog List Section -->
<div class="admin-form">
    <h3>All Blog Posts</h3>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Category</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($blog_posts) > 0): ?>
                <?php foreach ($blog_posts as $post): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($post['title']); ?></td>
                        <td><?php echo htmlspecialchars($post['author']); ?></td>
                        <td><?php echo htmlspecialchars($post['category']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                        <td>
                            <span class="status-badge <?php echo $post['published'] ? 'status-published' : 'status-draft'; ?>">
                                <?php echo $post['published'] ? 'Published' : 'Draft'; ?>
                            </span>
                        </td>
                        <td class="table-actions">
                            <a href="blogs.php?edit=<?php echo $post['id']; ?>" class="btn btn-primary btn-action">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="blogs.php?toggle=<?php echo $post['id']; ?>" class="btn <?php echo $post['published'] ? 'btn-warning' : 'btn-success'; ?> btn-action">
                                <i class="fas <?php echo $post['published'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                <?php echo $post['published'] ? 'Unpublish' : 'Publish'; ?>
                            </a>
                            <a href="blogs.php?delete=<?php echo $post['id']; ?>" class="btn btn-danger btn-action btn-delete">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No blog posts found. Add your first blog post using the form above.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
// Initialize TinyMCE
tinymce.init({
    selector: '#content',
    height: 400,
    menubar: true,
    plugins: [
        'advlist autolink lists link image charmap print preview anchor',
        'searchreplace visualblocks code fullscreen',
        'insertdatetime media table paste code help wordcount'
    ],
    toolbar: 'undo redo | formatselect | bold italic backcolor | \
            alignleft aligncenter alignright alignjustify | \
            bullist numlist outdent indent | removeformat | help'
});
</script>

<?php include 'includes/footer.php'; ?>