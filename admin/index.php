<?php
/**
 * Admin Dashboard
 */

session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Handle logout
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

$page_title = "Dashboard";

// Get statistics
try {
    // Get total inquiries
    $inquiries_sql = "SELECT COUNT(*) as total FROM inquiries";
    $inquiries_stmt = $pdo->query($inquiries_sql);
    $total_inquiries = $inquiries_stmt->fetch()['total'];

    // Get total feedback
    $feedback_sql = "SELECT COUNT(*) as total FROM feedback";
    $feedback_stmt = $pdo->query($feedback_sql);
    $total_feedback = $feedback_stmt->fetch()['total'];

    // Get published blogs
    $blogs_sql = "SELECT COUNT(*) as total FROM blogs WHERE published = 1";
    $blogs_stmt = $pdo->query($blogs_sql);
    $published_blogs = $blogs_stmt->fetch()['total'];

    // Get active subscribers
    $subscribers_sql = "SELECT COUNT(*) as total FROM subscribers WHERE status = 'active'";
    $subscribers_stmt = $pdo->query($subscribers_sql);
    $active_subscribers = $subscribers_stmt->fetch()['total'];

} catch (PDOException $e) {
    $total_inquiries = 0;
    $total_feedback = 0;
    $published_blogs = 0;
    $active_subscribers = 0;
}

include 'includes/header.php';
?>

<div class="welcome-section">
    <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h2>
    <p>Here's your AI-Solutions admin dashboard overview.</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="card-icon">
            <i class="fas fa-envelope"></i>
        </div>
        <div class="card-content">
            <h3>Total Inquiries</h3>
            <div class="stat-value"><?php echo $total_inquiries; ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="card-icon">
            <i class="fas fa-comments"></i>
        </div>
        <div class="card-content">
            <h3>Total Feedback</h3>
            <div class="stat-value"><?php echo $total_feedback; ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="card-icon">
            <i class="fas fa-blog"></i>
        </div>
        <div class="card-content">
            <h3>Published Blogs</h3>
            <div class="stat-value"><?php echo $published_blogs; ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="card-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="card-content">
            <h3>Active Subscribers</h3>
            <div class="stat-value"><?php echo $active_subscribers; ?></div>
        </div>
    </div>
</div>

<div class="quick-actions-section">
    <h3>Quick Actions</h3>
    <div class="quick-actions-grid">
        <a href="blogs.php" class="quick-action-card">
            <div class="quick-action-icon">
                <i class="fas fa-blog"></i>
            </div>
            <div class="quick-action-content">
                <h4>Manage Blogs</h4>
                <p>Create and edit blog posts</p>
            </div>
        </a>

        <a href="events.php" class="quick-action-card">
            <div class="quick-action-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="quick-action-content">
                <h4>Manage Events</h4>
                <p>Add and update events</p>
            </div>
        </a>

        <a href="inquiries.php" class="quick-action-card">
            <div class="quick-action-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="quick-action-content">
                <h4>View Inquiries</h4>
                <p>Manage customer inquiries</p>
            </div>
        </a>

        <a href="logo.php" class="quick-action-card">
            <div class="quick-action-icon">
                <i class="fas fa-cog"></i>
            </div>
            <div class="quick-action-content">
                <h4>Settings</h4>
                <p>Update company information</p>
            </div>
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>