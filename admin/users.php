<?php
/**
 * User Management for AI-Solutions Admin Panel
 */

session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$page_title = "User Management";

// Delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $delete_sql = "DELETE FROM users WHERE id = :id";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $delete_stmt->execute();
        set_flash_message('success', 'User deleted successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Error deleting user: ' . $e->getMessage());
    }
    header("Location: users.php");
    exit;
}

// Save user (add/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $role = sanitize_input($_POST['role']);
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    try {
        if ($id > 0) {
            // Update existing user
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $sql = "UPDATE users SET username = :username, email = :email, role = :role, password = :password WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            } else {
                $sql = "UPDATE users SET username = :username, email = :email, role = :role WHERE id = :id";
                $stmt = $pdo->prepare($sql);
            }
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        } else {
            // Create new user
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
        }

        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':role', $role, PDO::PARAM_STR);
        $stmt->execute();

        $msg = ($id > 0) ? 'updated' : 'created';
        set_flash_message('success', "User {$msg} successfully.");
        header("Location: users.php");
        exit;

    } catch (PDOException $e) {
        set_flash_message('danger', 'Error saving user: ' . $e->getMessage());
    }
}

// Edit user
$edit_mode = false;
$user = ['id' => '', 'username' => '', 'email' => '', 'role' => 'admin'];

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $edit_stmt->bindParam(':id', $edit_id, PDO::PARAM_INT);
    $edit_stmt->execute();

    if ($edit_stmt->rowCount() > 0) {
        $user = $edit_stmt->fetch();
        $edit_mode = true;
    }
}

// Fetch all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

include 'includes/header.php';
?>

<!-- User Form Section -->
<div class="admin-form">
    <h3><?php echo $edit_mode ? 'Edit User' : 'Add New User'; ?></h3>
    <form action="users.php" method="POST">
        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">

        <div class="form-row">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="manager" <?php echo ($user['role'] === 'manager') ? 'selected' : ''; ?>>Manager</option>
                    <option value="editor" <?php echo ($user['role'] === 'editor') ? 'selected' : ''; ?>>Editor</option>
                </select>
            </div>

            <div class="form-group">
                <label for="password">Password <?php echo $edit_mode ? '(Leave blank to keep current)' : ''; ?></label>
                <input type="password" id="password" name="password" class="form-control" <?php echo $edit_mode ? '' : 'required'; ?>>
            </div>
        </div>

        <div class="btn-group">
            <button type="submit" name="save_user" class="btn btn-primary">
                <?php echo $edit_mode ? 'Update User' : 'Add User'; ?>
            </button>
            <a href="users.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<!-- Users List Section -->
<div class="admin-form">
    <h3>User Accounts</h3>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($users) > 0): ?>
                <?php foreach ($users as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['username']); ?></td>
                        <td><?php echo htmlspecialchars($item['email']); ?></td>
                        <td>
                            <span class="status-badge status-active">
                                <?php echo ucfirst($item['role']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                        <td class="table-actions">
                            <a href="users.php?edit=<?php echo $item['id']; ?>" class="btn btn-primary btn-action">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="users.php?delete=<?php echo $item['id']; ?>" class="btn btn-danger btn-action btn-delete">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">No users found. Add your first user using the form above.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>