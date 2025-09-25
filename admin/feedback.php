<?php
/**
 * Feedback Management
 * View, edit, delete, and manage feedback submissions
 */

session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'update_status') {
            $id = $_POST['id'];
            $status = $_POST['status'];

            $sql = "UPDATE feedback SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->execute();

            $success_message = "Feedback status updated successfully!";

        } elseif ($action === 'delete') {
            $id = $_POST['id'];

            $sql = "DELETE FROM feedback WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $success_message = "Feedback deleted successfully!";

        } elseif ($action === 'bulk_action') {
            $bulk_action = $_POST['bulk_action'];
            $selected_ids = $_POST['selected_ids'] ?? [];

            if (!empty($selected_ids)) {
                $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
                
                if ($bulk_action === 'delete') {
                    $sql = "DELETE FROM feedback WHERE id IN ($placeholders)";
                } elseif (in_array($bulk_action, ['new', 'read', 'replied', 'archived'])) {
                    $sql = "UPDATE feedback SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)";
                    $params = array_merge([$bulk_action], $selected_ids);
                } else {
                    throw new Exception("Invalid bulk action");
                }

                $stmt = $pdo->prepare($sql);
                if ($bulk_action === 'delete') {
                    $stmt->execute($selected_ids);
                } else {
                    $stmt->execute($params);
                }

                $count = count($selected_ids);
                $success_message = "Bulk action completed on {$count} feedback entries!";
            }
        }

        // Log the activity
        $logSql = "INSERT INTO activity_log (user_id, activity, ip_address)
                  VALUES (:user_id, :activity, :ip_address)";
        $logStmt = $pdo->prepare($logSql);
        $activity = "Feedback " . $action . "ed";
        $logStmt->bindParam(':user_id', $_SESSION['admin_id'], PDO::PARAM_INT);
        $logStmt->bindParam(':activity', $activity, PDO::PARAM_STR);
        $logStmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
        $logStmt->execute();

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) FROM feedback $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get feedback data
$sql = "SELECT * FROM feedback $where_clause ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$feedback_list = $stmt->fetchAll();

// Get statistics
try {
    $stats_sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
        SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
        SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_count,
        SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived_count
        FROM feedback";
    $stats_stmt = $pdo->prepare($stats_sql);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch();
} catch (PDOException $e) {
    $stats = ['total' => 0, 'new_count' => 0, 'read_count' => 0, 'replied_count' => 0, 'archived_count' => 0];
}

$page_title = "Feedback Management";
include 'includes/header.php';
?>

<div class="feedback-container">
    <div class="feedback-header">
        <h2>Feedback Management</h2>
        <p>Manage customer feedback and reviews</p>
    </div>

    <?php if ($success_message): ?>
        <div class="flash-message success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="flash-message error"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-comments"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Feedback</div>
            </div>
        </div>
        <div class="stat-card new">
            <div class="stat-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['new_count']; ?></div>
                <div class="stat-label">New</div>
            </div>
        </div>
        <div class="stat-card read">
            <div class="stat-icon">
                <i class="fas fa-eye"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['read_count']; ?></div>
                <div class="stat-label">Read</div>
            </div>
        </div>
        <div class="stat-card replied">
            <div class="stat-icon">
                <i class="fas fa-reply"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['replied_count']; ?></div>
                <div class="stat-label">Replied</div>
            </div>
        </div>
        <div class="stat-card archived">
            <div class="stat-icon">
                <i class="fas fa-archive"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $stats['archived_count']; ?></div>
                <div class="stat-label">Archived</div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters-card">
        <div class="card-content">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>New</option>
                        <option value="read" <?php echo $status_filter === 'read' ? 'selected' : ''; ?>>Read</option>
                        <option value="replied" <?php echo $status_filter === 'replied' ? 'selected' : ''; ?>>Replied</option>
                        <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="search">Search:</label>
                    <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by name, email, subject, or message">
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Filter
                    </button>
                    <a href="feedback.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions -->
    <?php if (!empty($feedback_list)): ?>
    <div class="bulk-actions-card">
        <form method="POST" id="bulkForm">
            <input type="hidden" name="action" value="bulk_action">
            <div class="bulk-controls">
                <select name="bulk_action" id="bulkAction">
                    <option value="">Select Action</option>
                    <option value="new">Mark as New</option>
                    <option value="read">Mark as Read</option>
                    <option value="replied">Mark as Replied</option>
                    <option value="archived">Archive</option>
                    <option value="delete">Delete</option>
                </select>
                <button type="submit" class="btn btn-warning" onclick="return confirmBulkAction()">
                    <i class="fas fa-tasks"></i>
                    Apply to Selected
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <!-- Feedback List -->
    <div class="feedback-list-card">
        <div class="card-content">
            <?php if (empty($feedback_list)): ?>
                <div class="no-data">
                    <i class="fas fa-comments"></i>
                    <h4>No Feedback Found</h4>
                    <p>No feedback entries match your current filters.</p>
                </div>
            <?php else: ?>
                <div class="feedback-table">
                    <div class="table-header">
                        <div class="table-cell checkbox">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                        </div>
                        <div class="table-cell">Name</div>
                        <div class="table-cell">Email</div>
                        <div class="table-cell">Subject</div>
                        <div class="table-cell">Rating</div>
                        <div class="table-cell">Status</div>
                        <div class="table-cell">Date</div>
                        <div class="table-cell">Actions</div>
                    </div>
                    
                    <?php foreach ($feedback_list as $feedback): ?>
                        <div class="table-row">
                            <div class="table-cell checkbox">
                                <input type="checkbox" name="selected_ids[]" value="<?php echo $feedback['id']; ?>" 
                                       class="row-checkbox" form="bulkForm">
                            </div>
                            <div class="table-cell">
                                <strong><?php echo htmlspecialchars($feedback['name']); ?></strong>
                            </div>
                            <div class="table-cell">
                                <a href="mailto:<?php echo htmlspecialchars($feedback['email']); ?>">
                                    <?php echo htmlspecialchars($feedback['email']); ?>
                                </a>
                            </div>
                            <div class="table-cell">
                                <?php echo htmlspecialchars($feedback['subject'] ?: 'No subject'); ?>
                            </div>
                            <div class="table-cell">
                                <div class="rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $feedback['rating'] ? 'active' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="table-cell">
                                <span class="status-badge status-<?php echo $feedback['status']; ?>">
                                    <?php echo ucfirst($feedback['status']); ?>
                                </span>
                            </div>
                            <div class="table-cell">
                                <?php echo format_date($feedback['created_at']); ?>
                            </div>
                            <div class="table-cell">
                                <div class="action-buttons">
                                    <button class="btn-action btn-view" onclick="viewFeedback(<?php echo $feedback['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-action btn-edit" onclick="editStatus(<?php echo $feedback['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-action btn-delete" onclick="deleteFeedback(<?php echo $feedback['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="btn btn-secondary">
                            <i class="fas fa-chevron-left"></i>
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <span class="page-info">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                    </span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="btn btn-secondary">
                            Next
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- View Feedback Modal -->
<div class="modal" id="viewModal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3>Feedback Details</h3>
            <button class="modal-close" onclick="closeViewModal()">&times;</button>
        </div>
        <div class="modal-body" id="viewModalBody">
            <!-- Content will be loaded here -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeViewModal()">Close</button>
        </div>
    </div>
