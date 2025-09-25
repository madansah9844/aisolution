<?php
// Include database configuration
require_once 'admin/includes/config.php';

// Track page visit
track_visit($pdo);

// Get gallery category filter if any
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Query to get all categories for filter
$category_sql = "SELECT DISTINCT category FROM gallery WHERE category IS NOT NULL ORDER BY category";
$categories = $pdo->query($category_sql)->fetchAll();

// Query for gallery items
$gallery_sql = "SELECT * FROM gallery";
if (!empty($category_filter)) {
    $gallery_sql .= " WHERE category = :category";
    $stmt = $pdo->prepare($gallery_sql);
    $stmt->bindParam(':category', $category_filter, PDO::PARAM_STR);
    $stmt->execute();
} else {
    $stmt = $pdo->query($gallery_sql);
}
$gallery_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - AI-Solution</title>
    <meta name="description" content="View our gallery of AI technology events, team photos, client implementations, and more at AI-Solution.">
    <meta name="keywords" content="AI Gallery, Technology Photos, Company Events, Team Images, Client Implementations">
    <meta name="author" content="AI-Solution">

    <!-- CSS Files -->
    <link rel="stylesheet" href="css/styles.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Lightbox CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">

    <!-- Favicon -->
    <link rel="icon" href="images/logo.png" type="image/png">

    <style>
        /* Gallery specific styles */
        .gallery-filters {
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

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(30rem, 1fr));
            gap: 2rem;
        }

        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            height: 25rem;
            transition: var(--transition);
        }

        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .gallery-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .gallery-item:hover .gallery-img {
            transform: scale(1.05);
        }

        .gallery-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.7) 0%, rgba(0, 0, 0, 0) 50%);
            opacity: 0;
            transition: opacity 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 2rem;
        }

        .gallery-item:hover .gallery-overlay {
            opacity: 1;
        }

        .gallery-title {
            color: var(--light-color);
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .gallery-category {
            color: var(--light-color);
            font-size: 1.4rem;
            opacity: 0.8;
            margin-bottom: 1rem;
        }

        .gallery-zoom {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            width: 4rem;
            height: 4rem;
            background-color: var(--primary-color);
            color: var(--light-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            transform: scale(0);
            transition: transform 0.3s ease;
        }

        .gallery-item:hover .gallery-zoom {
            transform: scale(1);
        }

        /* Lightbox Customization */
        .lightbox-overlay {
            background-color: rgba(0, 0, 0, 0.9);
        }

        .lb-data .lb-details {
            width: 100%;
            text-align: center;
        }

        .lb-data .lb-caption {
            font-size: 1.6rem;
            font-weight: 600;
            color: var(--light-color);
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

        @media (max-width: 992px) {
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(25rem, 1fr));
            }
        }

        @media (max-width: 768px) {
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(20rem, 1fr));
            }
        }

        @media (max-width: 576px) {
            .gallery-grid {
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
                        <img src="images/logo.png" alt="AI-Solution Logo" class="logo-img">
                        <span class="logo-text">AI-Solution</span>
                    </a>
                </div>
                <nav>
                    <ul class="nav-menu">
                        <li><a href="index.html">Home</a></li>
                        <li><a href="about.html">About Us</a></li>
                        <li><a href="services.html">Services</a></li>
                        <li><a href="portfolio.php">Portfolio</a></li>
                        <li><a href="events.php">Events</a></li>
                        <li><a href="blogs.php">Blogs</a></li>
                        <li><a href="gallery.php" class="active">Gallery</a></li>
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
                <h1>Our Gallery</h1>
                <p>Explore images from our events, team activities, and implementations</p>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="gallery" class="section">
        <div class="container">
            <div class="section-header">
                <h2>Image Gallery</h2>
                <p>Browse through moments captured at AI-Solution events, team activities, and client implementations</p>
            </div>

            <!-- Gallery Filters -->
            <div class="gallery-filters">
                <a href="gallery.php" class="filter-btn <?php echo empty($category_filter) ? 'active' : ''; ?>">All</a>
                <?php foreach ($categories as $category): ?>
                    <a href="gallery.php?category=<?php echo urlencode($category['category']); ?>"
                       class="filter-btn <?php echo ($category_filter === $category['category']) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($category['category']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Gallery Grid -->
            <div class="gallery-grid">
                <?php if (count($gallery_items) > 0): ?>
                    <?php foreach($gallery_items as $item): ?>
                        <div class="gallery-item">
                            <?php if (!empty($item['image'])): ?>
                                <a href="images/gallery/<?php echo htmlspecialchars($item['image']); ?>" data-lightbox="gallery" data-title="<?php echo htmlspecialchars($item['title'] . ' - ' . $item['description']); ?>">
                                    <img src="images/gallery/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="gallery-img">
                                    <div class="gallery-overlay">
                                        <h3 class="gallery-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                                        <?php if (!empty($item['category'])): ?>
                                            <p class="gallery-category"><?php echo htmlspecialchars($item['category']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="gallery-zoom">
                                        <i class="fas fa-search-plus"></i>
                                    </div>
                                </a>
                            <?php else: ?>
                                <a href="images/gallery-default.jpg" data-lightbox="gallery" data-title="<?php echo htmlspecialchars($item['title'] . ' - ' . $item['description']); ?>">
                                    <img src="images/gallery-default.jpg" alt="<?php echo htmlspecialchars($item['title']); ?>" class="gallery-img">
                                    <div class="gallery-overlay">
                                        <h3 class="gallery-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                                        <?php if (!empty($item['category'])): ?>
                                            <p class="gallery-category"><?php echo htmlspecialchars($item['category']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="gallery-zoom">
                                        <i class="fas fa-search-plus"></i>
                                    </div>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-items">
                        <h3>No gallery items found</h3>
                        <p>There are currently no images in this category. Please check back later or select a different category.</p>
                        <a href="gallery.php" class="btn btn-primary">View All Images</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="cta" class="section bg-primary">
        <div class="container">
            <div class="cta-content">
                <h2>Interested in Working with AI-Solution?</h2>
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
                        <img src="images/logo.png" alt="AI-Solution Logo">
                        <span>AI-Solution</span>
                    </div>
                    <p>At AI-Solution, we harness the power of AI to enhance digital employee experiences, driving innovation and productivity for businesses worldwide.</p>
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
                <p>&copy; <span id="year"></span> AI-Solution. All rights reserved. | Designed with <i class="fas fa-heart"></i> by AI-Solution Team</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="js/main.js"></script>

    <!-- Lightbox JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script>
        // Initialize Lightbox
        lightbox.option({
            'resizeDuration': 200,
            'wrapAround': true,
            'albumLabel': 'Image %1 of %2',
            'fadeDuration': 300,
            'imageFadeDuration': 300
        });
    </script>
</body>
</html>
