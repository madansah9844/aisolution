<?php
/**
 * Modern Admin Header Component
 * Clean header with profile dropdown and theme toggle
 */

// Get user info
$user_id = $_SESSION['admin_id'];
$username = $_SESSION['admin_username'];
$user_role = $_SESSION['admin_role'];
?>

<!-- Modern Admin Header -->
<header class="admin-header">
    <div class="header-left">
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="header-title">
            <h1><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h1>
            <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($username); ?></p>
        </div>
    </div>

    <div class="header-right">
        <!-- Search Bar -->
        <div class="search-container">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search anything..." id="globalSearch">
            </div>
        </div>

        <!-- Notifications -->
        <div class="notification-dropdown">
            <button class="notification-btn" id="notificationBtn">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">3</span>
            </button>
            
            <div class="notification-menu" id="notificationMenu">
                <div class="notification-header">
                    <h4>Notifications</h4>
                    <button class="mark-all-read">Mark all read</button>
                </div>
                <div class="notification-list">
                    <div class="notification-item unread">
                        <div class="notification-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="notification-content">
                            <h5>New Inquiry</h5>
                            <p>You have 2 new inquiries waiting for review.</p>
                            <span class="notification-time">2 minutes ago</span>
                        </div>
                    </div>
                    <div class="notification-item">
                        <div class="notification-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="notification-content">
                            <h5>New Feedback</h5>
                            <p>Customer left a 5-star feedback.</p>
                            <span class="notification-time">1 hour ago</span>
                        </div>
                    </div>
                    <div class="notification-item">
                        <div class="notification-icon">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="notification-content">
                            <h5>Chatbot Updated</h5>
                            <p>Chatbot responses have been updated.</p>
                            <span class="notification-time">3 hours ago</span>
                        </div>
                    </div>
                </div>
                <div class="notification-footer">
                    <a href="#" class="view-all-notifications">View All Notifications</a>
                </div>
            </div>
        </div>

        <!-- Theme Toggle -->
        <div class="theme-toggle-container">
            <button class="theme-toggle" id="themeToggle" title="Toggle Dark/Light Mode">
                <div class="theme-icon">
                    <i class="fas fa-sun light-icon"></i>
                    <i class="fas fa-moon dark-icon"></i>
                </div>
            </button>
        </div>

        <!-- Profile Dropdown -->
        <div class="profile-dropdown">
            <button class="profile-btn" id="profileBtn">
                <div class="profile-avatar">
                    <img src="../images/logo.png" alt="Profile" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="avatar-fallback">
                        <?php echo strtoupper(substr($username, 0, 2)); ?>
                    </div>
                </div>
                <div class="profile-info">
                    <span class="profile-name"><?php echo htmlspecialchars($username); ?></span>
                    <span class="profile-role"><?php echo ucfirst($user_role); ?></span>
                </div>
                <i class="fas fa-chevron-down"></i>
            </button>

            <div class="profile-menu" id="profileMenu">
                <div class="profile-menu-header">
                    <div class="profile-menu-avatar">
                        <div class="avatar-circle">
                            <?php echo strtoupper(substr($username, 0, 2)); ?>
                        </div>
                    </div>
                    <div class="profile-menu-info">
                        <h4><?php echo htmlspecialchars($username); ?></h4>
                        <p><?php echo ucfirst($user_role); ?></p>
                        <span class="online-status">
                            <i class="fas fa-circle"></i> Online
                        </span>
                    </div>
                </div>
                
                <div class="profile-menu-divider"></div>
                
                <div class="profile-menu-items">
                    <a href="profile.php" class="profile-menu-item">
                        <i class="fas fa-user"></i>
                        <span>My Profile</span>
                    </a>
                    <a href="password.php" class="profile-menu-item">
                        <i class="fas fa-key"></i>
                        <span>Change Password</span>
                    </a>
                    <a href="assistant.php" class="profile-menu-item">
                        <i class="fas fa-robot"></i>
                        <span>AI Assistant</span>
                    </a>
                    <a href="../index.html" class="profile-menu-item" target="_blank">
                        <i class="fas fa-external-link-alt"></i>
                        <span>View Website</span>
                    </a>
                </div>
                
                <div class="profile-menu-divider"></div>
                
                <div class="profile-menu-footer">
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<style>
/* Modern Admin Header Styles */
.admin-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 7rem;
    padding: 0 2rem;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.05);
    position: sticky;
    top: 0;
    z-index: 999;
    transition: all 0.3s ease;
}

