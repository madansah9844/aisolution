<?php
// Include database configuration
require_once 'admin/includes/config.php';

// Track page visit
track_visit($pdo);

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirect to blogs page if ID is not valid
    header("Location: blogs.php");
    exit;
}

// Get blog post ID
$id = (int)$_GET['id'];

// Fetch blog post
$sql = "SELECT * FROM blogs WHERE id = :id AND published = 1";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();

// Check if blog post exists
if ($stmt->rowCount() === 0) {
    // Redirect to blogs page if post doesn't exist
    header("Location: blogs.php");
    exit;
}

$post = $stmt->fetch();

// Format the date
$date_formatted = date('F j, Y', strtotime($post['created_at']));

// Query for related posts (same category)
$related_sql = "SELECT * FROM blogs WHERE category = :category AND id != :id AND published = 1 ORDER BY RAND() LIMIT 3";
$related_stmt = $pdo->prepare($related_sql);
$related_stmt->bindParam(':category', $post['category'], PDO::PARAM_STR);
$related_stmt->bindParam(':id', $id, PDO::PARAM_INT);
$related_stmt->execute();
$related_posts = $related_stmt->fetchAll();

// Query for recent posts sidebar
$recent_sql = "SELECT id, title, created_at FROM blogs WHERE published = 1 AND id != :id ORDER BY created_at DESC LIMIT 5";
$recent_stmt = $pdo->prepare($recent_sql);
$recent_stmt->bindParam(':id', $id, PDO::PARAM_INT);
$recent_stmt->execute();
$recent_posts = $recent_stmt->fetchAll();

