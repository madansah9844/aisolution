<?php
/**
 * Public Feedback Form
 * Allows visitors to submit feedback and reviews
 */

session_start();
require_once 'admin/includes/config.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $subject = sanitize_input($_POST['subject']);
    $message = sanitize_input($_POST['message']);
    $rating = intval($_POST['rating']);

    // Validation
    if (empty($name) || empty($email) || empty($message)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif ($rating < 1 || $rating > 5) {
        $error_message = "Please select a valid rating.";
    } else {
        try {
            $sql = "INSERT INTO feedback (name, email, subject, message, rating) 
                    VALUES (:name, :email, :subject, :message, :rating)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
            $stmt->bindParam(':message', $message, PDO::PARAM_STR);
            $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
            $stmt->execute();

            $success_message = "Thank you for your feedback! We appreciate your input and will review it shortly.";

            // Clear form data
            $name = $email = $subject = $message = '';
            $rating = 5;

        } catch (PDOException $e) {
            $error_message = "Sorry, there was an error submitting your feedback. Please try again later.";
            error_log("Feedback submission error: " . $e->getMessage());
        }
    }
}

// Get company settings for header
try {
    $settings_sql = "SELECT setting_key, setting_value FROM company_settings";
    $settings_stmt = $pdo->prepare($settings_sql);
    $settings_stmt->execute();
    $settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $settings = [];
}