</div>

<!-- Edit Status Modal -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Update Status</h3>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST" id="editForm">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="id" id="editId">
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="editStatus">Status</label>
                    <select id="editStatus" name="status" required>
                        <option value="new">New</option>
                        <option value="read">Read</option>
                        <option value="replied">Replied</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Update Status</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal" id="deleteModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Delete Feedback</h3>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteId">
            
            <div class="modal-body">
                <p>Are you sure you want to delete this feedback? This action cannot be undone.</p>
            </div>
            
            <div class="modal-footer">
                <button type="submit" class="btn btn-danger">Delete</button>
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.feedback-container {
    max-width: 120rem;
    margin: 0 auto;
}

.feedback-header {
    margin-bottom: 3rem;
}

.feedback-header h2 {
    font-size: 2.8rem;
    margin-bottom: 0.5rem;
}

.feedback-header p {
    color: var(--gray-color);
    font-size: 1.6rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(20rem, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.stat-card {
    background-color: var(--light-color);
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    padding: 2rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.stat-icon {
    width: 5rem;
    height: 5rem;
    border-radius: 50%;
    background-color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--dark-color);
}

.stat-card.new .stat-icon {
    background-color: var(--info-color);
}

.stat-card.read .stat-icon {
    background-color: var(--warning-color);
}

.stat-card.replied .stat-icon {
    background-color: var(--success-color);
}

.stat-card.archived .stat-icon {
    background-color: var(--gray-color);
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 2.4rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 1.4rem;
    color: var(--gray-color);
    text-transform: uppercase;
    letter-spacing: 0.1rem;
}

.filters-card,
.bulk-actions-card,
.feedback-list-card {
    background-color: var(--light-color);
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    margin-bottom: 2rem;
}

.card-content {
    padding: 2rem;
}

.filters-form {
    display: flex;
    gap: 2rem;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    min-width: 15rem;
}

.filter-group label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--dark-color);
}

.filter-group input,
.filter-group select {
    padding: 1rem;
    border: 1px solid var(--gray-light);
    border-radius: var(--border-radius);
    font-size: 1.4rem;
}

.filter-actions {
    display: flex;
    gap: 1rem;
}

