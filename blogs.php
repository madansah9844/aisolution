<?php
// Include database configuration
require_once 'admin/includes/config.php';

// Track page visit
track_visit($pdo);

// Get pagination parameters
$posts_per_page = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $posts_per_page;

// Get blog category filter if any
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Query to get all categories for filter
$category_sql = "SELECT DISTINCT category FROM blogs WHERE category IS NOT NULL AND published = 1 ORDER BY category";
$categories = $pdo->query($category_sql)->fetchAll();

// Count total published blog posts for pagination
$count_sql = "SELECT COUNT(*) as total FROM blogs WHERE published = 1";
if (!empty($category_filter)) {
    $count_sql .= " AND category = :category";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->bindParam(':category', $category_filter, PDO::PARAM_STR);
    $count_stmt->execute();
} else {
    $count_stmt = $pdo->query($count_sql);
}
$total_count = $count_stmt->fetch()['total'];
$total_pages = ceil($total_count / $posts_per_page);

// Query for blog posts
$blog_sql = "SELECT * FROM blogs WHERE published = 1";
if (!empty($category_filter)) {
    $blog_sql .= " AND category = :category";
}
$blog_sql .= " ORDER BY created_at DESC LIMIT :offset, :limit";

$blog_stmt = $pdo->prepare($blog_sql);
if (!empty($category_filter)) {
    $blog_stmt->bindParam(':category', $category_filter, PDO::PARAM_STR);
}
$blog_stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$blog_stmt->bindParam(':limit', $posts_per_page, PDO::PARAM_INT);
$blog_stmt->execute();
$blog_posts = $blog_stmt->fetchAll();

// Query for recent posts sidebar
$recent_sql = "SELECT id, title, created_at FROM blogs WHERE published = 1 ORDER BY created_at DESC LIMIT 5";
$recent_posts = $pdo->query($recent_sql)->fetchAll();

