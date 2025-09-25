<?php
// Include database configuration
require_once 'admin/includes/config.php';

// Track page visit
track_visit($pdo);

// Get portfolio category filter if any
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Query to get all categories for filter
$category_sql = "SELECT DISTINCT category FROM portfolio WHERE category IS NOT NULL ORDER BY category";
$categories = $pdo->query($category_sql)->fetchAll();

// Prepare portfolio query
$portfolio_sql = "SELECT * FROM portfolio";

// Apply category filter if any
if (!empty($category_filter)) {
    $portfolio_sql .= " WHERE category = :category";
    $stmt = $pdo->prepare($portfolio_sql);
    $stmt->bindParam(':category', $category_filter, PDO::PARAM_STR);
    $stmt->execute();
} else {
    $stmt = $pdo->query($portfolio_sql);
}

$portfolio_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio - AI-Solutions</title>
    <meta name="description" content="Explore AI-Solutions' portfolio of successful AI implementations and case studies. See how our innovative solutions have transformed businesses.">
    <meta name="keywords" content="AI Portfolio, AI Case Studies, Business Transformation, AI Implementation, Success Stories">
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
        /* Portfolio-specific styles */
        .portfolio-filters {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 3rem;
        }

        .filter-btn {
            padding: 0.8rem 1.5rem;
            margin: 0.5rem;
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

        .portfolio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(30rem, 1fr));
            gap: 3rem;
        }

        .portfolio-item {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .portfolio-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .portfolio-img {
            height: 25rem;
            overflow: hidden;
        }

        .portfolio-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .portfolio-item:hover .portfolio-img img {
            transform: scale(1.05);
        }

        .portfolio-content {
            padding: 2rem;
        }

        .portfolio-category {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
            border-radius: 2rem;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .portfolio-title {
            font-size: 2rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .portfolio-client {
            color: var(--gray-color);
            font-style: italic;
            margin-bottom: 1.5rem;
        }

        .portfolio-desc {
            margin-bottom: 2rem;
        }

        .portfolio-footer {
            border-top: 1px solid var(--gray-light);
            padding-top: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .no-items {
            grid-column: 1 / -1;
            padding: 5rem;
            text-align: center;
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .no-items h3 {
            font-size: 2.4rem;
            margin-bottom: 1rem;
        }

        .no-items p {
            font-size: 1.6rem;
            color: var(--gray-color);
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .portfolio-grid {
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
                        <li><a href="portfolio.php" class="active">Portfolio</a></li>
                        <li><a href="events.php">Events</a></li>
                        <li><a href="blogs.php">Blogs</a></li>
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
                <h1>Our Portfolio</h1>
                <p>Explore our successful AI implementations and case studies</p>
            </div>
        </div>
    </section>

    <!-- Portfolio Section -->
    <section id="portfolio" class="section">
        <div class="container">
            <div class="section-header">
                <h2>Client Success Stories</h2>
                <p>Browse through our portfolio of successful AI implementations and case studies</p>
            </div>

            <!-- Portfolio Filters -->
            <div class="portfolio-filters">
                <a href="portfolio.php" class="filter-btn <?php echo empty($category_filter) ? 'active' : ''; ?>">All</a>
                <?php foreach ($categories as $category): ?>
                    <a href="portfolio.php?category=<?php echo urlencode($category['category']); ?>"
                       class="filter-btn <?php echo ($category_filter === $category['category']) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($category['category']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Portfolio Grid -->
            <div class="portfolio-grid">
                <?php if (count($portfolio_items) > 0): ?>
                    <?php foreach ($portfolio_items as $item): ?>
                        <div class="portfolio-item">
                            <div class="portfolio-img">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="images/portfolio/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                <?php else: ?>
                                    <img src="images/portfolio-default.jpg" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="portfolio-content">
                                <?php if (!empty($item['category'])): ?>
                                    <span class="portfolio-category"><?php echo htmlspecialchars($item['category']); ?></span>
                                <?php endif; ?>
                                <h3 class="portfolio-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                                <?php if (!empty($item['client'])): ?>
                                    <p class="portfolio-client">Client: <?php echo htmlspecialchars($item['client']); ?></p>
                                <?php endif; ?>
                                <p class="portfolio-desc"><?php echo htmlspecialchars(substr($item['description'], 0, 150)); ?>...</p>
                                <div class="portfolio-footer">
                                    <a href="portfolio-single.php?id=<?php echo $item['id']; ?>" class="btn btn-sm">View Case Study</a>
                                    <span class="portfolio-date"><?php echo date('M Y', strtotime($item['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-items">
                        <h3>No portfolio items found</h3>
                        <p>There are currently no portfolio items in this category. Please check back later or select a different category.</p>
                        <a href="portfolio.php" class="btn btn-primary">View All Portfolio Items</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="cta" class="section bg-primary">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Transform Your Business with AI?</h2>
                <p>Contact us today to discuss how our AI solutions can enhance your digital employee experience and drive business innovation.</p>
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
