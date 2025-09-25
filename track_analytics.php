<?php
/**
 * Analytics Tracking Script
 * Receives analytics data from the client side and stores it in the database
 */

// Start session
session_start();

// Include database configuration
require_once 'admin/includes/config.php';

// Get the raw POST data
$json_data = file_get_contents('php://input');

// Check if data is received
if (!empty($json_data)) {
    // Decode the JSON data
    $data = json_decode($json_data, true);

    // Check if JSON was valid
    if ($data !== null) {
        // Extract event type and data
        $event_type = isset($data['event']) ? $data['event'] : 'unknown';
        $event_data = isset($data['data']) ? $data['data'] : [];

        // Handle different event types
        switch ($event_type) {
            case 'pageview':
                // Store page view in database
                storePageView($pdo, $event_data);
                break;

            case 'button_click':
                // Store button click in database (if you want to track this)
                logEvent($pdo, 'button_click', $event_data);
                break;

            case 'form_submit':
                // Store form submission event
                logEvent($pdo, 'form_submit', $event_data);
                break;

            case 'time_spent':
                // Store time spent data
                logEvent($pdo, 'time_spent', $event_data);
                break;

            default:
                // Store unknown event type
                logEvent($pdo, 'unknown', $event_data);
                break;
        }

        // Return success response
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        exit;
    }
}

// If we got here, something went wrong
header('Content-Type: application/json');
echo json_encode(['status' => 'error', 'message' => 'Invalid data received']);
exit;

/**
 * Function to store page view in database
 */
function storePageView($pdo, $data) {
    try {
        // Extract data
        $page = isset($data['page']) ? $data['page'] : '';
        $referrer = isset($data['referrer']) ? $data['referrer'] : '';
        $user_agent = isset($data['userAgent']) ? $data['userAgent'] : '';

        // Insert into visitors table
        $sql = "INSERT INTO visitors (ip_address, user_agent, page_visited, referrer)
                VALUES (:ip, :user_agent, :page, :referrer)";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':ip', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
        $stmt->bindParam(':user_agent', $user_agent, PDO::PARAM_STR);
        $stmt->bindParam(':page', $page, PDO::PARAM_STR);
        $stmt->bindParam(':referrer', $referrer, PDO::PARAM_STR);

        $stmt->execute();

        return true;
    } catch (PDOException $e) {
        // Log error but don't crash
        error_log("Analytics error (pageview): " . $e->getMessage());
        return false;
    }
}

/**
 * Function to log general event data (for custom analytics)
 * This could store data in a separate events table if needed
 */
function logEvent($pdo, $event_type, $data) {
    // Log event to a file for now
    // In a production system, you would store this in a database table

    $log_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'event_type' => $event_type,
        'data' => $data
    ];

    // Append to log file
    $log_entry = json_encode($log_data) . "\n";
    file_put_contents('logs/analytics_events.log', $log_entry, FILE_APPEND);

    return true;
}
