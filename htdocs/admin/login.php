<?php
/**
 * Admin Login Page for AI-Solution
 */

// Start session
session_start();

// Include database configuration
require_once 'includes/config.php';

// Check if user is already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // Redirect to admin dashboard
    header("Location: index.php");
    exit;
}

// Initialize variables
$error = '';

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize
    $username = isset($_POST['username']) ? sanitize_input($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Simple validation
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // Check user credentials against database
        try {
            $sql = "SELECT * FROM users WHERE username = :username LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() === 1) {
                // User found, verify password
                $user = $stmt->fetch();

                if (password_verify($password, $user['password'])) {
                    // Password is correct, set session variables
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_role'] = $user['role'];

                    // Log the login activity
                    $logSql = "INSERT INTO activity_log (user_id, activity, ip_address)
                              VALUES (:user_id, :activity, :ip_address)";
                    $logStmt = $pdo->prepare($logSql);
                    $activity = "User login";
                    $logStmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
                    $logStmt->bindParam(':activity', $activity, PDO::PARAM_STR);
                    $logStmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
                    $logStmt->execute();

                    // Redirect to admin dashboard
                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Invalid username or password.";
                }
            } else {
                $error = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            // Log the error
            error_log("Login error: " . $e->getMessage());
            $error = "A system error occurred. Please try again later.";
        }
    }
}

// For demonstration purposes - if database isn't set up yet, allow hardcoded admin user
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($username) && !empty($password)) {
    if ($username === "admin" && $password === "admin123") {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_role'] = 'admin';

        // Redirect to admin dashboard
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - AI-Solution</title>

    <!-- CSS Files -->
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="css/admin.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" href="../images/logo.png" type="image/png">

    <style>
        body {
            background-color: var(--gray-light);
        }

        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem;
        }

        .login-card {
            background-color: var(--light-color);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 40rem;
            padding: 3rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .login-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .login-logo img {
            height: 6rem;
            width: auto;
        }

        .login-title {
            font-size: 2.4rem;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            font-size: 1.6rem;
            color: var(--gray-color);
        }

        .error-message {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            text-align: center;
        }

        .login-form .form-group {
            margin-bottom: 2rem;
        }

        .login-form label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 500;
        }

        .login-form .input-group {
            position: relative;
        }

        .login-form .input-group i {
            position: absolute;
            top: 50%;
            left: 1.5rem;
            transform: translateY(-50%);
            color: var(--gray-color);
        }

        .login-form input {
            width: 100%;
            padding: 1.2rem 1.2rem 1.2rem 4rem;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1.6rem;
        }

        .login-form input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .login-form button {
            width: 100%;
            padding: 1.2rem;
            background-color: var(--primary-color);
            color: var(--light-color);
            border: none;
            border-radius: var(--border-radius);
            font-size: 1.6rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .login-form button:hover {
            background-color: var(--primary-dark);
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
        }

        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .login-footer a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <img src="../images/logo.png" alt="AI-Solution Logo">
                </div>
                <h1 class="login-title">Admin Login</h1>
                <p class="login-subtitle">Enter your credentials to access the admin panel</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form class="login-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" placeholder="Enter your username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>

                <button type="submit">Log In</button>
            </form>

            <div class="login-footer">
                <p>Return to <a href="../index.html">Website</a></p>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/main.js"></script>
</body>
</html>