// Format date helper function
function format_blog_date($date) {
    return date('F j, Y', strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog - AI-Solutions</title>
    <meta name="description" content="Read the latest insights about AI technology, digital employee experience, and business innovation from AI-Solutions' experts.">
    <meta name="keywords" content="AI Blog, AI Technology, Digital Employee Experience, Business Innovation, AI Trends">
    <meta name="author" content="AI-Solutions">

    <!-- CSS Files -->
    <link rel="stylesheet" href="css/styles.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" href="images/logo.png" type="image/png">

    <style>
        /* Blog-specific styles */
        .blog-container {
            display: grid;
            grid-template-columns: 1fr 30rem;
            gap: 4rem;
        }

        .blog-filters {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 3rem;
            gap: 1rem;
        }

        .filter-btn {
            padding: 0.8rem 1.5rem;
            background-color: var(--light-color);
            color: var(--dark-color);
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-btn:hover, .filter-btn.active {
            background-color: var(--primary-color);
            color: var(--light-color);
            border-color: var(--primary-color);
        }

        .blog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(100%, 35rem), 1fr));
            gap: 3rem;
            margin-bottom: 4rem;
        }

        .blog-card {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .blog-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .blog-img {
            height: 22rem;
            overflow: hidden;
        }

        .blog-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .blog-card:hover .blog-img img {
            transform: scale(1.05);
        }

        .blog-content {
            padding: 2rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .blog-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            font-size: 1.4rem;
            color: var(--gray-color);
        }

        .blog-category {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
            border-radius: 2rem;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .blog-title {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .blog-excerpt {
            margin-bottom: 2rem;
            flex-grow: 1;
        }

        .blog-footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-light);
        }

        .blog-author {
            display: flex;
            align-items: center;
        }

        .author-name {
            font-weight: 600;
        }

        /* Sidebar styles */
        .blog-sidebar {
            position: sticky;
            top: 2rem;
        }

        .sidebar-widget {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-bottom: 3rem;
        }

        .widget-title {
            font-size: 2rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .search-form {
            display: flex;
        }

        .search-form input {
            flex: 1;
            padding: 1rem;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius) 0 0 var(--border-radius);
            outline: none;
        }

        .search-form button {
            background-color: var(--primary-color);
            color: var(--light-color);
            border: none;
            padding: 0 1.5rem;
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
            cursor: pointer;
            transition: var(--transition);
        }

        .search-form button:hover {
            background-color: var(--primary-dark);
        }

        .categories-list, .recent-posts-list {
            list-style: none;
        }

        .categories-list li, .recent-posts-list li {
            border-bottom: 1px solid var(--gray-light);
        }

        .categories-list li:last-child, .recent-posts-list li:last-child {
            border-bottom: none;
        }

        .categories-list a, .recent-posts-list a {
            display: block;
            padding: 1rem 0;
            color: var(--dark-color);
            transition: var(--transition);
        }

        .categories-list a:hover, .recent-posts-list a:hover {
            color: var(--primary-color);
            padding-left: 0.5rem;
        }

        .categories-list a {
            display: flex;
            justify-content: space-between;
        }

        .category-count {
            background-color: var(--gray-light);
            color: var(--dark-color);
            border-radius: 2rem;
            padding: 0.2rem 1rem;
            font-size: 1.2rem;
        }

        .recent-post-date {
            font-size: 1.2rem;
            color: var(--gray-color);
            margin-top: 0.5rem;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 4rem;
        }

        .pagination-list {
            display: flex;
            list-style: none;
        }

        .pagination-item {
            margin: 0 0.5rem;
        }

        .pagination-link {
            display: block;
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-light);
            background-color: var(--light-color);
            color: var(--dark-color);
            transition: var(--transition);
        }

        .pagination-link:hover, .pagination-link.active {
            background-color: var(--primary-color);
            color: var(--light-color);
            border-color: var(--primary-color);
        }

        .no-posts {
            grid-column: 1 / -1;
            padding: 5rem;
            text-align: center;
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .no-posts h3 {
            font-size: 2.4rem;
            margin-bottom: 1rem;
        }

        .no-posts p {
            font-size: 1.6rem;
            color: var(--gray-color);
            margin-bottom: 2rem;
        }

        @media (max-width: 992px) {
            .blog-container {
                grid-template-columns: 1fr;
            }

            .blog-sidebar {
                position: static;
                margin-top: 4rem;
            }
        }

        @media (max-width: 768px) {
            .blog-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header id="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="index.html">
                        <img src="images/logo.png" alt="AI-Solutions Logo" class="logo-img">
                        <span class="logo-text">AI-Solutions</span>
                    </a>
                </div>
                <nav>
                    <ul class="nav-menu">
                        <li><a href="index.html">Home</a></li>
                        <li><a href="about.html">About Us</a></li>
                        <li><a href="services.html">Services</a></li>
                        <li><a href="portfolio.php">Portfolio</a></li>
                        <li><a href="events.php">Events</a></li>
                        <li><a href="blogs.php" class="active">Blogs</a></li>
                        <li><a href="gallery.php">Gallery</a></li>
                        <li><a href="contact.html">Contact</a></li>
                    </ul>
                    <div class="admin-btn">
                        <a href="admin/login.php" class="btn">Go Admin</a>
                    </div>
                    <div class="menu-toggle">
                        <i class="fas fa-bars"></i>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <!-- Page Banner -->
    <section class="page-banner">
        <div class="container">
            <div class="banner-content">
                <h1>Blog & Insights</h1>
                <p>Latest thoughts, ideas, and insights about AI technology and business innovation</p>
            </div>
        </div>
    </section>

    <!-- Blog Section -->
    <section id="blog" class="section">
        <div class="container">
            <div class="blog-container">
                <div class="blog-main">
                    <!-- Blog Filters -->
                    <div class="blog-filters">
                        <a href="blogs.php" class="filter-btn <?php echo empty($category_filter) ? 'active' : ''; ?>">All Posts</a>
                        <?php foreach ($categories as $category): ?>
                            <a href="blogs.php?category=<?php echo urlencode($category['category']); ?>"
                               class="filter-btn <?php echo ($category_filter === $category['category']) ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($category['category']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Blog Grid -->
                    <div class="blog-grid">
                        <?php if (count($blog_posts) > 0): ?>
                            <?php foreach($blog_posts as $post): ?>
                                <div class="blog-card">
                                    <div class="blog-img">
                                        <?php if (!empty($post['image'])): ?>
                                            <img src="images/blogs/<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                        <?php else: ?>
                                            <img src="images/blog-default.jpg" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="blog-content">
                                        <?php if (!empty($post['category'])): ?>
                                            <span class="blog-category"><?php echo htmlspecialchars($post['category']); ?></span>
                                        <?php endif; ?>
                                        <h3 class="blog-title"><?php echo htmlspecialchars($post['title']); ?></h3>
                                        <div class="blog-meta">
                                            <span>By <?php echo htmlspecialchars($post['author']); ?></span>
                                            <span><?php echo format_blog_date($post['created_at']); ?></span>
                                        </div>
                                        <p class="blog-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                                        <div class="blog-footer">
                                            <a href="blog-single.php?id=<?php echo $post['id']; ?>" class="btn btn-sm">Read More</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-posts">
                                <h3>No blog posts found</h3>
                                <p>There are currently no blog posts in this category. Please check back later or select a different category.</p>
                                <a href="blogs.php" class="btn btn-primary">View All Blog Posts</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <ul class="pagination-list">
                                <?php if ($page > 1): ?>
                                    <li class="pagination-item">
                                        <a href="blogs.php?page=<?php echo $page - 1; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>" class="pagination-link">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="pagination-item">
                                        <a href="blogs.php?page=<?php echo $i; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>"
                                           class="pagination-link <?php echo ($page === $i) ? 'active' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="pagination-item">
                                        <a href="blogs.php?page=<?php echo $page + 1; ?><?php echo !empty($category_filter) ? '&category=' . urlencode($category_filter) : ''; ?>" class="pagination-link">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Blog Sidebar -->
                <div class="blog-sidebar">
                    <!-- Search Widget -->
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Search</h3>
                        <form action="blogs.php" method="GET" class="search-form">
                            <input type="text" name="search" placeholder="Search blog posts..." required>
                            <button type="submit"><i class="fas fa-search"></i></button>
                        </form>
                    </div>

                    <!-- Categories Widget -->
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Categories</h3>
                        <ul class="categories-list">
                            <?php foreach ($categories as $category): ?>
                                <?php
                                // Count posts in this category
                                $category_count_sql = "SELECT COUNT(*) as count FROM blogs WHERE category = :category AND published = 1";
                                $category_count_stmt = $pdo->prepare($category_count_sql);
                                $category_count_stmt->bindParam(':category', $category['category'], PDO::PARAM_STR);
                                $category_count_stmt->execute();
                                $category_count = $category_count_stmt->fetch()['count'];
                                ?>
                                <li>
                                    <a href="blogs.php?category=<?php echo urlencode($category['category']); ?>">
                                        <?php echo htmlspecialchars($category['category']); ?>
                                        <span class="category-count"><?php echo $category_count; ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Recent Posts Widget -->
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Recent Posts</h3>
                        <ul class="recent-posts-list">
                            <?php foreach ($recent_posts as $recent): ?>
                                <li>
                                    <a href="blog-single.php?id=<?php echo $recent['id']; ?>">
                                        <span class="recent-post-title"><?php echo htmlspecialchars($recent['title']); ?></span>
                                        <span class="recent-post-date"><?php echo format_blog_date($recent['created_at']); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Newsletter Widget -->
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Newsletter</h3>
                        <p>Subscribe to our newsletter to receive the latest updates and insights.</p>
                        <form action="subscribe.php" method="POST" class="newsletter-form">
                            <div class="form-group">
                                <input type="email" name="email" placeholder="Your email address" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Subscribe</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="cta" class="section bg-primary">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Enhance Your Digital Employee Experience?</h2>
                <p>Contact us today to discuss how our AI solutions can improve productivity and drive innovation in your business.</p>
                <a href="contact.html" class="btn btn-light">Contact Us</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-about">
                    <div class="footer-logo">
                        <img src="images/logo.png" alt="AI-Solutions Logo">
                        <span>AI-Solutions</span>
                    </div>
                    <p>At AI-Solutions, we harness the power of AI to enhance digital employee experiences, driving innovation and productivity for businesses worldwide.</p>
                    <div class="footer-social">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="footer-links">
                    <h3>Useful Links</h3>
                    <ul>
                        <li><a href="services.html">Our Solutions</a></li>
                        <li><a href="portfolio.php">Customer Stories</a></li>
                        <li><a href="services.html#ai-prototyping">AI Prototyping</a></li>
                        <li><a href="events.php">Events</a></li>
                        <li><a href="#">Terms & Policies</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h3>Navigation</h3>
                    <ul>
                        <li><a href="index.html">Home</a></li>
                        <li><a href="about.html">About Us</a></li>
                        <li><a href="services.html">Services</a></li>
                        <li><a href="portfolio.php">Portfolio</a></li>
                        <li><a href="blogs.php">Blogs</a></li>
                        <li><a href="contact.html">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <h3>Contact Us</h3>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> <span>1 Software Way, Sunderland, UK</span></li>
                        <li><i class="fas fa-phone"></i> <a href="tel:+441911234567">+44 191 123 4567</a></li>
                        <li><i class="fas fa-envelope"></i> <a href="mailto:info@ai-solution.com">info@ai-solution.com</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <span id="year"></span> AI-Solutions. All rights reserved. | Designed with <i class="fas fa-heart"></i> by AI-Solutions Team</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="js/main.js"></script>
</body>
</html>
