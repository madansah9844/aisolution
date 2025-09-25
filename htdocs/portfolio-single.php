<?php
// Include database configuration
require_once 'admin/includes/config.php';

// Track page visit
track_visit($pdo);

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirect to portfolio page if ID is not valid
    header("Location: portfolio.php");
    exit;
}

// Get portfolio item ID
$id = (int)$_GET['id'];

// Fetch portfolio item
$sql = "SELECT * FROM portfolio WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();

// Check if portfolio item exists
if ($stmt->rowCount() === 0) {
    // Redirect to portfolio page if item doesn't exist
    header("Location: portfolio.php");
    exit;
}

$portfolio = $stmt->fetch();

// Fetch related portfolio items (same category)
$related_sql = "SELECT * FROM portfolio WHERE category = :category AND id != :id ORDER BY RAND() LIMIT 3";
$related_stmt = $pdo->prepare($related_sql);
$related_stmt->bindParam(':category', $portfolio['category'], PDO::PARAM_STR);
$related_stmt->bindParam(':id', $id, PDO::PARAM_INT);
$related_stmt->execute();
$related_items = $related_stmt->fetchAll();

// Format the date
$date_formatted = date('F d, Y', strtotime($portfolio['created_at']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($portfolio['title']); ?> - AI-Solutions Portfolio</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($portfolio['description'], 0, 150)); ?>">
    <meta name="keywords" content="AI Solutions, <?php echo htmlspecialchars($portfolio['category']); ?>, <?php echo htmlspecialchars($portfolio['client']); ?>, Case Study">
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
        /* Portfolio Single Page Styles */
        .portfolio-single {
            padding: 5rem 0;
        }

        .portfolio-details {
            display: grid;
            grid-template-columns: 1fr 30rem;
            gap: 4rem;
            margin-bottom: 4rem;
        }

        .portfolio-main {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .portfolio-main-img {
            width: 100%;
            height: 40rem;
            overflow: hidden;
        }

        .portfolio-main-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .portfolio-main-content {
            padding: 3rem;
        }

        .portfolio-single-title {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }

        .portfolio-meta {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 2rem;
            gap: 1.5rem;
        }

        .portfolio-meta-item {
            display: flex;
            align-items: center;
            color: var(--gray-color);
        }

        .portfolio-meta-item i {
            margin-right: 0.8rem;
            color: var(--primary-color);
        }

        .portfolio-description {
            line-height: 1.8;
            margin-bottom: 2rem;
        }

        .portfolio-description p {
            margin-bottom: 1.5rem;
        }

        .portfolio-sidebar {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            padding: 3rem;
        }

        .sidebar-widget {
            margin-bottom: 3rem;
        }

        .sidebar-widget:last-child {
            margin-bottom: 0;
        }

        .widget-title {
            font-size: 2rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .info-list {
            list-style: none;
        }

        .info-list li {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-light);
        }

        .info-list li:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--dark-color);
        }

        .info-value {
            color: var(--gray-color);
        }

        .portfolio-navigation {
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

        .related-portfolio {
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
            .portfolio-details {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
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
                <h1><?php echo htmlspecialchars($portfolio['title']); ?></h1>
                <p>Case Study | <?php echo htmlspecialchars($portfolio['category']); ?></p>
            </div>
        </div>
    </section>

    <!-- Portfolio Single Section -->
    <section class="portfolio-single section">
        <div class="container">
            <div class="portfolio-details">
                <div class="portfolio-main">
                    <div class="portfolio-main-img">
                        <?php if (!empty($portfolio['image'])): ?>
                            <img src="images/portfolio/<?php echo htmlspecialchars($portfolio['image']); ?>" alt="<?php echo htmlspecialchars($portfolio['title']); ?>">
                        <?php else: ?>
                            <img src="images/portfolio-default.jpg" alt="<?php echo htmlspecialchars($portfolio['title']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="portfolio-main-content">
                        <h2 class="portfolio-single-title"><?php echo htmlspecialchars($portfolio['title']); ?></h2>
                        <div class="portfolio-meta">
                            <?php if (!empty($portfolio['client'])): ?>
                                <div class="portfolio-meta-item">
                                    <i class="fas fa-building"></i>
                                    <span>Client: <?php echo htmlspecialchars($portfolio['client']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($portfolio['category'])): ?>
                                <div class="portfolio-meta-item">
                                    <i class="fas fa-tag"></i>
                                    <span>Category: <?php echo htmlspecialchars($portfolio['category']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="portfolio-meta-item">
                                <i class="fas fa-calendar"></i>
                                <span>Date: <?php echo $date_formatted; ?></span>
                            </div>
                        </div>
                        <div class="portfolio-description">
                            <?php
                            // Format the description text with proper paragraphs
                            $description = nl2br(htmlspecialchars($portfolio['description']));
                            echo $description;
                            ?>
                        </div>
                        <a href="contact.html" class="btn btn-primary">Discuss Your Project</a>
                    </div>
                </div>
                <div class="portfolio-sidebar">
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Project Information</h3>
                        <ul class="info-list">
                            <?php if (!empty($portfolio['client'])): ?>
                                <li>
                                    <span class="info-label">Client:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($portfolio['client']); ?></span>
                                </li>
                            <?php endif; ?>
                            <?php if (!empty($portfolio['category'])): ?>
                                <li>
                                    <span class="info-label">Category:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($portfolio['category']); ?></span>
                                </li>
                            <?php endif; ?>
                            <li>
                                <span class="info-label">Date:</span>
                                <span class="info-value"><?php echo $date_formatted; ?></span>
                            </li>
                            <li>
                                <span class="info-label">Status:</span>
                                <span class="info-value">Completed</span>
                            </li>
                        </ul>
                    </div>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Services Provided</h3>
                        <ul class="info-list">
                            <li>
                                <span class="info-value">• AI Strategy and Planning</span>
                            </li>
                            <li>
                                <span class="info-value">• Solution Architecture</span>
                            </li>
                            <li>
                                <span class="info-value">• Custom Development</span>
                            </li>
                            <li>
                                <span class="info-value">• Integration Services</span>
                            </li>
                            <li>
                                <span class="info-value">• Training and Support</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Portfolio Navigation -->
            <div class="portfolio-navigation">
                <a href="portfolio.php" class="btn btn-sm">
                    <i class="fas fa-th-large"></i> Back to Portfolio
                </a>
                <div>
                    <a href="contact.html" class="btn btn-primary">Discuss Your Project</a>
                </div>
            </div>

            <!-- Related Portfolio Items -->
            <?php if (count($related_items) > 0): ?>
                <div class="related-portfolio">
                    <h3 class="related-title">Related Case Studies</h3>
                    <div class="related-grid">
                        <?php foreach ($related_items as $item): ?>
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
                                    <p class="portfolio-desc"><?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>...</p>
                                    <div class="portfolio-footer">
                                        <a href="portfolio-single.php?id=<?php echo $item['id']; ?>" class="btn btn-sm">View Case Study</a>
                                        <span class="portfolio-date"><?php echo date('M Y', strtotime($item['created_at'])); ?></span>
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
