<?php
/**
 * Company Settings Management
 * Logo, Favicon, and Company Information
 */

session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$page_title = "Company Settings";
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $settings = [
            'company_name' => sanitize_input($_POST['company_name']),
            'company_email' => sanitize_input($_POST['company_email']),
            'company_phone' => sanitize_input($_POST['company_phone']),
            'company_address' => sanitize_input($_POST['company_address']),
            'company_description' => sanitize_input($_POST['company_description']),
        ];

        // Handle file uploads
        $upload_dir = '../images/';
        
        // Handle logo upload
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
            $logo_file = $_FILES['company_logo'];
            $logo_extension = pathinfo($logo_file['name'], PATHINFO_EXTENSION);
            $logo_filename = 'logo.' . $logo_extension;
            $logo_path = $upload_dir . $logo_filename;
            
            // Validate file type
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
            if (in_array(strtolower($logo_extension), $allowed_types)) {
                if (move_uploaded_file($logo_file['tmp_name'], $logo_path)) {
                    $settings['company_logo'] = 'images/' . $logo_filename;
                } else {
                    throw new Exception("Failed to upload logo");
                }
            } else {
                throw new Exception("Invalid logo file type. Allowed: " . implode(', ', $allowed_types));
            }
        }

        // Handle favicon upload
        if (isset($_FILES['company_favicon']) && $_FILES['company_favicon']['error'] === UPLOAD_ERR_OK) {
            $favicon_file = $_FILES['company_favicon'];
            $favicon_extension = pathinfo($favicon_file['name'], PATHINFO_EXTENSION);
            $favicon_filename = 'favicon.' . $favicon_extension;
            $favicon_path = $upload_dir . $favicon_filename;
            
            // Validate file type
            $allowed_types = ['ico', 'png', 'jpg', 'jpeg'];
            if (in_array(strtolower($favicon_extension), $allowed_types)) {
                if (move_uploaded_file($favicon_file['tmp_name'], $favicon_path)) {
                    $settings['company_favicon'] = 'images/' . $favicon_filename;
                } else {
                    throw new Exception("Failed to upload favicon");
                }
            } else {
                throw new Exception("Invalid favicon file type. Allowed: " . implode(', ', $allowed_types));
            }
        }

        // Update settings in database
        foreach ($settings as $key => $value) {
            $sql = "INSERT INTO company_settings (setting_key, setting_value) 
                    VALUES (:key, :value) 
                    ON DUPLICATE KEY UPDATE setting_value = :value, updated_at = CURRENT_TIMESTAMP";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':key', $key, PDO::PARAM_STR);
            $stmt->bindParam(':value', $value, PDO::PARAM_STR);
            $stmt->execute();
        }

        // Log the activity
        $logSql = "INSERT INTO activity_log (user_id, activity, ip_address)
                  VALUES (:user_id, :activity, :ip_address)";
        $logStmt = $pdo->prepare($logSql);
        $activity = "Company settings updated";
        $logStmt->bindParam(':user_id', $_SESSION['admin_id'], PDO::PARAM_INT);
        $logStmt->bindParam(':activity', $activity, PDO::PARAM_STR);
        $logStmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
        $logStmt->execute();

        $success_message = "Company settings updated successfully!";

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get current settings
try {
    $sql = "SELECT setting_key, setting_value FROM company_settings";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $error_message = "Error loading settings";
    $settings_data = [];
}

// Set default values
$defaults = [
    'company_name' => 'AI-Solutions',
    'company_email' => 'info@ai-solutions.com',
    'company_phone' => '+44 123 456 7890',
    'company_address' => 'Sunderland, UK',
    'company_description' => 'Leading AI solutions provider specializing in virtual assistants, prototyping, and custom AI development.',
    'company_logo' => 'images/logo.png',
    'company_favicon' => 'images/logo.png'
];

$settings_data = array_merge($defaults, $settings_data);