$company_name = $settings['company_name'] ?? 'AI-Solutions';
$company_logo = $settings['company_logo'] ?? 'images/logo.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - <?php echo htmlspecialchars($company_name); ?></title>
    <meta name="description" content="Share your feedback with AI-Solutions. Help us improve our AI-powered software solutions and services.">
    <meta name="keywords" content="Feedback, Reviews, AI Solutions, Customer Feedback, Service Improvement">
    <meta name="author" content="AI-Solutions">

    <!-- CSS Files -->
    <link rel="stylesheet" href="css/styles.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" href="<?php echo htmlspecialchars($settings['company_favicon'] ?? 'images/logo.png'); ?>" type="image/png">

    <style>
        .feedback-hero {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 8rem 0 4rem;
            text-align: center;
            color: var(--dark-color);
        }

        .feedback-hero h1 {
            font-size: 4rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .feedback-hero p {
            font-size: 1.8rem;
            opacity: 0.9;
        }

        .feedback-container {
            max-width: 80rem;
            margin: 0 auto;
            padding: 4rem 2rem;
        }

        .feedback-form-card {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 4rem;
            margin-bottom: 4rem;
        }

        .form-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .form-header h2 {
            font-size: 2.8rem;
            margin-bottom: 1rem;
            color: var(--dark-color);
        }

        .form-header p {
            color: var(--gray-color);
            font-size: 1.6rem;
        }

        .feedback-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 0.8rem;
            color: var(--dark-color);
            font-size: 1.4rem;
        }

        .form-group label .required {
            color: var(--danger-color);
        }

        .form-group input,
        .form-group textarea {
            padding: 1.2rem;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1.4rem;
            transition: var(--transition);
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .rating-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .rating-stars {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .star {
            font-size: 2.4rem;
            color: var(--gray-light);
            cursor: pointer;
            transition: var(--transition);
        }

        .star:hover,
        .star.active {
            color: var(--primary-color);
        }

        .rating-text {
            font-size: 1.4rem;
            color: var(--gray-color);
            margin-left: 1rem;
        }

        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            padding: 1.2rem 3rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1.6rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            font-family: inherit;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--dark-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--gray-light);
            color: var(--dark-color);
        }

        .btn-secondary:hover {
            background-color: var(--gray-color);
        }

        .alert {
            padding: 1.5rem 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(50, 205, 50, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .alert-error {
            background-color: rgba(220, 20, 60, 0.1);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }

        .feedback-info {
            background-color: var(--body-bg);
            border-radius: var(--border-radius);
            padding: 3rem;
            text-align: center;
        }

        .feedback-info h3 {
            font-size: 2.4rem;
            margin-bottom: 1.5rem;
            color: var(--dark-color);
        }

        .feedback-info p {
            font-size: 1.6rem;
            color: var(--gray-color);
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(20rem, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .contact-item i {
            font-size: 2rem;
            color: var(--primary-color);
            width: 3rem;
            text-align: center;
        }

        .contact-item div h4 {
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .contact-item div p {
            margin: 0;
            color: var(--gray-color);
        }

        @media (max-width: 768px) {
            .feedback-hero h1 {
                font-size: 3rem;
            }

            .feedback-hero p {
                font-size: 1.6rem;
            }

            .feedback-form-card {
                padding: 2rem;
            }

            .feedback-form {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .contact-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="nav-wrapper">
                <div class="logo">
                    <a href="index.html">
                        <img src="<?php echo htmlspecialchars($company_logo); ?>" alt="<?php echo htmlspecialchars($company_name); ?>">
                    </a>
                </div>
                
                <nav class="nav">
                    <ul class="nav-list">
                        <li class="nav-item">
                            <a href="index.html" class="nav-link">Home</a>
                        </li>
                        <li class="nav-item">
                            <a href="about.html" class="nav-link">About</a>
                        </li>
                        <li class="nav-item">
                            <a href="services.html" class="nav-link">Services</a>
                        </li>
                        <li class="nav-item">
                            <a href="portfolio.php" class="nav-link">Portfolio</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link">More <i class="fas fa-chevron-down"></i></a>
                            <ul class="dropdown-menu">
                                <li><a href="gallery.php">Gallery</a></li>
                                <li><a href="feedback.php">Feedback</a></li>
                                <li><a href="blogs.php">Blog</a></li>
                                <li><a href="events.php">Events</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a href="contact.html" class="nav-link">Contact</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <!-- Feedback Hero -->
    <section class="feedback-hero">
        <div class="container">
            <h1>Share Your Feedback</h1>
            <p>Your opinion matters to us. Help us improve our services by sharing your experience.</p>
        </div>
    </section>

    <!-- Feedback Form -->
    <div class="feedback-container">
        <div class="feedback-form-card">
            <div class="form-header">
                <h2>Tell Us What You Think</h2>
                <p>We value your feedback and use it to improve our AI solutions and services.</p>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form class="feedback-form" method="POST">
                <div class="form-group">
                    <label for="name">Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($subject ?? ''); ?>" 
                           placeholder="Brief description of your feedback">
                </div>

                <div class="form-group">
                    <label for="rating">Rating <span class="required">*</span></label>
                    <div class="rating-group">
                        <div class="rating-stars" id="ratingStars">
                            <input type="hidden" name="rating" id="ratingInput" value="<?php echo $rating ?? 5; ?>">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?php echo ($i <= ($rating ?? 5)) ? 'active' : ''; ?>" data-rating="<?php echo $i; ?>">
                                    <i class="fas fa-star"></i>
                                </span>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-text" id="ratingText">Excellent</span>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="message">Your Feedback <span class="required">*</span></label>
                    <textarea id="message" name="message" rows="6" 
                              placeholder="Please share your thoughts about our services, website, or any suggestions for improvement..." required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Submit Feedback
                    </button>
                    <a href="index.html" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Home
                    </a>
                </div>
            </form>
        </div>

        <div class="feedback-info">
            <h3>Why Your Feedback Matters</h3>
            <p>At AI-Solutions, we're committed to providing exceptional AI services and customer experience. Your feedback helps us:</p>
            
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fas fa-lightbulb"></i>
                    <div>
                        <h4>Improve Our Services</h4>
                        <p>Identify areas for enhancement</p>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fas fa-users"></i>
                    <div>
                        <h4>Better Serve You</h4>
                        <p>Understand your needs better</p>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fas fa-chart-line"></i>
                    <div>
                        <h4>Drive Innovation</h4>
                        <p>Develop new solutions</p>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fas fa-handshake"></i>
                    <div>
                        <h4>Build Relationships</h4>
                        <p>Strengthen our partnership</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <img src="<?php echo htmlspecialchars($company_logo); ?>" alt="<?php echo htmlspecialchars($company_name); ?>">
                        <h3><?php echo htmlspecialchars($company_name); ?></h3>
                    </div>
                    <p><?php echo htmlspecialchars($settings['company_description'] ?? 'Leading AI solutions provider specializing in virtual assistants, prototyping, and custom AI development.'); ?></p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.html">Home</a></li>
                        <li><a href="about.html">About</a></li>
                        <li><a href="services.html">Services</a></li>
                        <li><a href="portfolio.php">Portfolio</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Services</h4>
                    <ul>
                        <li><a href="services.html">AI Virtual Assistant</a></li>
                        <li><a href="services.html">Affordable Prototyping</a></li>
                        <li><a href="services.html">Custom AI Solutions</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <div class="contact-info">
                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($settings['company_email'] ?? 'info@ai-solutions.com'); ?></p>
                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($settings['company_phone'] ?? '+44 123 456 7890'); ?></p>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($settings['company_address'] ?? 'Sunderland, UK'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($company_name); ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Rating stars functionality
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('ratingInput');
        const ratingText = document.getElementById('ratingText');

        const ratingTexts = {
            1: 'Poor',
            2: 'Fair',
            3: 'Good',
            4: 'Very Good',
            5: 'Excellent'
        };

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                ratingInput.value = rating;
                ratingText.textContent = ratingTexts[rating];
                
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });

            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                ratingText.textContent = ratingTexts[rating];
                
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });

        // Reset stars on mouse leave
        document.getElementById('ratingStars').addEventListener('mouseleave', function() {
            const currentRating = parseInt(ratingInput.value);
            ratingText.textContent = ratingTexts[currentRating];
            
            stars.forEach((s, index) => {
                if (index < currentRating) {
                    s.classList.add('active');
                } else {
                    s.classList.remove('active');
                }
            });
        });

        // Form validation
        document.querySelector('.feedback-form').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const message = document.getElementById('message').value.trim();
            const rating = parseInt(ratingInput.value);

            if (!name || !email || !message) {
                alert('Please fill in all required fields.');
                e.preventDefault();
                return;
            }

            if (!isValidEmail(email)) {
                alert('Please enter a valid email address.');
                e.preventDefault();
                return;
            }

            if (rating < 1 || rating > 5) {
                alert('Please select a rating.');
                e.preventDefault();
                return;
            }
        });

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
    </script>
</body>
</html>
