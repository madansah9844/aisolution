<?php
/**
 * Events Management for AI-Solutions Admin Panel
 */

session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$page_title = "Events Management";

// Process event deletion if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        // Get image filename before deleting event
        $img_query = "SELECT image FROM events WHERE id = :id";
        $img_stmt = $pdo->prepare($img_query);
        $img_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $img_stmt->execute();
        $image = $img_stmt->fetch()['image'] ?? '';

        // Delete the event
        $delete_sql = "DELETE FROM events WHERE id = :id";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $delete_stmt->execute();

        // Delete the image file if it exists
        if (!empty($image) && file_exists("../images/events/" . $image)) {
            unlink("../images/events/" . $image);
        }

        set_flash_message('success', 'Event deleted successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Error deleting event: ' . $e->getMessage());
    }

    header("Location: events.php");
    exit;
}

// Toggle featured status if requested
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    try {
        // Get current status
        $status_query = "SELECT featured FROM events WHERE id = :id";
        $status_stmt = $pdo->prepare($status_query);
        $status_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $status_stmt->execute();
        $current_status = $status_stmt->fetch()['featured'];

        // Toggle status
        $new_status = $current_status ? 0 : 1;
        $toggle_sql = "UPDATE events SET featured = :status WHERE id = :id";
        $toggle_stmt = $pdo->prepare($toggle_sql);
        $toggle_stmt->bindParam(':status', $new_status, PDO::PARAM_INT);
        $toggle_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $toggle_stmt->execute();

        $status_text = $new_status ? 'featured' : 'unfeatured';
        set_flash_message('success', "Event marked as {$status_text} successfully.");
    } catch (PDOException $e) {
        set_flash_message('danger', 'Error updating event status: ' . $e->getMessage());
    }

    header("Location: events.php");
    exit;
}

// Process event form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_event'])) {
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    $date = sanitize_input($_POST['date']);
    $time = sanitize_input($_POST['time']);
    $location = sanitize_input($_POST['location']);
    $featured = isset($_POST['featured']) ? 1 : 0;
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    try {
        if ($id > 0) {
            // Updating existing event
            $sql = "UPDATE events SET
                    title = :title,
                    description = :description,
                    date = :date,
                    time = :time,
                    location = :location,
                    featured = :featured
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        } else {
            // Creating new event
            $sql = "INSERT INTO events (title, description, date, time, location, featured)
                    VALUES (:title, :description, :date, :time, :location, :featured)";
            $stmt = $pdo->prepare($sql);
        }

        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->bindParam(':time', $time, PDO::PARAM_STR);
        $stmt->bindParam(':location', $location, PDO::PARAM_STR);
        $stmt->bindParam(':featured', $featured, PDO::PARAM_INT);
        $stmt->execute();

        if ($id === 0) {
            $id = $pdo->lastInsertId();
        }

        // Handle image upload if provided
        if (!empty($_FILES['image']['name'])) {
            $image_dir = "../images/events/";

            // Create directory if it doesn't exist
            if (!file_exists($image_dir)) {
                mkdir($image_dir, 0755, true);
            }

            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = 'event_' . $id . '_' . time() . '.' . $file_extension;
            $target_file = $image_dir . $file_name;

            // Check if file is an image
            $check = getimagesize($_FILES['image']['tmp_name']);
            if ($check !== false) {
                // Delete old image if exists
                $img_query = "SELECT image FROM events WHERE id = :id";
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
                    $update_img = "UPDATE events SET image = :image WHERE id = :id";
                    $img_stmt = $pdo->prepare($update_img);
                    $img_stmt->bindParam(':image', $file_name, PDO::PARAM_STR);
                    $img_stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $img_stmt->execute();
                } else {
                    set_flash_message('warning', 'Error uploading image, but event was saved.');
                }
            } else {
                set_flash_message('warning', 'Uploaded file is not an image, but event was saved.');
            }
        }

        $action_text = ($id > 0) ? 'updated' : 'created';
        set_flash_message('success', "Event {$action_text} successfully!");

        header("Location: events.php");
        exit;

    } catch (PDOException $e) {
        set_flash_message('danger', 'Error saving event: ' . $e->getMessage());
    }
}

