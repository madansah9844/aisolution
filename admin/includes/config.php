<?php
/**
 * Database Configuration File
 * Contains database credentials and connection setup
 */

// Database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'puja');

// Attempt to connect to MySQL database
try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // Set character set
    $pdo->exec("SET NAMES 'utf8'");
} catch(PDOException $e) {
    die("ERROR: Could not connect to database. " . $e->getMessage());
}

/**
 * Database Schema Information:
 *
 * 1. users - Admin users
 *    - id (INT, Primary Key, Auto Increment)
 *    - username (VARCHAR)
 *    - password (VARCHAR)
 *    - email (VARCHAR)
 *    - role (VARCHAR)
 *    - created_at (TIMESTAMP)
 *    - updated_at (TIMESTAMP)
 *
 * 2. inquiries - Contact form submissions
 *    - id (INT, Primary Key, Auto Increment)
 *    - name (VARCHAR)
 *    - email (VARCHAR)
 *    - phone (VARCHAR)
 *    - company (VARCHAR)
 *    - country (VARCHAR)
 *    - job_title (VARCHAR)
 *    - message (TEXT)
 *    - status (VARCHAR) [new, read, replied, archived]
 *    - created_at (TIMESTAMP)
 *    - updated_at (TIMESTAMP)
 *
 * 3. services - Company services
 *    - id (INT, Primary Key, Auto Increment)
 *    - title (VARCHAR)
 *    - description (TEXT)
 *    - short_description (VARCHAR)
 *    - image (VARCHAR)
 *    - icon (VARCHAR)
 *    - featured (BOOLEAN)
 *    - created_at (TIMESTAMP)
 *    - updated_at (TIMESTAMP)
 *
 * 4. portfolio - Portfolio/Case studies
 *    - id (INT, Primary Key, Auto Increment)
 *    - title (VARCHAR)
 *    - description (TEXT)
 *    - client (VARCHAR)
 *    - category (VARCHAR)
 *    - image (VARCHAR)
 *    - featured (BOOLEAN)
 *    - created_at (TIMESTAMP)
 *    - updated_at (TIMESTAMP)
 *
 * 5. events - Company events
 *    - id (INT, Primary Key, Auto Increment)
 *    - title (VARCHAR)
 *    - description (TEXT)
 *    - date (DATE)
 *    - time (TIME)
 *    - location (VARCHAR)
 *    - image (VARCHAR)
 *    - featured (BOOLEAN)
 *    - created_at (TIMESTAMP)
 *    - updated_at (TIMESTAMP)
 *
 * 6. blogs - Blog posts
 *    - id (INT, Primary Key, Auto Increment)
 *    - title (VARCHAR)
 *    - content (TEXT)
 *    - excerpt (VARCHAR)
 *    - author (VARCHAR)
 *    - category (VARCHAR)
 *    - image (VARCHAR)
 *    - published (BOOLEAN)
 *    - created_at (TIMESTAMP)
 *    - updated_at (TIMESTAMP)
 *
 * 7. gallery - Image gallery
 *    - id (INT, Primary Key, Auto Increment)
 *    - title (VARCHAR)
 *    - description (VARCHAR)
 *    - image (VARCHAR)
 *    - category (VARCHAR)
 *    - created_at (TIMESTAMP)
 *
 * 8. visitors - Website analytics
 *    - id (INT, Primary Key, Auto Increment)
 *    - ip_address (VARCHAR)
 *    - user_agent (VARCHAR)
 *    - page_visited (VARCHAR)
 *    - referrer (VARCHAR)
 *    - visit_time (TIMESTAMP)
 */

// Function to sanitize user inputs
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to generate slug from title
function generate_slug($string) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string)));
    return $slug;
}

// Function to format date
function format_date($date, $format = 'd M, Y') {
    return date($format, strtotime($date));
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Function to redirect to a specific page
function redirect($location) {
    header("Location: $location");
    exit;
}

// Function to set flash message
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

// Function to display flash message
function display_flash_message() {
    if(isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_message']['type'];
        $message = $_SESSION['flash_message']['message'];
        echo "<div class='alert alert-{$type}'>{$message}</div>";
        unset($_SESSION['flash_message']);
    }
}

// Function to track page visit
function track_visit($pdo) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $page = $_SERVER['REQUEST_URI'];
    $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

    $sql = "INSERT INTO visitors (ip_address, user_agent, page_visited, referrer)
            VALUES (:ip, :user_agent, :page, :referrer)";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':ip', $ip, PDO::PARAM_STR);
        $stmt->bindParam(':user_agent', $user_agent, PDO::PARAM_STR);
        $stmt->bindParam(':page', $page, PDO::PARAM_STR);
        $stmt->bindParam(':referrer', $referrer, PDO::PARAM_STR);
        $stmt->execute();
    } catch(PDOException $e) {
        // Silently fail - don't let tracking interfere with user experience
        error_log("Tracking error: " . $e->getMessage());
    }
}
