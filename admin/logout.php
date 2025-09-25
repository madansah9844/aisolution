<?php
/**
 * Admin Logout Script
 */

// Start session
session_start();

// Include database configuration
require_once 'includes/config.php';

// Log the logout activity if user is logged in
if (isset($_SESSION['admin_id'])) {
    try {
        $logSql = "INSERT INTO activity_log (user_id, activity, ip_address)
                  VALUES (:user_id, :activity, :ip_address)";
        $logStmt = $pdo->prepare($logSql);
        $activity = "User logout";
        $logStmt->bindParam(':user_id', $_SESSION['admin_id'], PDO::PARAM_INT);
        $logStmt->bindParam(':activity', $activity, PDO::PARAM_STR);
        $logStmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
        $logStmt->execute();
    } catch (PDOException $e) {
        // Log error but don't prevent logout
        error_log("Logout logging error: " . $e->getMessage());
    }
}

// Destroy all session data
session_destroy();

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page
header("Location: login.php");
exit;
?>
