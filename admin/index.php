<?php
/**
 * Admin Dashboard
 */

session_start();
require_once 'includes/config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$page_title = "Dashboard";
include 'includes/header.php';
?>

<div class="dashboard-container">
    <div class="welcome-section">
        <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h2>
        <p>Here's your AI-Solutions admin dashboard.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">0</div>
                <div class="stat-label">Total Inquiries</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-comments"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">0</div>
                <div class="stat-label">Total Feedback</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-blog"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">0</div>
                <div class="stat-label">Published Blogs</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">0</div>
                <div class="stat-label">Active Subscribers</div>
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

            <a href="chatbot.php" class="quick-action-card">
                <div class="quick-action-icon">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="quick-action-content">
                    <h4>Chatbot Settings</h4>
                    <p>Configure AI responses</p>
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
</div>

<style>
.dashboard-container {
    max-width: 120rem;
    margin: 0 auto;
}

.welcome-section {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: var(--dark-color);
    padding: 3rem;
    border-radius: 1rem;
    margin-bottom: 3rem;
    text-align: center;
}

.welcome-section h2 {
    font-size: 2.8rem;
    margin-bottom: 0.5rem;
}

.welcome-section p {
    font-size: 1.6rem;
    opacity: 0.9;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(25rem, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: var(--light-color);
    border-radius: 1rem;
    padding: 2.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 2rem;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(255, 215, 0, 0.15);
}

.stat-icon {
    width: 6rem;
    height: 6rem;
    border-radius: 1rem;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.4rem;
    color: var(--dark-color);
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 3.2rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 1.4rem;
    color: var(--gray-color);
}

.quick-actions-section {
    margin-bottom: 3rem;
}

.quick-actions-section h3 {
    font-size: 2.4rem;
    margin-bottom: 2rem;
    color: var(--dark-color);
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(25rem, 1fr));
    gap: 2rem;
}

.quick-action-card {
    background: var(--light-color);
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    text-decoration: none;
    color: var(--dark-color);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 2rem;
}

.quick-action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(255, 215, 0, 0.15);
    color: var(--dark-color);
}

.quick-action-icon {
    width: 5rem;
    height: 5rem;
    border-radius: 1rem;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--dark-color);
}

.quick-action-content h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1.6rem;
}

.quick-action-content p {
    margin: 0;
    font-size: 1.3rem;
    color: var(--gray-color);
}
</style>

<?php include 'includes/footer.php'; ?>