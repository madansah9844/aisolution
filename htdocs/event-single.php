<?php
// Include database configuration
require_once 'admin/includes/config.php';

// Track page visit
track_visit($pdo);

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redirect to events page if ID is not valid
    header("Location: events.php");
    exit;
}

// Get event ID
$id = (int)$_GET['id'];

// Fetch event
$sql = "SELECT * FROM events WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();

// Check if event exists
if ($stmt->rowCount() === 0) {
    // Redirect to events page if event doesn't exist
    header("Location: events.php");
    exit;
}

$event = $stmt->fetch();

// Get current date
$current_date = date('Y-m-d');

// Determine if event is upcoming or past
$is_upcoming = strtotime($event['date']) >= strtotime($current_date);

// Format the date and time
$date_formatted = date('F j, Y', strtotime($event['date']));
$time_formatted = date('g:i A', strtotime($event['time']));

// Fetch other upcoming events to show at the bottom
$other_events_sql = "SELECT * FROM events WHERE id != :id AND date >= :current_date ORDER BY date ASC LIMIT 3";
$other_stmt = $pdo->prepare($other_events_sql);
$other_stmt->bindParam(':id', $id, PDO::PARAM_INT);
$other_stmt->bindParam(':current_date', $current_date, PDO::PARAM_STR);
$other_stmt->execute();
$other_events = $other_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['title']); ?> - AI-Solutions Events</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($event['description'], 0, 150)); ?>">
    <meta name="keywords" content="AI Event, <?php echo htmlspecialchars($event['title']); ?>, AI Workshop, AI Conference, Business Technology">
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
        /* Event Single Page Styles */
        .event-single {
            padding: 5rem 0;
        }

        .event-details {
            display: grid;
            grid-template-columns: 1fr 30rem;
            gap: 4rem;
            margin-bottom: 4rem;
        }

        .event-main {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .event-main-img {
            width: 100%;
            height: 40rem;
            overflow: hidden;
        }

        .event-main-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .event-main-content {
            padding: 3rem;
        }

        .event-single-title {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }

        .event-meta {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 2rem;
            gap: 2rem;
        }

        .event-meta-item {
            display: flex;
            align-items: center;
            color: var(--gray-color);
            font-size: 1.6rem;
        }

        .event-meta-item i {
            margin-right: 1rem;
            color: var(--primary-color);
            font-size: 1.8rem;
            width: 2.5rem;
            text-align: center;
        }

        .event-description {
            line-height: 1.8;
            margin-bottom: 2rem;
        }

        .event-description p {
            margin-bottom: 1.5rem;
        }

        .event-sidebar {
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
            flex-direction: column;
            padding: 1.5rem 0;
            border-bottom: 1px solid var(--gray-light);
        }

        .info-list li:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .info-value {
            color: var(--gray-color);
        }

        .event-cta {
            margin-top: 2rem;
        }

        .event-cta .btn {
            width: 100%;
            margin-bottom: 1rem;
            text-align: center;
            display: block;
        }

        .event-navigation {
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

        .event-agenda {
            margin-top: 4rem;
        }

        .agenda-title {
            font-size: 2.4rem;
            margin-bottom: 2rem;
        }

        .agenda-list {
            list-style: none;
            border-left: 2px solid var(--primary-color);
            padding-left: 2rem;
            margin-left: 1rem;
        }

        .agenda-item {
            position: relative;
            padding: 2rem 0;
        }

        .agenda-item::before {
            content: '';
            position: absolute;
            left: -2.8rem;
            top: 2.5rem;
            width: 1.6rem;
            height: 1.6rem;
            background-color: var(--primary-color);
            border-radius: 50%;
        }

        .agenda-time {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 1.6rem;
        }

        .agenda-item-title {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .agenda-item-desc {
            color: var(--gray-color);
        }

        .other-events {
            margin-top: 5rem;
        }

        .other-events-title {
            font-size: 2.4rem;
            margin-bottom: 3rem;
            text-align: center;
        }

        .other-events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(35rem, 1fr));
            gap: 3rem;
        }

        .event-type {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background-color: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
            border-radius: 2rem;
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
        }

        .event-type i {
            margin-right: 0.5rem;
        }

        @media (max-width: 992px) {
            .event-details {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .other-events-grid {
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
                <h1><?php echo htmlspecialchars($event['title']); ?></h1>
                <p>Event Details | <?php echo $date_formatted; ?></p>
            </div>
        </div>
    </section>

    <!-- Event Single Section -->
    <section class="event-single section">
        <div class="container">
            <div class="event-details">
                <div class="event-main">
                    <div class="event-main-img">
                        <?php if (!empty($event['image'])): ?>
                            <img src="images/events/<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                        <?php else: ?>
                            <img src="images/event-default.jpg" alt="<?php echo htmlspecialchars($event['title']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="event-main-content">
                        <?php if(strpos(strtolower($event['location']), 'online') !== false): ?>
                            <div class="event-type"><i class="fas fa-video"></i> Online Event</div>
                        <?php else: ?>
                            <div class="event-type"><i class="fas fa-map-pin"></i> In-Person Event</div>
                        <?php endif; ?>

                        <h2 class="event-single-title"><?php echo htmlspecialchars($event['title']); ?></h2>
                        <div class="event-meta">
                            <div class="event-meta-item">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo $date_formatted; ?></span>
                            </div>
                            <div class="event-meta-item">
                                <i class="fas fa-clock"></i>
                                <span><?php echo $time_formatted; ?></span>
                            </div>
                            <div class="event-meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($event['location']); ?></span>
                            </div>
                        </div>
                        <div class="event-description">
                            <?php
                            // Format the description text with proper paragraphs
                            $description = nl2br(htmlspecialchars($event['description']));
                            echo $description;
                            ?>
                        </div>

                        <!-- Event Agenda -->
                        <div class="event-agenda">
                            <h3 class="agenda-title">Event Agenda</h3>
                            <ul class="agenda-list">
                                <li class="agenda-item">
                                    <div class="agenda-time">09:00 AM - 09:30 AM</div>
                                    <h4 class="agenda-item-title">Registration & Welcome Coffee</h4>
                                    <p class="agenda-item-desc">Get your badge, enjoy refreshments, and network with other attendees.</p>
                                </li>
                                <li class="agenda-item">
                                    <div class="agenda-time">09:30 AM - 10:30 AM</div>
                                    <h4 class="agenda-item-title">Keynote: The Future of AI in Business</h4>
                                    <p class="agenda-item-desc">An inspiring talk about upcoming AI trends and their impact on business operations.</p>
                                </li>
                                <li class="agenda-item">
                                    <div class="agenda-time">10:45 AM - 12:00 PM</div>
                                    <h4 class="agenda-item-title">Panel Discussion: Real-world AI Applications</h4>
                                    <p class="agenda-item-desc">Industry experts share their experiences implementing AI solutions.</p>
                                </li>
                                <li class="agenda-item">
                                    <div class="agenda-time">12:00 PM - 01:00 PM</div>
                                    <h4 class="agenda-item-title">Networking Lunch</h4>
                                    <p class="agenda-item-desc">Enjoy a delicious lunch while connecting with speakers and other attendees.</p>
                                </li>
                                <li class="agenda-item">
                                    <div class="agenda-time">01:00 PM - 03:00 PM</div>
                                    <h4 class="agenda-item-title">Hands-on Workshop: Building Your First AI Assistant</h4>
                                    <p class="agenda-item-desc">A practical session where you'll learn to create a simple AI assistant for your business.</p>
                                </li>
                                <li class="agenda-item">
                                    <div class="agenda-time">03:15 PM - 04:00 PM</div>
                                    <h4 class="agenda-item-title">Closing Remarks & Networking</h4>
                                    <p class="agenda-item-desc">Final thoughts and additional time to connect with speakers and attendees.</p>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="event-sidebar">
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Event Details</h3>
                        <ul class="info-list">
                            <li>
                                <span class="info-label">Date:</span>
                                <span class="info-value"><?php echo $date_formatted; ?></span>
                            </li>
                            <li>
                                <span class="info-label">Time:</span>
                                <span class="info-value"><?php echo $time_formatted; ?></span>
                            </li>
                            <li>
                                <span class="info-label">Location:</span>
                                <span class="info-value"><?php echo htmlspecialchars($event['location']); ?></span>
                            </li>
                            <?php if(strpos(strtolower($event['location']), 'online') !== false): ?>
                                <li>
                                    <span class="info-label">Platform:</span>
                                    <span class="info-value">Zoom (link will be sent after registration)</span>
                                </li>
                            <?php endif; ?>
                            <li>
                                <span class="info-label">Event Type:</span>
                                <span class="info-value">
                                    <?php if(strpos(strtolower($event['location']), 'online') !== false): ?>
                                        Online Webinar
                                    <?php else: ?>
                                        In-Person Event
                                    <?php endif; ?>
                                </span>
                            </li>
                            <li>
                                <span class="info-label">Cost:</span>
                                <span class="info-value">Free</span>
                            </li>
                        </ul>
                    </div>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">Organizer</h3>
                        <ul class="info-list">
                            <li>
                                <span class="info-label">Company:</span>
                                <span class="info-value">AI-Solution</span>
                            </li>
                            <li>
                                <span class="info-label">Phone:</span>
                                <span class="info-value">+44 191 123 4567</span>
                            </li>
                            <li>
                                <span class="info-label">Email:</span>
                                <span class="info-value">events@ai-solution.com</span>
                            </li>
                        </ul>
                    </div>
                    <?php if ($is_upcoming): ?>
                        <div class="event-cta">
                            <a href="#" class="btn btn-primary">Register Now</a>
                            <a href="#" class="btn">Add to Calendar</a>
                        </div>
                    <?php else: ?>
                        <div class="event-cta">
                            <p class="info-value" style="text-align: center; margin-bottom: 1rem;">This event has already taken place.</p>
                            <a href="events.php" class="btn btn-primary">View Upcoming Events</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Event Navigation -->
            <div class="event-navigation">
                <a href="events.php" class="btn btn-sm">
                    <i class="fas fa-th-large"></i> All Events
                </a>
                <div>
                    <a href="contact.html" class="btn btn-primary">Contact Organizer</a>
                </div>
            </div>

            <!-- Other Events -->
            <?php if (count($other_events) > 0): ?>
                <div class="other-events">
                    <h3 class="other-events-title">Other Upcoming Events</h3>
                    <div class="other-events-grid">
                        <?php foreach($other_events as $other): ?>
                            <div class="event-card">
                                <div class="event-img">
                                    <?php
                                    // Extract date components for badge
                                    $date_obj = new DateTime($other['date']);
                                    $month = $date_obj->format('M');
                                    $day = $date_obj->format('d');
                                    ?>
                                    <div class="event-date-badge">
                                        <span class="month"><?php echo $month; ?></span>
                                        <span class="day"><?php echo $day; ?></span>
                                    </div>
                                    <?php if (!empty($other['image'])): ?>
                                        <img src="images/events/<?php echo htmlspecialchars($other['image']); ?>" alt="<?php echo htmlspecialchars($other['title']); ?>">
                                    <?php else: ?>
                                        <img src="images/event-default.jpg" alt="<?php echo htmlspecialchars($other['title']); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="event-content">
                                    <h3 class="event-title"><?php echo htmlspecialchars($other['title']); ?></h3>
                                    <div class="event-meta">
                                        <div class="event-meta-item">
                                            <i class="fas fa-calendar"></i>
                                            <span><?php echo date('F j, Y', strtotime($other['date'])); ?></span>
                                        </div>
                                        <div class="event-meta-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($other['location']); ?></span>
                                        </div>
                                    </div>
                                    <p class="event-desc"><?php echo htmlspecialchars(substr($other['description'], 0, 100)); ?>...</p>
                                    <div class="event-footer">
                                        <a href="event-single.php?id=<?php echo $other['id']; ?>" class="btn btn-sm">Event Details</a>
                                        <?php if(strpos(strtolower($other['location']), 'online') !== false): ?>
                                            <span class="event-type"><i class="fas fa-video"></i> Online</span>
                                        <?php else: ?>
                                            <span class="event-type"><i class="fas fa-map-pin"></i> In-Person</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
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
