<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sidebar Example</title>
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      display: flex;
    }

    /* Sidebar Base */
    .sidebar {
      width: 250px;
      background: #1e293b;
      color: #fff;
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      overflow-y: auto;
      transition: width 0.3s ease;
    }

    .sidebar.collapsed {
      width: 70px;
    }

    /* Sidebar Header */
    .sidebar-header {
      padding: 20px;
      display: flex;
      align-items: center;
      border-bottom: 1px solid #334155;
    }

    .sidebar-logo {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .sidebar-logo img {
      width: 30px;
      height: 30px;
    }

    .sidebar.collapsed .sidebar-logo span {
      display: none;
    }

    /* Menu Sections */
    .menu-section {
      padding: 15px 0;
    }

    .menu-section-title {
      font-size: 12px;
      text-transform: uppercase;
      color: #94a3b8;
      padding: 0 20px;
      margin-bottom: 8px;
    }

    .menu-list {
      list-style: none;
      margin: 0;
      padding: 0;
    }

    .menu-item {
      margin: 5px 0;
    }

    .menu-link {
      display: flex;
      align-items: center;
      padding: 10px 20px;
      text-decoration: none;
      color: #cbd5e1;
      transition: background 0.2s ease, color 0.2s ease;
    }

    .menu-link:hover,
    .menu-link.active {
      background: #334155;
      color: #fff;
    }

    .menu-icon {
      width: 20px;
      text-align: center;
      margin-right: 15px;
    }

    .sidebar.collapsed .menu-link span {
      display: none;
    }

    /* Toggle Button */
    .toggle-btn {
      position: absolute;
      top: 20px;
      right: -15px;
      background: #1e293b;
      color: #fff;
      border: none;
      border-radius: 50%;
      width: 30px;
      height: 30px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* Main Content */
    .content {
      margin-left: 250px;
      padding: 20px;
      flex: 1;
      transition: margin-left 0.3s ease;
    }

    .sidebar.collapsed ~ .content {
      margin-left: 70px;
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">
        <img src="../images/logo.png" alt="AI-Solution Logo">
        <span>AI-Solution</span>
      </div>
      <button class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></button>
    </div>

    <div class="sidebar-menu">
      <div class="menu-section">
        <h4 class="menu-section-title">Main</h4>
        <ul class="menu-list">
          <li class="menu-item"><a href="index.php" class="menu-link"><i class="menu-icon fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
          <li class="menu-item"><a href="inquiries.php" class="menu-link"><i class="menu-icon fas fa-envelope"></i><span>Manage Inquires</span></a></li>
          <li class="menu-item"><a href="analytics.php" class="menu-link"><i class="menu-icon fas fa-chart-bar"></i><span>Visitor Analytics</span></a></li>
          <li class="menu-item"><a href="chatbot.php" class="menu-link"><i class="menu-icon fas fa-robot"></i><span>AI Assistant</span></a></li>
          <li class="menu-item"><a href="feedback.php" class="menu-link"><i class="menu-icon fas fa-comments"></i><span>Feedback</span></a></li>
          <li class="menu-item"><a href="subscribers.php" class="menu-link"><i class="menu-icon fas fa-envelope"></i><span>Subscribers</span></a></li>
        </ul>
      </div>

      <div class="menu-section">
        <h4 class="menu-section-title">Content</h4>
        <ul class="menu-list">
          <li class="menu-item"><a href="logo.php" class="menu-link"><i class="menu-icon fas fa-image"></i><span>Logo Management</span></a></li>
          <li class="menu-item"><a href="portfolio.php" class="menu-link"><i class="menu-icon fas fa-briefcase"></i><span>Portfolio Management</span></a></li>
          <li class="menu-item"><a href="events.php" class="menu-link"><i class="menu-icon fas fa-calendar"></i><span>Events Management</span></a></li>
          <li class="menu-item"><a href="blogs.php" class="menu-link active"><i class="menu-icon fas fa-blog"></i><span>Blog Management</span></a></li>
          <li class="menu-item"><a href="gallery.php" class="menu-link"><i class="menu-icon fas fa-images"></i><span>Manage Gallery</span></a></li>
        </ul>
      </div>

      <div class="menu-section">
        <h4 class="menu-section-title">User</h4>
        <ul class="menu-list">
          <li class="menu-item"><a href="users.php" class="menu-link"><i class="menu-icon fas fa-users"></i><span>Manage Users</span></a></li>
          <li class="menu-item"><a href="password.php" class="menu-link"><i class="menu-icon fas fa-key"></i><span>Change Password</span></a></li>
          <li class="menu-item"><a href="index.php?logout=true" class="menu-link"><i class="menu-icon fas fa-sign-out-alt"></i><span>Logout</span></a></li>
        </ul>
      </div>
    </div>
  </aside>
  <!-- Sidebar Toggle JS -->
  
<script>
// Enhanced Sidebar Functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    
    // Sidebar toggle functionality
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            
            // Save state to localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            
            // Update main content margin
            updateMainContentMargin(isCollapsed);
            
            // Dispatch custom event for other components
            window.dispatchEvent(new CustomEvent('sidebarToggle', { 
                detail: { collapsed: isCollapsed } 
            }));
        });
    }
    
    // Mobile menu toggle
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Close sidebar on mobile when clicking outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 992) {
            if (!sidebar.contains(e.target) && !mobileMenuToggle?.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });
    
    // Restore sidebar state from localStorage
    const savedState = localStorage.getItem('sidebarCollapsed');
    if (savedState === 'true' && window.innerWidth > 992) {
        sidebar.classList.add('collapsed');
        updateMainContentMargin(true);
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            sidebar.classList.remove('active');
            const isCollapsed = sidebar.classList.contains('collapsed');
            updateMainContentMargin(isCollapsed);
        } else {
            // On mobile, ensure main content has no margin
            const mainContent = document.querySelector('.main-content');
            if (mainContent) {
                mainContent.style.marginLeft = '0';
            }
        }
    });
    
    // Update main content margin based on sidebar state
    function updateMainContentMargin(isCollapsed) {
        const mainContent = document.querySelector('.main-content');
        if (mainContent && window.innerWidth > 992) {
            if (isCollapsed) {
                mainContent.style.marginLeft = '7rem';
            } else {
                mainContent.style.marginLeft = '28rem';
            }
        }
    }
    
    // Add loading state for navigation links
    const navLinks = sidebar.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Don't add loading state for external links
            if (this.href.includes('http') && !this.href.includes(window.location.origin)) {
                return;
            }
            
            // Add loading state
            const originalText = this.querySelector('.nav-text')?.textContent;
            if (originalText) {
                this.classList.add('loading');
                this.querySelector('.nav-text').textContent = 'Loading...';
                this.style.pointerEvents = 'none';
                
                // Remove loading state after a delay (in case page doesn't load)
                setTimeout(() => {
                    this.classList.remove('loading');
                    this.querySelector('.nav-text').textContent = originalText;
                    this.style.pointerEvents = 'auto';
                }, 5000);
            }
        });
    });
    
    // Quick action button functionality
    const quickActionBtns = sidebar.querySelectorAll('.quick-action-btn');
    quickActionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const title = this.getAttribute('title');
            
            switch(title) {
                case 'View Website':
                    window.open('../index.html', '_blank');
                    break;
                case 'Help':
                    // Add help functionality here
                    showHelpModal();
                    break;
                case 'Notifications':
                    // Toggle notification panel if it exists
                    const notificationPanel = document.getElementById('notificationPanel');
                    if (notificationPanel) {
                        notificationPanel.classList.toggle('active');
                    }
                    break;
            }
        });
    });
    
    // Profile menu functionality
    const profileMenuToggle = document.querySelector('.profile-menu-toggle');
    if (profileMenuToggle) {
        profileMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            // Toggle profile menu dropdown
            const profileMenu = document.getElementById('profileMenu');
            if (profileMenu) {
                profileMenu.classList.toggle('active');
            } else {
                createProfileMenu();
            }
        });
    }
    
    // Create profile menu dropdown
    function createProfileMenu() {
        const menu = document.createElement('div');
        menu.id = 'profileMenu';
        menu.className = 'profile-menu';
        menu.innerHTML = `
            <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <div class="menu-divider"></div>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        `;
        
        const profileCard = document.querySelector('.user-profile-card');
        profileCard.appendChild(menu);
        
        // Position the menu
        menu.style.top = '100%';
        menu.style.right = '0';
        
        // Close menu when clicking outside
        document.addEventListener('click', function() {
            menu.remove();
        });
    }
    
    // Help modal function
    function showHelpModal() {
        // Create and show a help modal
        const modal = document.createElement('div');
        modal.className = 'help-modal';
        modal.innerHTML = `
            <div class="modal-content">
                <h3>Help & Support</h3>
                <p>Need assistance? Contact our support team or check our documentation.</p>
                <div class="modal-actions">
                    <button class="btn-primary">Contact Support</button>
                    <button class="btn-secondary">View Documentation</button>
                </div>
                <button class="modal-close"><i class="fas fa-times"></i></button>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Close modal functionality
        modal.querySelector('.modal-close').addEventListener('click', function() {
            modal.remove();
        });
        
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }
    
    // Keyboard navigation support
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + B to toggle sidebar
        if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
            e.preventDefault();
            if (sidebarToggle) {
                sidebarToggle.click();
            }
        }
        
        // Escape key to close sidebar on mobile
        if (e.key === 'Escape' && window.innerWidth <= 992) {
            sidebar.classList.remove('active');
        }
    });
    
    // Add hover effects for touch devices
    if ('ontouchstart' in window) {
        document.querySelectorAll('.nav-link, .quick-action-btn').forEach(el => {
            el.addEventListener('touchstart', function() {
                this.classList.add('touch-active');
            });
            
            el.addEventListener('touchend', function() {
                setTimeout(() => {
                    this.classList.remove('touch-active');
                }, 150);
            });
        });
    }
    
    // Initialize sidebar with correct state
    function initSidebar() {
        // Add loaded class for entrance animation
        setTimeout(() => {
            sidebar.classList.add('loaded');
        }, 100);
    }
    
    // Initialize the sidebar
    initSidebar();
});
</script>

</body>
</html>