.bulk-controls {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.bulk-controls select {
    padding: 1rem;
    border: 1px solid var(--gray-light);
    border-radius: var(--border-radius);
    font-size: 1.4rem;
    min-width: 15rem;
}

.feedback-table {
    border: 1px solid var(--gray-light);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.table-header,
.table-row {
    display: grid;
    grid-template-columns: 0.5fr 1fr 1.5fr 1fr 0.8fr 0.8fr 0.8fr 1fr;
    align-items: center;
}

.table-header {
    background-color: var(--gray-light);
    font-weight: 600;
    padding: 1.5rem 1rem;
    border-bottom: 1px solid var(--gray-light);
}

.table-row {
    padding: 1.5rem 1rem;
    border-bottom: 1px solid var(--gray-light);
}

.table-row:last-child {
    border-bottom: none;
}

.table-row:hover {
    background-color: var(--body-bg);
}

.table-cell {
    padding: 0 1rem;
}

.checkbox input[type="checkbox"] {
    width: 1.8rem;
    height: 1.8rem;
}

.rating {
    display: flex;
    gap: 0.2rem;
}

.rating i {
    color: var(--gray-light);
    font-size: 1.4rem;
}

.rating i.active {
    color: var(--primary-color);
}

.status-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 1rem;
    font-size: 1.2rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.status-new {
    background-color: var(--info-color);
    color: var(--dark-color);
}

.status-badge.status-read {
    background-color: var(--warning-color);
    color: var(--light-color);
}

.status-badge.status-replied {
    background-color: var(--success-color);
    color: var(--light-color);
}

.status-badge.status-archived {
    background-color: var(--gray-color);
    color: var(--light-color);
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    width: 3rem;
    height: 3rem;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
}

.btn-view {
    background-color: var(--info-color);
    color: var(--dark-color);
}

.btn-view:hover {
    background-color: var(--primary-dark);
}

.btn-edit {
    background-color: var(--warning-color);
    color: var(--light-color);
}

.btn-edit:hover {
    background-color: #e55a2b;
}

.btn-delete {
    background-color: var(--danger-color);
    color: var(--light-color);
}

.btn-delete:hover {
    background-color: #b91c1c;
}

.pagination {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--gray-light);
}

.page-info {
    color: var(--gray-color);
    font-size: 1.4rem;
}

.no-data {
    text-align: center;
    padding: 4rem 2rem;
}

.no-data i {
    font-size: 4rem;
    color: var(--gray-color);
    margin-bottom: 1rem;
}

.no-data h4 {
    margin-bottom: 1rem;
    color: var(--dark-color);
}

.no-data p {
    color: var(--gray-color);
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 2000;
}

.modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: var(--light-color);
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    min-width: 50rem;
    max-width: 90vw;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-content.large {
    min-width: 70rem;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 2rem;
    border-bottom: 1px solid var(--gray-light);
}

.modal-header h3 {
    margin: 0;
    font-size: 1.8rem;
}

.modal-close {
    background: none;
    border: none;
    font-size: 2.4rem;
    cursor: pointer;
    color: var(--gray-color);
    transition: var(--transition);
}

.modal-close:hover {
    color: var(--dark-color);
}

.modal-body {
    padding: 2rem;
}

.modal-footer {
    display: flex;
    gap: 1rem;
    padding: 2rem;
    border-top: 1px solid var(--gray-light);
    justify-content: flex-end;
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

.btn-warning {
    background-color: var(--warning-color);
    color: var(--light-color);
}

.btn-warning:hover {
    background-color: #e55a2b;
}

.btn-danger {
    background-color: var(--danger-color);
    color: var(--light-color);
}

.btn-danger:hover {
    background-color: #b91c1c;
}

@media (max-width: 992px) {
    .filters-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        min-width: auto;
    }
    
    .table-header,
    .table-row {
        grid-template-columns: 0.5fr 1fr 1fr 0.8fr 0.8fr 1fr;
    }
    
    .table-cell:nth-child(3),
    .table-cell:nth-child(4) {
        display: none;
    }
    
    .modal-content {
        min-width: 90vw;
        margin: 2rem;
    }
}
</style>

<script>
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.row-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function confirmBulkAction() {
    const bulkAction = document.getElementById('bulkAction').value;
    const selectedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
    
    if (!bulkAction) {
        alert('Please select an action');
        return false;
    }
    
    if (selectedCheckboxes.length === 0) {
        alert('Please select at least one feedback entry');
        return false;
    }
    
    const actionText = bulkAction === 'delete' ? 'delete' : 'update';
    return confirm(`Are you sure you want to ${actionText} ${selectedCheckboxes.length} feedback entries?`);
}

function viewFeedback(id) {
    // In a real implementation, you would fetch the feedback details via AJAX
    // For now, we'll show a placeholder
    const modalBody = document.getElementById('viewModalBody');
    modalBody.innerHTML = `
        <div class="feedback-details">
            <h4>Loading feedback details...</h4>
            <p>Feedback ID: ${id}</p>
        </div>
    `;
    document.getElementById('viewModal').style.display = 'block';
}

function editStatus(id) {
    document.getElementById('editId').value = id;
    document.getElementById('editModal').style.display = 'block';
}

function deleteFeedback(id) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const viewModal = document.getElementById('viewModal');
    const editModal = document.getElementById('editModal');
    const deleteModal = document.getElementById('deleteModal');
    
    if (event.target === viewModal) {
        closeViewModal();
    }
    if (event.target === editModal) {
        closeEditModal();
    }
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