$page_title = "Company Settings";
include 'includes/header.php';
?>

<div class="settings-container">
    <div class="settings-header">
        <h2>Company Settings</h2>
        <p>Manage your company logo, favicon, and general information</p>
    </div>

    <div class="settings-content">
        <?php if ($success_message): ?>
            <div class="flash-message success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="flash-message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" id="settingsForm">
            <div class="settings-grid">
                <!-- Company Information -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3><i class="fas fa-building"></i> Company Information</h3>
                    </div>
                    <div class="card-content">
                        <div class="form-group">
                            <label for="company_name">Company Name</label>
                            <input type="text" id="company_name" name="company_name" 
                                   value="<?php echo htmlspecialchars($settings_data['company_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="company_email">Email Address</label>
                            <input type="email" id="company_email" name="company_email" 
                                   value="<?php echo htmlspecialchars($settings_data['company_email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="company_phone">Phone Number</label>
                            <input type="text" id="company_phone" name="company_phone" 
                                   value="<?php echo htmlspecialchars($settings_data['company_phone']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="company_address">Address</label>
                            <textarea id="company_address" name="company_address" rows="3"><?php echo htmlspecialchars($settings_data['company_address']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="company_description">Company Description</label>
                            <textarea id="company_description" name="company_description" rows="4"><?php echo htmlspecialchars($settings_data['company_description']); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Logo Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3><i class="fas fa-image"></i> Logo Settings</h3>
                    </div>
                    <div class="card-content">
                        <div class="current-logo">
                            <h4>Current Logo</h4>
                            <div class="logo-preview">
                                <img src="../<?php echo htmlspecialchars($settings_data['company_logo']); ?>" 
                                     alt="Current Logo" id="currentLogo">
                                <p class="logo-path"><?php echo htmlspecialchars($settings_data['company_logo']); ?></p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="company_logo">Upload New Logo</label>
                            <input type="file" id="company_logo" name="company_logo" 
                                   accept="image/jpeg,image/jpg,image/png,image/gif,image/svg+xml">
                            <p class="form-note">Recommended size: 200x60px. Supported formats: JPG, PNG, GIF, SVG</p>
                        </div>

                        <div class="logo-preview-new" id="logoPreview" style="display: none;">
                            <h4>New Logo Preview</h4>
                            <img id="newLogoPreview" alt="New Logo Preview">
                        </div>
                    </div>
                </div>

                <!-- Favicon Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3><i class="fas fa-star"></i> Favicon Settings</h3>
                    </div>
                    <div class="card-content">
                        <div class="current-favicon">
                            <h4>Current Favicon</h4>
                            <div class="favicon-preview">
                                <img src="../<?php echo htmlspecialchars($settings_data['company_favicon']); ?>" 
                                     alt="Current Favicon" id="currentFavicon">
                                <p class="favicon-path"><?php echo htmlspecialchars($settings_data['company_favicon']); ?></p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="company_favicon">Upload New Favicon</label>
                            <input type="file" id="company_favicon" name="company_favicon" 
                                   accept="image/x-icon,image/png,image/jpeg">
                            <p class="form-note">Recommended size: 32x32px or 16x16px. Supported formats: ICO, PNG, JPG</p>
                        </div>

                        <div class="favicon-preview-new" id="faviconPreview" style="display: none;">
                            <h4>New Favicon Preview</h4>
                            <img id="newFaviconPreview" alt="New Favicon Preview">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Save Settings
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.settings-container {
    max-width: 120rem;
    margin: 0 auto;
}

.settings-header {
    margin-bottom: 3rem;
}

.settings-header h2 {
    font-size: 2.8rem;
    margin-bottom: 0.5rem;
}

.settings-header p {
    color: var(--gray-color);
    font-size: 1.6rem;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(40rem, 1fr));
    gap: 3rem;
    margin-bottom: 3rem;
}

.settings-card {
    background-color: var(--light-color);
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
}

