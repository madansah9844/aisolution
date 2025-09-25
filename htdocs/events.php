<?php
// Include database configuration
require_once 'admin/includes/config.php';

// Track page visit
track_visit($pdo);

// Get current date
$current_date = date('Y-m-d');

// Get events category filter if any
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Query for upcoming events
$upcoming_sql = "SELECT * FROM events WHERE date >= :current_date";
if ($filter === 'featured') {
    $upcoming_sql .= " AND featured = 1";
}
$upcoming_sql .= " ORDER BY date ASC LIMIT 6";
$upcoming_stmt = $pdo->prepare($upcoming_sql);
$upcoming_stmt->bindParam(':current_date', $current_date, PDO::PARAM_STR);
$upcoming_stmt->execute();
$upcoming_events = $upcoming_stmt->fetchAll();

// Query for past events
$past_sql = "SELECT * FROM events WHERE date < :current_date";
if ($filter === 'featured') {
    $past_sql .= " AND featured = 1";
}
$past_sql .= " ORDER BY date DESC LIMIT 6";
$past_stmt = $pdo->prepare($past_sql);
$past_stmt->bindParam(':current_date', $current_date, PDO::PARAM_STR);
$past_stmt->execute();
$past_events = $past_stmt->fetchAll();

// Helper function to format date
function format_event_date($date, $time = null) {
    $date_obj = new DateTime($date);
    $formatted_date = $date_obj->format('F j, Y');

    if ($time) {
        $time_obj = new DateTime($time);
        $formatted_time = $time_obj->format('g:i A');
        return $formatted_date . ' at ' . $formatted_time;
    }

    return $formatted_date;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - AI-Solutions</title>
    <meta name="description" content="Join AI-Solutions' upcoming events, conferences, workshops, and webinars to learn about the latest in AI technology for business transformation.">
    <meta name="keywords" content="AI Events, Tech Conference, AI Workshop, Webinar, Business Technology, Digital Transformation">
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
        /* Event-specific styles */
        .events-header {
            margin-bottom: 4rem;
        }

        .event-filters {
            display: flex;
            justify-content: center;
            margin-bottom: 3rem;
            flex-wrap: wrap;
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

        .event-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(35rem, 1fr));
            gap: 3rem;
            margin-bottom: 5rem;
        }

        .event-card {
            display: flex;
            flex-direction: column;
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            height: 100%;
        }

        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .event-img {
            height: 22rem;
            overflow: hidden;
            position: relative;
        }

        .event-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .event-card:hover .event-img img {
            transform: scale(1.05);
        }

        .event-date-badge {
            position: absolute;
            top: 1.5rem;
            left: 1.5rem;
            background-color: var(--primary-color);
            color: var(--light-color);
            padding: 1rem;
            border-radius: var(--border-radius);
            text-align: center;
            line-height: 1.2;
            z-index: 1;
        }

        .event-date-badge .month {
            font-size: 1.2rem;
            text-transform: uppercase;
            font-weight: 500;
            display: block;
        }

        .event-date-badge .day {
            font-size: 2.4rem;
            font-weight: 700;
            display: block;
        }

        .event-content {
            padding: 2rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .event-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .event-meta-item {
            display: flex;
            align-items: center;
            color: var(--gray-color);
            font-size: 1.4rem;
        }

        .event-meta-item i {
            margin-right: 0.8rem;
            color: var(--primary-color);
        }

        .event-title {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .event-desc {
            margin-bottom: 2rem;
            flex-grow: 1;
        }

        .event-footer {
            margin-top: auto;
            border-top: 1px solid var(--gray-light);
            padding-top: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-divider {
            position: relative;
            text-align: center;
            margin: 6rem 0;
        }

        .section-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background-color: var(--gray-light);
            z-index: -1;
        }

        .section-divider span {
            background-color: var(--light-color);
            padding: 0 2rem;
            font-size: 2rem;
            font-weight: 600;
            color: var(--gray-color);
        }

        .no-events {
            grid-column: 1 / -1;
            padding: 5rem;
            text-align: center;
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .no-events h3 {
            font-size: 2.4rem;
            margin-bottom: 1rem;
        }

        .no-events p {
            font-size: 1.6rem;
            color: var(--gray-color);
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .event-grid {
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
                        <li><a href="events.php" class="active">Events</a></li>
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
                <h1>Events & Workshops</h1>
                <p>Join us for informative and engaging events about AI technology</p>
            </div>
        </div>
    </section>

    <!-- Events Section -->
    <section id="events" class="section">
        <div class="container">
            <div class="events-header">
                <div class="section-header">
                    <h2>AI-Solution Events</h2>
                    <p>Stay updated with our conferences, workshops, and webinars focused on AI innovation</p>
                </div>

                <!-- Event Filters -->
                <div class="event-filters">
                    <a href="events.php" class="filter-btn <?php echo empty($filter) ? 'active' : ''; ?>">All Events</a>
                    <a href="events.php?filter=featured" class="filter-btn <?php echo ($filter === 'featured') ? 'active' : ''; ?>">Featured Events</a>
                </div>
            </div>

            <!-- Upcoming Events -->
            <h3 class="sub-heading">Upcoming Events</h3>
            <div class="event-grid">
                <?php if (count($upcoming_events) > 0): ?>
                    <?php foreach($upcoming_events as $event): ?>
                        <div class="event-card">
                            <div class="event-img">
                                <?php
                                // Extract date components for badge
                                $date_obj = new DateTime($event['date']);
                                $month = $date_obj->format('M');
                                $day = $date_obj->format('d');
                                ?>
                                <div class="event-date-badge">
                                    <span class="month"><?php echo $month; ?></span>
                                    <span class="day"><?php echo $day; ?></span>
                                </div>
                                <?php if (!empty($event['image'])): ?>
                                    <img src="images/events/<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                <?php else: ?>
                                    <img src="images/event-default.jpg" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="event-content">
                                <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                                <div class="event-meta">
                                    <div class="event-meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo format_event_date($event['date'], $event['time']); ?></span>
                                    </div>
                                    <div class="event-meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($event['location']); ?></span>
                                    </div>
                                </div>
                                <p class="event-desc"><?php echo htmlspecialchars(substr($event['description'], 0, 150)); ?>...</p>
                                <div class="event-footer">
                                    <a href="event-single.php?id=<?php echo $event['id']; ?>" class="btn btn-sm">Event Details</a>
                                    <?php if(strpos(strtolower($event['location']), 'online') !== false): ?>
                                        <span class="event-type"><i class="fas fa-video"></i> Online</span>
                                    <?php else: ?>
                                        <span class="event-type"><i class="fas fa-map-pin"></i> In-Person</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-events">
                        <h3>No upcoming events</h3>
                        <p>There are currently no upcoming events scheduled. Please check back later for future events.</p>
                        <a href="contact.html" class="btn btn-primary">Contact Us for Event Information</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Section Divider -->
            <div class="section-divider">
                <span>Past Events</span>
            </div>

            <!-- Past Events -->
            <div class="event-grid">
                <?php if (count($past_events) > 0): ?>
                    <?php foreach($past_events as $event): ?>
                        <div class="event-card">
                            <div class="event-img">
                                <?php
                                // Extract date components for badge
                                $date_obj = new DateTime($event['date']);
                                $month = $date_obj->format('M');
                                $day = $date_obj->format('d');
                                ?>
                                <div class="event-date-badge">
                                    <span class="month"><?php echo $month; ?></span>
                                    <span class="day"><?php echo $day; ?></span>
                                </div>
                                <?php if (!empty($event['image'])): ?>
                                    <img src="images/events/<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                <?php else: ?>
                                    <img src="images/event-default.jpg" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="event-content">
                                <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                                <div class="event-meta">
                                    <div class="event-meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo format_event_date($event['date'], $event['time']); ?></span>
                                    </div>
                                    <div class="event-meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($event['location']); ?></span>
                                    </div>
                                </div>
                                <p class="event-desc"><?php echo htmlspecialchars(substr($event['description'], 0, 150)); ?>...</p>
                                <div class="event-footer">
                                    <a href="event-single.php?id=<?php echo $event['id']; ?>" class="btn btn-sm">View Recap</a>
                                    <?php if(strpos(strtolower($event['location']), 'online') !== false): ?>
                                        <span class="event-type"><i class="fas fa-video"></i> Online</span>
                                    <?php else: ?>
                                        <span class="event-type"><i class="fas fa-map-pin"></i> In-Person</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-events">
                        <h3>No past events</h3>
                        <p>There are currently no past events to display. Please check our upcoming events section for future events.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section id="newsletter" class="section bg-light">
        <div class="container">
            <div class="newsletter-content">
                <h2>Subscribe to Our Events Newsletter</h2>
                <p>Stay updated with our upcoming events, workshops, and webinars</p>
                <form action="subscribe.php" method="POST" id="newsletterForm" class="newsletter-form">
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Enter your email address" required>
                        <button type="submit" class="btn btn-primary">Subscribe</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="cta" class="section bg-primary">
        <div class="container">
            <div class="cta-content">
                <h2>Looking for Custom AI Events for Your Organization?</h2>
                <p>We provide customized workshops and training sessions for businesses interested in implementing AI solutions.</p>
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
