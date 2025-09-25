<?php
// Include database configuration
require_once 'admin/includes/config.php';

// Track page visit
track_visit($pdo);

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if email is provided
    if (isset($_POST['email']) && !empty($_POST['email'])) {
        $email = sanitize_input($_POST['email']);

        // Validate email
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                // Get name if provided
                $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : null;
                $source = isset($_POST['source']) ? sanitize_input($_POST['source']) : 'website';

                // Check if email already exists in subscribers table
                $check_email = "SELECT id FROM subscribers WHERE email = :email";
                $stmt_check = $pdo->prepare($check_email);
                $stmt_check->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt_check->execute();

                if ($stmt_check->rowCount() > 0) {
                    // Email exists, update status to active if it was unsubscribed
                    $update = "UPDATE subscribers SET status = 'active',
                              name = COALESCE(:name, name)
                              WHERE email = :email AND status = 'unsubscribed'";
                    $stmt_update = $pdo->prepare($update);
                    $stmt_update->bindParam(':email', $email, PDO::PARAM_STR);
                    $stmt_update->bindParam(':name', $name, PDO::PARAM_STR);
                    $stmt_update->execute();

                    $response['success'] = true;
                    $response['message'] = 'Thank you! You are already subscribed to our newsletter.';
                } else {
                    // Add new email to the database
                    $insert = "INSERT INTO subscribers (email, name, status) VALUES (:email, :name, 'active')";
                    $stmt_insert = $pdo->prepare($insert);
                    $stmt_insert->bindParam(':email', $email, PDO::PARAM_STR);
                    $stmt_insert->bindParam(':name', $name, PDO::PARAM_STR);
                    $stmt_insert->execute();

                    $response['success'] = true;
                    $response['message'] = 'Thank you for subscribing to our newsletter!';
                }

                // Set redirect page based on the referring page
                $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
                if (strpos($referer, 'blogs.php') !== false) {
                    $response['redirect'] = 'blogs.php';
                } elseif (strpos($referer, 'blog-single.php') !== false) {
                    $response['redirect'] = 'blogs.php';
                } elseif (strpos($referer, 'events.php') !== false) {
                    $response['redirect'] = 'events.php';
                } elseif (strpos($referer, 'event-single.php') !== false) {
                    $response['redirect'] = 'events.php';
                } else {
                    $response['redirect'] = 'index.html';
                }

            } catch (PDOException $e) {
                $response['message'] = 'Database error: ' . $e->getMessage();
                error_log("Newsletter subscription error: " . $e->getMessage());
            }
        } else {
            $response['message'] = 'Please enter a valid email address.';
        }
    } else {
        $response['message'] = 'Please enter your email address.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

// If it's an AJAX request, return JSON
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Otherwise, set flash message and redirect
if ($response['success']) {
    set_flash_message('success', $response['message']);
} else {
    set_flash_message('danger', $response['message']);
}

// Redirect back to referring page
$redirect_to = !empty($response['redirect']) ? $response['redirect'] : 'index.html';
header("Location: $redirect_to");
exit;
?>
