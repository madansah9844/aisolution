<?php
// Include database configuration
require_once 'admin/includes/config.php';

// Track page visit
track_visit($pdo);

// Get search query
$search_query = isset($_GET['q']) ? sanitize_input($_GET['q']) : '';
$filter = isset($_GET['filter']) ? sanitize_input($_GET['filter']) : 'all';

// Initialize results arrays
$blog_results = [];
$portfolio_results = [];
$event_results = [];
$gallery_results = [];

// Process search only if query is provided
if (!empty($search_query)) {
    // Prepare search query with wildcards for LIKE
    $search_param = "%{$search_query}%";

    // Search blogs if filter is 'all' or 'blogs'
    if ($filter === 'all' || $filter === 'blogs') {
        $blog_sql = "SELECT id, title, excerpt, author, category, image, created_at
                   FROM blogs
                   WHERE published = 1
                   AND (title LIKE :search OR content LIKE :search OR excerpt LIKE :search OR category LIKE :search)
                   ORDER BY created_at DESC
                   LIMIT 10";
        $blog_stmt = $pdo->prepare($blog_sql);
        $blog_stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
        $blog_stmt->execute();
        $blog_results = $blog_stmt->fetchAll();
    }

    // Search portfolio if filter is 'all' or 'portfolio'
    if ($filter === 'all' || $filter === 'portfolio') {
        $portfolio_sql = "SELECT id, title, description, client, category, image, created_at
                        FROM portfolio
                        WHERE title LIKE :search OR description LIKE :search OR client LIKE :search OR category LIKE :search
                        ORDER BY created_at DESC
                        LIMIT 10";
        $portfolio_stmt = $pdo->prepare($portfolio_sql);
        $portfolio_stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
        $portfolio_stmt->execute();
        $portfolio_results = $portfolio_stmt->fetchAll();
    }

    // Search events if filter is 'all' or 'events'
    if ($filter === 'all' || $filter === 'events') {
        $event_sql = "SELECT id, title, description, date, time, location, image, featured
                    FROM events
                    WHERE title LIKE :search OR description LIKE :search OR location LIKE :search
                    ORDER BY date DESC
                    LIMIT 10";
        $event_stmt = $pdo->prepare($event_sql);
        $event_stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
        $event_stmt->execute();
        $event_results = $event_stmt->fetchAll();
    }

    // Search gallery if filter is 'all' or 'gallery'
    if ($filter === 'all' || $filter === 'gallery') {
        $gallery_sql = "SELECT id, title, description, category, image, created_at
                      FROM gallery
                      WHERE title LIKE :search OR description LIKE :search OR category LIKE :search
                      ORDER BY created_at DESC
                      LIMIT 10";
        $gallery_stmt = $pdo->prepare($gallery_sql);
        $gallery_stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
        $gallery_stmt->execute();
        $gallery_results = $gallery_stmt->fetchAll();
    }
}

// Calculate total results count
$total_results = count($blog_results) + count($portfolio_results) + count($event_results) + count($gallery_results);

// Helper function to format date
function format_date($date) {
    return date('F j, Y', strtotime($date));
}

