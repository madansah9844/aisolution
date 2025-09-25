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

        set_flash_message('success', "Company settings updated successfully!");

    } catch (Exception $e) {
        set_flash_message('danger', $e->getMessage());
    }
}

// Get current settings
try {
    $sql = "SELECT setting_key, setting_value FROM company_settings";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    set_flash_message('danger', "Error loading settings");
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

include 'includes/header.php';
?>

<div class="admin-form">
    <h3>Company Information</h3>
    <form method="POST" enctype="multipart/form-data" id="settingsForm">
        <div class="form-group">
            <label for="company_name">Company Name</label>
            <input type="text" id="company_name" name="company_name" class="form-control"
                   value="<?php echo htmlspecialchars($settings_data['company_name']); ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="company_email">Email Address</label>
                <input type="email" id="company_email" name="company_email" class="form-control"
                       value="<?php echo htmlspecialchars($settings_data['company_email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="company_phone">Phone Number</label>
                <input type="text" id="company_phone" name="company_phone" class="form-control"
                       value="<?php echo htmlspecialchars($settings_data['company_phone']); ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="company_address">Address</label>
            <textarea id="company_address" name="company_address" class="form-control" rows="3"><?php echo htmlspecialchars($settings_data['company_address']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="company_description">Company Description</label>
            <textarea id="company_description" name="company_description" class="form-control" rows="4"><?php echo htmlspecialchars($settings_data['company_description']); ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="company_logo">Upload New Logo</label>
                <input type="file" id="company_logo" name="company_logo" class="form-control"
                       accept="image/jpeg,image/jpg,image/png,image/gif,image/svg+xml">
                <div class="image-preview">
                    <img src="../<?php echo htmlspecialchars($settings_data['company_logo']); ?>" 
                         alt="Current Logo" id="currentLogo">
                    <p>Current logo: <?php echo htmlspecialchars($settings_data['company_logo']); ?></p>
                </div>
            </div>

            <div class="form-group">
                <label for="company_favicon">Upload New Favicon</label>
                <input type="file" id="company_favicon" name="company_favicon" class="form-control"
                       accept="image/x-icon,image/png,image/jpeg">
                <div class="image-preview">
                    <img src="../<?php echo htmlspecialchars($settings_data['company_favicon']); ?>" 
                         alt="Current Favicon" id="currentFavicon">
                    <p>Current favicon: <?php echo htmlspecialchars($settings_data['company_favicon']); ?></p>
                </div>
            </div>
        </div>

        <div class="btn-group">
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

<?php include 'includes/footer.php'; ?>