.card-header {
    background-color: var(--gray-light);
    padding: 2rem;
    border-bottom: 1px solid var(--gray-light);
}

.card-header h3 {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 0;
    font-size: 1.8rem;
}

.card-header i {
    color: var(--primary-color);
}

.card-content {
    padding: 2rem;
}

.form-group {
    margin-bottom: 2rem;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.8rem;
    color: var(--dark-color);
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 1.2rem;
    border: 1px solid var(--gray-light);
    border-radius: var(--border-radius);
    font-size: 1.4rem;
    transition: var(--transition);
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
}

.form-group input[type="file"] {
    padding: 0.8rem;
    border: 2px dashed var(--gray-light);
    background-color: var(--body-bg);
}

.form-group input[type="file"]:hover {
    border-color: var(--primary-color);
}

.form-note {
    font-size: 1.2rem;
    color: var(--gray-color);
    margin-top: 0.5rem;
    font-style: italic;
}

.current-logo,
.current-favicon {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--gray-light);
}

.current-logo h4,
.current-favicon h4 {
    margin-bottom: 1rem;
    font-size: 1.4rem;
}

.logo-preview,
.favicon-preview {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.logo-preview img,
.favicon-preview img {
    max-height: 6rem;
    max-width: 20rem;
    border: 1px solid var(--gray-light);
    border-radius: var(--border-radius);
    padding: 1rem;
    background-color: var(--body-bg);
}

.logo-path,
.favicon-path {
    font-size: 1.2rem;
    color: var(--gray-color);
    margin: 0;
}

.logo-preview-new,
.favicon-preview-new {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--gray-light);
}

.logo-preview-new h4,
.favicon-preview-new h4 {
    margin-bottom: 1rem;
    font-size: 1.4rem;
}

.logo-preview-new img,
.favicon-preview-new img {
    max-height: 6rem;
    max-width: 20rem;
    border: 1px solid var(--gray-light);
    border-radius: var(--border-radius);
    padding: 1rem;
    background-color: var(--body-bg);
}

.form-actions {
    display: flex;
    gap: 1rem;
    padding-top: 2rem;
    border-top: 1px solid var(--gray-light);
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.8rem;
    padding: 1.2rem 2rem;
    border: none;
    border-radius: var(--border-radius);
    font-size: 1.4rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: var(--transition);
}

.btn-primary {
    background-color: var(--primary-color);
    color: var(--dark-color);
}

.btn-primary:hover {
    background-color: var(--secondary-color);
}

.btn-secondary {
    background-color: var(--gray-light);
    color: var(--dark-color);
}

.btn-secondary:hover {
    background-color: var(--gray-color);
}

@media (max-width: 768px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .logo-preview,
    .favicon-preview {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Logo preview
    const logoInput = document.getElementById('company_logo');
    const logoPreview = document.getElementById('logoPreview');
    const newLogoPreview = document.getElementById('newLogoPreview');
    const currentLogo = document.getElementById('currentLogo');

    logoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                newLogoPreview.src = e.target.result;
                logoPreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    // Favicon preview
    const faviconInput = document.getElementById('company_favicon');
    const faviconPreview = document.getElementById('faviconPreview');
    const newFaviconPreview = document.getElementById('newFaviconPreview');
    const currentFavicon = document.getElementById('currentFavicon');

    faviconInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                newFaviconPreview.src = e.target.result;
                faviconPreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    // Form validation
    document.getElementById('settingsForm').addEventListener('submit', function(e) {
        const companyName = document.getElementById('company_name').value.trim();
        const companyEmail = document.getElementById('company_email').value.trim();

        if (!companyName) {
            alert('Company name is required');
            e.preventDefault();
            return;
        }

        if (!companyEmail) {
            alert('Company email is required');
            e.preventDefault();
            return;
        }

        if (companyEmail && !isValidEmail(companyEmail)) {
            alert('Please enter a valid email address');
            e.preventDefault();
            return;
        }
    });

    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