// Helper function to highlight search terms in text
function highlight_search_term($text, $search_term) {
    if (empty($search_term)) {
        return $text;
    }

    // Get words from search term and escape special characters
    $words = explode(' ', $search_term);
    $words = array_filter($words, function($word) {
        return strlen($word) > 2; // Only highlight words longer than 2 characters
    });

    if (empty($words)) {
        return $text;
    }

    $pattern = '/' . implode('|', array_map(function($word) {
        return preg_quote($word, '/');
    }, $words)) . '/i';

    return preg_replace($pattern, '<mark>$0</mark>', $text);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo !empty($search_query) ? "Search results for \"" . htmlspecialchars($search_query) . "\"" : "Search"; ?> - AI-Solution</title>
    <meta name="description" content="Search through AI-Solution's blog posts, portfolio items, events, and gallery.">
    <meta name="keywords" content="AI Solution, Search, AI Technology, Digital Employee Experience">
    <meta name="author" content="AI-Solution">

    <!-- CSS Files -->
    <link rel="stylesheet" href="css/styles.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" href="images/logo.png" type="image/png">

    <style>
        /* Search Page Styles */
        .search-container {
            max-width: 80rem;
            margin: 0 auto;
        }

        .search-form {
            margin-bottom: 3rem;
            display: flex;
            gap: 1rem;
            width: 100%;
        }

        .search-input {
            flex: 1;
            padding: 1.2rem 2rem;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1.6rem;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
        }

        .search-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
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

        .search-info {
            margin-bottom: 2rem;
            color: var(--gray-color);
            font-size: 1.6rem;
        }

        .result-section {
            margin-bottom: 4rem;
        }

        .section-title {
            font-size: 2.2rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .result-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(30rem, 1fr));
            gap: 2rem;
        }

        .result-card {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .result-img {
            height: 18rem;
            overflow: hidden;
        }

        .result-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .result-card:hover .result-img img {
            transform: scale(1.05);
        }

        .result-content {
            padding: 2rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .result-category {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
            border-radius: 2rem;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .result-title {
            font-size: 2rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .result-excerpt {
            margin-bottom: 1.5rem;
            color: var(--gray-color);
            flex-grow: 1;
        }

        .result-meta {
            display: flex;
            justify-content: space-between;
            margin-top: auto;
            border-top: 1px solid var(--gray-light);
            padding-top: 1.5rem;
            color: var(--gray-color);
            font-size: 1.3rem;
        }

        .no-results {
            text-align: center;
            padding: 5rem 2rem;
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .no-results h3 {
            font-size: 2.4rem;
            margin-bottom: 1.5rem;
        }

        .no-results p {
            color: var(--gray-color);
            margin-bottom: 2rem;
            font-size: 1.6rem;
        }

        /* Highlighted search terms */
        mark {
            background-color: rgba(255, 230, 0, 0.4);
            padding: 0.1rem 0.2rem;
            border-radius: 0.2rem;
        }

        @media (max-width: 768px) {
            .search-container {
                padding: 0 2rem;
            }

            .search-form {
                flex-direction: column;
            }

            .result-grid {
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
                <h1>Search Results</h1>
                <p><?php echo !empty($search_query) ? "Showing results for \"" . htmlspecialchars($search_query) . "\"" : "Search our website"; ?></p>
            </div>
        </div>
    </section>

    <!-- Search Section -->
    <section id="search" class="section">
        <div class="container">
            <div class="search-container">
                <!-- Search Form -->
                <form action="search.php" method="GET" class="search-form">
                    <input type="text" name="q" class="search-input" placeholder="Search for blog posts, portfolio items, events..." value="<?php echo htmlspecialchars($search_query); ?>" required>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>

                <!-- Search Filters -->
                <div class="search-filters">
                    <a href="search.php?q=<?php echo urlencode($search_query); ?>&filter=all" class="filter-btn <?php echo ($filter === 'all') ? 'active' : ''; ?>">All Results</a>
                    <a href="search.php?q=<?php echo urlencode($search_query); ?>&filter=blogs" class="filter-btn <?php echo ($filter === 'blogs') ? 'active' : ''; ?>">Blogs</a>
                    <a href="search.php?q=<?php echo urlencode($search_query); ?>&filter=portfolio" class="filter-btn <?php echo ($filter === 'portfolio') ? 'active' : ''; ?>">Portfolio</a>
                    <a href="search.php?q=<?php echo urlencode($search_query); ?>&filter=events" class="filter-btn <?php echo ($filter === 'events') ? 'active' : ''; ?>">Events</a>
                    <a href="search.php?q=<?php echo urlencode($search_query); ?>&filter=gallery" class="filter-btn <?php echo ($filter === 'gallery') ? 'active' : ''; ?>">Gallery</a>
                </div>

                <?php if (!empty($search_query)): ?>
                    <!-- Search Results Info -->
                    <div class="search-info">
                        Found <?php echo $total_results; ?> result<?php echo ($total_results !== 1) ? 's' : ''; ?> for "<?php echo htmlspecialchars($search_query); ?>"
                    </div>

                    <?php if ($total_results > 0): ?>
                        <!-- Blog Results -->
                        <?php if (count($blog_results) > 0 && ($filter === 'all' || $filter === 'blogs')): ?>
                            <div class="result-section">
                                <h2 class="section-title">Blog Posts (<?php echo count($blog_results); ?>)</h2>
                                <div class="result-grid">
                                    <?php foreach ($blog_results as $blog): ?>
                                        <div class="result-card">
                                            <div class="result-img">
                                                <?php if (!empty($blog['image'])): ?>
                                                    <img src="images/blogs/<?php echo htmlspecialchars($blog['image']); ?>" alt="<?php echo htmlspecialchars($blog['title']); ?>">
                                                <?php else: ?>
                                                    <img src="images/blog-default.jpg" alt="<?php echo htmlspecialchars($blog['title']); ?>">
                                                <?php endif; ?>
                                            </div>
                                            <div class="result-content">
                                                <?php if (!empty($blog['category'])): ?>
                                                    <span class="result-category"><?php echo htmlspecialchars($blog['category']); ?></span>
                                                <?php endif; ?>
                                                <h3 class="result-title"><?php echo highlight_search_term(htmlspecialchars($blog['title']), $search_query); ?></h3>
                                                <p class="result-excerpt"><?php echo highlight_search_term(htmlspecialchars(substr($blog['excerpt'], 0, 150)), $search_query); ?>...</p>
                                                <div class="result-meta">
                                                    <span>By <?php echo htmlspecialchars($blog['author']); ?></span>
                                                    <span><?php echo format_date($blog['created_at']); ?></span>
                                                </div>
                                                <div class="mt-auto pt-3">
                                                    <a href="blog-single.php?id=<?php echo $blog['id']; ?>" class="btn btn-sm">Read More</a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Portfolio Results -->
                        <?php if (count($portfolio_results) > 0 && ($filter === 'all' || $filter === 'portfolio')): ?>
                            <div class="result-section">
                                <h2 class="section-title">Portfolio Items (<?php echo count($portfolio_results); ?>)</h2>
                                <div class="result-grid">
                                    <?php foreach ($portfolio_results as $portfolio): ?>
                                        <div class="result-card">
                                            <div class="result-img">
                                                <?php if (!empty($portfolio['image'])): ?>
                                                    <img src="images/portfolio/<?php echo htmlspecialchars($portfolio['image']); ?>" alt="<?php echo htmlspecialchars($portfolio['title']); ?>">
                                                <?php else: ?>
                                                    <img src="images/portfolio-default.jpg" alt="<?php echo htmlspecialchars($portfolio['title']); ?>">
                                                <?php endif; ?>
                                            </div>
                                            <div class="result-content">
                                                <?php if (!empty($portfolio['category'])): ?>
                                                    <span class="result-category"><?php echo htmlspecialchars($portfolio['category']); ?></span>
                                                <?php endif; ?>
                                                <h3 class="result-title"><?php echo highlight_search_term(htmlspecialchars($portfolio['title']), $search_query); ?></h3>
                                                <p class="result-excerpt">
                                                    <?php if (!empty($portfolio['client'])): ?>
                                                        <strong>Client:</strong> <?php echo highlight_search_term(htmlspecialchars($portfolio['client']), $search_query); ?><br>
                                                    <?php endif; ?>
                                                    <?php echo highlight_search_term(htmlspecialchars(substr($portfolio['description'], 0, 150)), $search_query); ?>...
                                                </p>
                                                <div class="mt-auto pt-3">
                                                    <a href="portfolio-single.php?id=<?php echo $portfolio['id']; ?>" class="btn btn-sm">View Case Study</a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Event Results -->
                        <?php if (count($event_results) > 0 && ($filter === 'all' || $filter === 'events')): ?>
                            <div class="result-section">
                                <h2 class="section-title">Events (<?php echo count($event_results); ?>)</h2>
                                <div class="result-grid">
                                    <?php foreach ($event_results as $event): ?>
                                        <div class="result-card">
                                            <div class="result-img">
                                                <?php if (!empty($event['image'])): ?>
                                                    <img src="images/events/<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                                <?php else: ?>
                                                    <img src="images/event-default.jpg" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                                <?php endif; ?>
                                            </div>
                                            <div class="result-content">
                                                <?php
                                                $is_upcoming = strtotime($event['date']) >= strtotime(date('Y-m-d'));
                                                $event_status = $is_upcoming ? 'Upcoming' : 'Past';
                                                ?>
                                                <span class="result-category"><?php echo $event_status; ?> Event</span>
                                                <h3 class="result-title"><?php echo highlight_search_term(htmlspecialchars($event['title']), $search_query); ?></h3>
                                                <p class="result-excerpt">
                                                    <strong>Date:</strong> <?php echo format_date($event['date']); ?><br>
                                                    <strong>Location:</strong> <?php echo highlight_search_term(htmlspecialchars($event['location']), $search_query); ?><br>
                                                    <?php echo highlight_search_term(htmlspecialchars(substr($event['description'], 0, 100)), $search_query); ?>...
                                                </p>
                                                <div class="mt-auto pt-3">
                                                    <a href="event-single.php?id=<?php echo $event['id']; ?>" class="btn btn-sm">Event Details</a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Gallery Results -->
                        <?php if (count($gallery_results) > 0 && ($filter === 'all' || $filter === 'gallery')): ?>
                            <div class="result-section">
                                <h2 class="section-title">Gallery (<?php echo count($gallery_results); ?>)</h2>
                                <div class="result-grid">
                                    <?php foreach ($gallery_results as $gallery): ?>
                                        <div class="result-card">
                                            <div class="result-img">
                                                <?php if (!empty($gallery['image'])): ?>
                                                    <img src="images/gallery/<?php echo htmlspecialchars($gallery['image']); ?>" alt="<?php echo htmlspecialchars($gallery['title']); ?>">
                                                <?php else: ?>
                                                    <img src="images/gallery-default.jpg" alt="<?php echo htmlspecialchars($gallery['title']); ?>">
                                                <?php endif; ?>
                                            </div>
                                            <div class="result-content">
                                                <?php if (!empty($gallery['category'])): ?>
                                                    <span class="result-category"><?php echo htmlspecialchars($gallery['category']); ?></span>
                                                <?php endif; ?>
                                                <h3 class="result-title"><?php echo highlight_search_term(htmlspecialchars($gallery['title']), $search_query); ?></h3>
                                                <?php if (!empty($gallery['description'])): ?>
                                                    <p class="result-excerpt"><?php echo highlight_search_term(htmlspecialchars($gallery['description']), $search_query); ?></p>
                                                <?php endif; ?>
                                                <div class="mt-auto pt-3">
                                                    <a href="gallery.php?category=<?php echo urlencode($gallery['category']); ?>" class="btn btn-sm">View Gallery</a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- No Results -->
                        <div class="no-results">
                            <h3>No results found</h3>
                            <p>Sorry, we couldn't find any results for "<?php echo htmlspecialchars($search_query); ?>". Please try a different search term or browse our content using the navigation menu.</p>
                            <a href="index.html" class="btn btn-primary">Back to Homepage</a>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Empty Search -->
                    <div class="no-results">
                        <h3>Enter a search term</h3>
                        <p>Use the search box above to find blog posts, portfolio items, events, and gallery images on our website.</p>
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
</body>
</html>