// Fetch event for editing if ID provided
$edit_mode = false;
$event = [
    'id' => '',
    'title' => '',
    'description' => '',
    'date' => date('Y-m-d'),
    'time' => date('H:i'),
    'location' => '',
    'image' => '',
    'featured' => 0
];

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_query = "SELECT * FROM events WHERE id = :id";
    $edit_stmt = $pdo->prepare($edit_query);
    $edit_stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
    $edit_stmt->execute();

    if ($edit_stmt->rowCount() > 0) {
        $event = $edit_stmt->fetch();
        $edit_mode = true;
    }
}

// Fetch all events for listing
$sql = "SELECT * FROM events ORDER BY date DESC";
$stmt = $pdo->query($sql);
$events = $stmt->fetchAll();

// Get current date
$current_date = date('Y-m-d');

include 'includes/header.php';
?>

<!-- Event Form Section -->
<div class="admin-form">
    <h3><?php echo $edit_mode ? 'Edit Event' : 'Add New Event'; ?></h3>
    <form action="events.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $event['id']; ?>">

        <div class="form-group">
            <label for="title">Event Title</label>
            <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($event['title']); ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="date">Event Date</label>
                <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($event['date']); ?>" required>
            </div>

            <div class="form-group">
                <label for="time">Event Time</label>
                <input type="time" id="time" name="time" class="form-control" value="<?php echo htmlspecialchars($event['time']); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="location">Event Location</label>
            <input type="text" id="location" name="location" class="form-control" value="<?php echo htmlspecialchars($event['location']); ?>" required>
        </div>

        <div class="form-group">
            <label for="description">Event Description</label>
            <textarea id="description" name="description" class="form-control" rows="6" required><?php echo htmlspecialchars($event['description']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="image">Event Image</label>
            <input type="file" id="image" name="image" class="form-control" accept="image/*">
            <?php if (!empty($event['image'])): ?>
                <div class="image-preview">
                    <img src="../images/events/<?php echo htmlspecialchars($event['image']); ?>" alt="Current Event Image">
                    <p>Current image: <?php echo htmlspecialchars($event['image']); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-check">
            <input type="checkbox" id="featured" name="featured" class="form-check-input" <?php echo $event['featured'] ? 'checked' : ''; ?>>
            <label for="featured" class="form-check-label">Feature this event on homepage</label>
        </div>

        <div class="btn-group">
            <button type="submit" name="save_event" class="btn btn-primary">
                <?php echo $edit_mode ? 'Update Event' : 'Add Event'; ?>
            </button>
            <a href="events.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<!-- Event List Section -->
<div class="admin-form">
    <h3>All Events</h3>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Date & Time</th>
                <th>Location</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($events) > 0): ?>
                <?php foreach ($events as $e): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($e['title']); ?></td>
                        <td>
                            <?php
                            echo date('M d, Y', strtotime($e['date']));
                            echo ' at ';
                            echo date('g:i A', strtotime($e['time']));
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($e['location']); ?></td>
                        <td>
                            <span class="status-badge <?php echo strtotime($e['date']) >= strtotime($current_date) ? 'status-upcoming' : 'status-past'; ?>">
                                <?php echo strtotime($e['date']) >= strtotime($current_date) ? 'Upcoming' : 'Past'; ?>
                            </span>
                            <?php if ($e['featured']): ?>
                                <span class="status-badge status-featured">Featured</span>
                            <?php endif; ?>
                        </td>
                        <td class="table-actions">
                            <a href="events.php?edit=<?php echo $e['id']; ?>" class="btn btn-primary btn-action">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="events.php?toggle=<?php echo $e['id']; ?>" class="btn <?php echo $e['featured'] ? 'btn-warning' : 'btn-success'; ?> btn-action">
                                <i class="fas fa-star"></i>
                                <?php echo $e['featured'] ? 'Unfeature' : 'Feature'; ?>
                            </a>
                            <a href="events.php?delete=<?php echo $e['id']; ?>" class="btn btn-danger btn-action btn-delete">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">No events found. Add your first event using the form above.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>