// Query to get all categories for sidebar
$category_sql = "SELECT DISTINCT category FROM blogs WHERE category IS NOT NULL AND published = 1 ORDER BY category";
$categories = $pdo->query($category_sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - AI-Solutions Blog</title>
    <meta name="description" content="<?php echo htmlspecialchars($post['excerpt']); ?>">
    <meta name="keywords" content="AI Solutions, <?php echo htmlspecialchars($post['category']); ?>, <?php echo htmlspecialchars($post['title']); ?>, Business Innovation">
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
        /* Blog Single Page Styles */
        .blog-single {
            padding: 5rem 0;
        }

        .blog-container {
            display: grid;
            grid-template-columns: 1fr 30rem;
            gap: 4rem;
        }

        .blog-main {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .blog-main-img {
            width: 100%;
            height: 40rem;
            overflow: hidden;
        }

        .blog-main-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .blog-main-content {
            padding: 3rem;
        }

        .blog-header {
            margin-bottom: 3rem;
        }

        .blog-category {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
            border-radius: 2rem;
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
        }

        .blog-title {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
            line-height: 1.3;
        }

        .blog-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            color: var(--gray-color);
            font-size: 1.4rem;
        }

        .blog-meta-item {
            display: flex;
            align-items: center;
        }

        .blog-meta-item i {
            margin-right: 0.8rem;
            color: var(--primary-color);
        }

        .blog-content {
            line-height: 1.8;
            margin-bottom: 3rem;
        }

        .blog-content p {
            margin-bottom: 2rem;
        }

        .blog-content h2 {
            font-size: 2.4rem;
            margin: 3rem 0 1.5rem;
        }

        .blog-content h3 {
            font-size: 2rem;
            margin: 2.5rem 0 1.5rem;
        }

        .blog-content ul, .blog-content ol {
            margin-left: 2rem;
            margin-bottom: 2rem;
        }

        .blog-content li {
            margin-bottom: 1rem;
        }

        .blog-content blockquote {
            background-color: rgba(79, 70, 229, 0.05);
            border-left: 4px solid var(--primary-color);
            padding: 2rem;
            margin: 2rem 0;
            font-style: italic;
        }

        .blog-content img {
            max-width: 100%;
            height: auto;
            border-radius: var(--border-radius);
            margin: 2rem 0;
        }

        .blog-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-light);
        }

        .blog-tag {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: var(--gray-light);
            color: var(--dark-color);
            border-radius: 2rem;
            font-size: 1.2rem;
            transition: var(--transition);
        }

        .blog-tag:hover {
            background-color: var(--primary-color);
            color: var(--light-color);
        }

        .blog-author-box {
            display: flex;
            align-items: center;
            background-color: rgba(79, 70, 229, 0.05);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-top: 4rem;
        }

        .author-avatar {
            width: 8rem;
            height: 8rem;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 2rem;
            flex-shrink: 0;
        }

        .author-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .author-info {
            flex: 1;
        }

        .author-name {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .author-bio {
            color: var(--gray-color);
            margin-bottom: 1rem;
        }

        .author-social {
            display: flex;
            gap: 1rem;
        }

        .author-social a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            background-color: var(--light-color);
            color: var(--primary-color);
            border-radius: 50%;
            transition: var(--transition);
        }

        .author-social a:hover {
            background-color: var(--primary-color);
            color: var(--light-color);
        }

        .blog-navigation {
            display: flex;
            justify-content: space-between;
            margin: 4rem 0;
            padding: 2rem 0;
            border-top: 1px solid var(--gray-light);
            border-bottom: 1px solid var(--gray-light);
        }

        .nav-prev, .nav-next {
            display: flex;
            align-items: center;
        }

        .nav-prev i, .nav-next i {
            font-size: 1.8rem;
        }

        .nav-prev i {
            margin-right: 1rem;
        }

        .nav-next i {
            margin-left: 1rem;
        }

        /* Sidebar Styles */
        .blog-sidebar {
            position: sticky;
            top: 2rem;
        }

        .sidebar-widget {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            overflow: hidden;
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

        /* Related Posts */
        .related-posts {
            margin-top: 5rem;
        }

        .related-title {
            font-size: 2.4rem;
            margin-bottom: 3rem;
            text-align: center;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(30rem, 1fr));
            gap: 3rem;
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
            .blog-author-box {
                flex-direction: column;
                text-align: center;
            }

            .author-avatar {
                margin-right: 0;
                margin-bottom: 1.5rem;
            }

            .author-social {
                justify-content: center;
            }

            .related-grid {
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
                <h1><?php echo htmlspecialchars($post['title']); ?></h1>
                <p>Blog Post | <?php echo htmlspecialchars($post['category']); ?></p>
            </div>
        </div>
    </section>

    <!-- Blog Single Section -->
    <section class="blog-single section">
        <div class="container">
            <div class="blog-container">
                <div class="blog-main">
                    <div class="blog-main-img">
                        <?php if (!empty($post['image'])): ?>
                            <img src="images/blogs/<?php echo htmlspecialchars($post['image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                        <?php else: ?>
                            <img src="images/blog-default.jpg" alt="<?php echo htmlspecialchars($post['title']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="blog-main-content">
                        <div class="blog-header">
                            <?php if (!empty($post['category'])): ?>
                                <span class="blog-category"><?php echo htmlspecialchars($post['category']); ?></span>
                            <?php endif; ?>
                            <h1 class="blog-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                            <div class="blog-meta">
                                <div class="blog-meta-item">
                                    <i class="fas fa-user"></i>
                                    <span><?php echo htmlspecialchars($post['author']); ?></span>
                                </div>
                                <div class="blog-meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo $date_formatted; ?></span>
                                </div>
                                <div class="blog-meta-item">
                                    <i class="fas fa-folder"></i>
                                    <span><?php echo htmlspecialchars($post['category']); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="blog-content">
                            <?php
                            // Format the blog content with proper paragraphs
                            $content = nl2br(htmlspecialchars($post['content']));
                            echo $content;
                            ?>
                        </div>
                        <div class="blog-tags">
                            <a href="#" class="blog-tag">Artificial Intelligence</a>
                            <a href="#" class="blog-tag">Machine Learning</a>
                            <a href="#" class="blog-tag">Business Innovation</a>
                            <a href="#" class="blog-tag">Digital Transformation</a>
                        </div>
                        <div class="blog-author-box">
                            <div class="author-avatar">
                                <img src="images/author-default.jpg" alt="<?php echo htmlspecialchars($post['author']); ?>">
                            </div>
                            <div class="author-info">
                                <h3 class="author-name"><?php echo htmlspecialchars($post['author']); ?></h3>
                                <p class="author-bio">AI technology expert and innovation consultant with over 10 years of experience helping businesses implement AI solutions to enhance digital employee experiences.</p>
                                <div class="author-social">
                                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                                    <a href="#"><i class="fab fa-twitter"></i></a>
                                    <a href="#"><i class="fas fa-globe"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
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
                                        <span class="recent-post-date"><?php echo date('F j, Y', strtotime($recent['created_at'])); ?></span>
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

            <!-- Blog Navigation -->
            <div class="blog-navigation">
                <a href="blogs.php" class="btn btn-sm">
                    <i class="fas fa-th-large"></i> All Blog Posts
                </a>
                <div>
                    <a href="blogs.php?category=<?php echo urlencode($post['category']); ?>" class="btn btn-primary">More from <?php echo htmlspecialchars($post['category']); ?></a>
                </div>
            </div>

            <!-- Related Posts -->
            <?php if (count($related_posts) > 0): ?>
                <div class="related-posts">
                    <h3 class="related-title">Related Posts</h3>
                    <div class="related-grid">
                        <?php foreach($related_posts as $related): ?>
                            <div class="blog-card">
                                <div class="blog-img">
                                    <?php if (!empty($related['image'])): ?>
                                        <img src="images/blogs/<?php echo htmlspecialchars($related['image']); ?>" alt="<?php echo htmlspecialchars($related['title']); ?>">
                                    <?php else: ?>
                                        <img src="images/blog-default.jpg" alt="<?php echo htmlspecialchars($related['title']); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="blog-content">
                                    <?php if (!empty($related['category'])): ?>
                                        <span class="blog-category"><?php echo htmlspecialchars($related['category']); ?></span>
                                    <?php endif; ?>
                                    <h3 class="blog-title"><?php echo htmlspecialchars($related['title']); ?></h3>
                                    <div class="blog-meta">
                                        <span>By <?php echo htmlspecialchars($related['author']); ?></span>
                                        <span><?php echo date('F j, Y', strtotime($related['created_at'])); ?></span>
                                    </div>
                                    <p class="blog-excerpt"><?php echo htmlspecialchars($related['excerpt']); ?></p>
                                    <div class="blog-footer">
                                        <a href="blog-single.php?id=<?php echo $related['id']; ?>" class="btn btn-sm">Read More</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
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
