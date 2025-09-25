<?php
/**
 * Form processing script for AI-Solution
 * Handles contact form submissions and stores them in the database
 */

// Start session
session_start();

// Include database configuration
require_once 'admin/includes/config.php';

// Initialize variables
$errors = [];
$success = false;
$data = [];

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize
    $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)) : '';
    $phone = isset($_POST['phone']) ? sanitize_input($_POST['phone']) : '';
    $company = isset($_POST['company']) ? sanitize_input($_POST['company']) : '';
    $country = isset($_POST['country']) ? sanitize_input($_POST['country']) : '';
    $job_title = isset($_POST['job_title']) ? sanitize_input($_POST['job_title']) : '';
    $message = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';

    // Validate required fields
    if (empty($name)) {
        $errors[] = "Name is required";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($country)) {
        $errors[] = "Country is required";
    }

    if (empty($job_title)) {
        $errors[] = "Job title is required";
    }

    if (empty($message)) {
        $errors[] = "Message is required";
    }

    // If no errors, process the form
    if (empty($errors)) {
        // Store data in array for database insertion or email sending
        $data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'company' => $company,
            'country' => $country,
            'job_title' => $job_title,
            'message' => $message,
            'date' => date('Y-m-d H:i:s')
        ];

        // Store in database
        $success = storeInDatabase($pdo, $data);

        // Send email notification
        $emailSuccess = sendEmail($data);

        // If everything is successful, redirect with success message
        if ($success) {
            // Set session success message if sessions are being used
            // $_SESSION['success_message'] = "Thank you for your inquiry! We will get back to you soon.";

            // Redirect to thank you page or back to contact with success parameter
            header("Location: contact.html?success=1");
            exit;
        } else {
            $errors[] = "There was an error processing your inquiry. Please try again later.";
        }
    }
}

/**
 * Function to store form data in database
 */
function storeInDatabase($pdo, $data) {
    try {
        // Prepare SQL statement
        $sql = "INSERT INTO inquiries (name, email, phone, company, country, job_title, message, status)
                VALUES (:name, :email, :phone, :company, :country, :job_title, :message, :status)";

        // Prepare and execute the statement
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindParam(':email', $data['email'], PDO::PARAM_STR);
        $stmt->bindParam(':phone', $data['phone'], PDO::PARAM_STR);
        $stmt->bindParam(':company', $data['company'], PDO::PARAM_STR);
        $stmt->bindParam(':country', $data['country'], PDO::PARAM_STR);
        $stmt->bindParam(':job_title', $data['job_title'], PDO::PARAM_STR);
        $stmt->bindParam(':message', $data['message'], PDO::PARAM_STR);

        // Set default status to 'new'
        $status = 'new';
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);

        // Execute the statement
        $result = $stmt->execute();

        return $result;
    } catch(PDOException $e) {
        // Log the error
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

/**
 * Function to send email notification
 */
function sendEmail($data) {
    // Set up email details
    $to = "info@ai-solutions.com"; // Replace with actual email
    $subject = "New Inquiry from " . $data['name'];

    // Compose email body
    $body = "You have received a new inquiry from the website:\n\n"
          . "Name: " . $data['name'] . "\n"
          . "Email: " . $data['email'] . "\n";

    if (!empty($data['phone'])) {
        $body .= "Phone: " . $data['phone'] . "\n";
    }

    if (!empty($data['company'])) {
        $body .= "Company: " . $data['company'] . "\n";
    }

    if (!empty($data['country'])) {
        $body .= "Country: " . $data['country'] . "\n";
    }

    if (!empty($data['job_title'])) {
        $body .= "Job Title: " . $data['job_title'] . "\n";
    }

    $body .= "Message: " . $data['message'] . "\n\n"
           . "This inquiry was submitted on " . $data['date'] . ".";

    // Set headers
    $headers = "From: noreply@ai-solutions.com" . "\r\n"
             . "Reply-To: " . $data['email'] . "\r\n"
             . "X-Mailer: PHP/" . phpversion();

    // Attempt to send email
    // Commented out to prevent actual sending in this example
    // return mail($to, $subject, $body, $headers);

    // For demonstration purposes, just return true
    return true;
}

// If there are errors and it's an AJAX request, return JSON response
if (!empty($errors) && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// If it's not AJAX and there are errors, redirect back with error information
if (!empty($errors)) {
    // Redirect back to contact form with error information
    $error_string = implode(',', $errors);
    header("Location: contact.html?error=" . urlencode($error_string));
    exit;
}
?>