[data-theme="dark"] .admin-header {
    background: rgba(45, 45, 45, 0.95);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
}

.header-left {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.mobile-menu-toggle {
    display: none;
    background: none;
    border: none;
    color: var(--dark-color);
    font-size: 2rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.mobile-menu-toggle:hover {
    background: rgba(255, 215, 0, 0.1);
    color: var(--primary-color);
}

.header-title h1 {
    font-size: 2.4rem;
    font-weight: 700;
    margin: 0;
    color: var(--dark-color);
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-subtitle {
    font-size: 1.4rem;
    color: var(--gray-color);
    margin: 0;
    font-weight: 400;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 2rem;
}

/* Search Container */
.search-container {
    position: relative;
}

.search-box {
    display: flex;
    align-items: center;
    background: rgba(0, 0, 0, 0.05);
    border-radius: 2.5rem;
    padding: 1rem 1.5rem;
    width: 30rem;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

[data-theme="dark"] .search-box {
    background: rgba(255, 255, 255, 0.1);
}

.search-box:focus-within {
    border-color: var(--primary-color);
    background: rgba(255, 215, 0, 0.1);
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.2);
}

.search-box i {
    color: var(--gray-color);
    margin-right: 1rem;
    font-size: 1.4rem;
}

.search-box input {
    border: none;
    background: none;
    outline: none;
    flex: 1;
    font-size: 1.4rem;
    color: var(--dark-color);
}

.search-box input::placeholder {
    color: var(--gray-color);
}

/* Notifications */
.notification-dropdown {
    position: relative;
}

.notification-btn {
    position: relative;
    background: none;
    border: none;
    color: var(--dark-color);
    font-size: 2rem;
    cursor: pointer;
    padding: 1rem;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.notification-btn:hover {
    background: rgba(255, 215, 0, 0.1);
    color: var(--primary-color);
    transform: scale(1.1);
}

.notification-badge {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: linear-gradient(135deg, #DC143C, #FF6347);
    color: white;
    font-size: 1rem;
    font-weight: 600;
    padding: 0.2rem 0.6rem;
    border-radius: 1rem;
    min-width: 1.8rem;
    text-align: center;
    animation: pulse 2s infinite;
}

.notification-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--light-color);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 1rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    width: 35rem;
    max-height: 50rem;
    overflow-y: auto;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-1rem);
    transition: all 0.3s ease;
    z-index: 1001;
}

[data-theme="dark"] .notification-menu {
    background: var(--light-color);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.notification-menu.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.notification-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 2rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.notification-header h4 {
    margin: 0;
    font-size: 1.6rem;
    color: var(--dark-color);
}

.mark-all-read {
    background: none;
    border: none;
    color: var(--primary-color);
    font-size: 1.2rem;
    cursor: pointer;
    font-weight: 500;
}

.notification-list {
    max-height: 30rem;
    overflow-y: auto;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.notification-item:hover {
    background: rgba(255, 215, 0, 0.05);
}

.notification-item.unread {
    background: rgba(255, 215, 0, 0.05);
    border-left: 3px solid var(--primary-color);
}

.notification-icon {
    width: 4rem;
    height: 4rem;
    border-radius: 50%;
    background: rgba(255, 215, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1.5rem;
    color: var(--primary-color);
    font-size: 1.6rem;
}

.notification-content {
    flex: 1;
}

.notification-content h5 {
    margin: 0 0 0.5rem 0;
    font-size: 1.4rem;
    color: var(--dark-color);
}

.notification-content p {
    margin: 0 0 0.5rem 0;
    font-size: 1.3rem;
    color: var(--gray-color);
    line-height: 1.4;
}

.notification-time {
    font-size: 1.1rem;
    color: var(--gray-color);
}

.notification-footer {
    padding: 1.5rem 2rem;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
    text-align: center;
}

.view-all-notifications {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
}

/* Theme Toggle */
.theme-toggle-container {
    position: relative;
}

.theme-toggle {
    background: rgba(0, 0, 0, 0.05);
    border: none;
    width: 5rem;
    height: 2.5rem;
    border-radius: 1.25rem;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

[data-theme="dark"] .theme-toggle {
    background: rgba(255, 255, 255, 0.1);
}

.theme-toggle:hover {
    transform: scale(1.05);
}

.theme-icon {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.light-icon,
.dark-icon {
    position: absolute;
    font-size: 1.2rem;
    transition: all 0.3s ease;
}

.light-icon {
    color: #FFD700;
    opacity: 1;
}

.dark-icon {
    color: #ffffff;
    opacity: 0;
}

[data-theme="dark"] .light-icon {
    opacity: 0;
}

[data-theme="dark"] .dark-icon {
    opacity: 1;
}

/* Profile Dropdown */
.profile-dropdown {
    position: relative;
}

.profile-btn {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem 1rem;
    border-radius: 1rem;
    transition: all 0.3s ease;
}

.profile-btn:hover {
    background: rgba(255, 215, 0, 0.1);
}

.profile-avatar {
    position: relative;
    width: 4rem;
    height: 4rem;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid var(--primary-color);
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-fallback {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    display: none;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.6rem;
    color: var(--dark-color);
}

.profile-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.profile-name {
    font-size: 1.4rem;
    font-weight: 600;
    color: var(--dark-color);
    margin: 0;
}

.profile-role {
    font-size: 1.2rem;
    color: var(--gray-color);
    margin: 0;
}

.profile-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--light-color);
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: 1rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    width: 30rem;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-1rem);
    transition: all 0.3s ease;
    z-index: 1001;
    overflow: hidden;
}

[data-theme="dark"] .profile-menu {
    background: var(--light-color);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.profile-menu.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.profile-menu-header {
    padding: 2rem;
    background: linear-gradient(135deg, rgba(255, 215, 0, 0.1), rgba(255, 165, 0, 0.05));
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.profile-menu-avatar .avatar-circle {
    width: 5rem;
    height: 5rem;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 2rem;
    color: var(--dark-color);
}

.profile-menu-info h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1.6rem;
    color: var(--dark-color);
}

.profile-menu-info p {
    margin: 0 0 0.5rem 0;
    font-size: 1.3rem;
    color: var(--gray-color);
}

.online-status {
    font-size: 1.2rem;
    color: var(--success-color);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.online-status i {
    font-size: 0.8rem;
}

.profile-menu-divider {
    height: 1px;
    background: rgba(0, 0, 0, 0.1);
    margin: 0 2rem;
}

.profile-menu-items {
    padding: 1rem 0;
}

.profile-menu-item {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1.2rem 2rem;
    color: var(--dark-color);
    text-decoration: none;
    transition: all 0.3s ease;
}

.profile-menu-item:hover {
    background: rgba(255, 215, 0, 0.05);
    color: var(--primary-color);
}

.profile-menu-item i {
    width: 2rem;
    font-size: 1.4rem;
}

.profile-menu-footer {
    padding: 1rem 2rem;
}

.logout-btn {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1.2rem 2rem;
    color: var(--danger-color);
    text-decoration: none;
    transition: all 0.3s ease;
    border-radius: 0.5rem;
}

.logout-btn:hover {
    background: rgba(220, 20, 60, 0.1);
}

.logout-btn i {
    width: 2rem;
    font-size: 1.4rem;
}

/* Mobile Responsive */
@media (max-width: 992px) {
    .mobile-menu-toggle {
        display: block;
    }
    
    .search-container {
        display: none;
    }
    
    .header-right {
        gap: 1rem;
    }
    
    .profile-info {
        display: none;
    }
    
    .search-box {
        width: 25rem;
    }
}

@media (max-width: 768px) {
    .admin-header {
        padding: 0 1rem;
        height: 6rem;
    }
    
    .header-title h1 {
        font-size: 2rem;
    }
    
    .page-subtitle {
        display: none;
    }
    
    .notification-menu,
    .profile-menu {
        width: 90vw;
        right: -2rem;
    }
}

/* Animations */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}
</style>
