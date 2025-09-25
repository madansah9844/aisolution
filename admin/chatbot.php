<?php
/**
 * Chatbot Management
 * Manage keywords and responses for the chatbot
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
        if ($action === 'add') {
            $keyword = sanitize_input($_POST['keyword']);
            $response = sanitize_input($_POST['response']);
            $active = isset($_POST['active']) ? 1 : 0;

            $sql = "INSERT INTO chatbot_responses (keyword, response, active) VALUES (:keyword, :response, :active)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':keyword', $keyword, PDO::PARAM_STR);
            $stmt->bindParam(':response', $response, PDO::PARAM_STR);
            $stmt->bindParam(':active', $active, PDO::PARAM_INT);
            $stmt->execute();

            $success_message = "Chatbot response added successfully!";

        } elseif ($action === 'edit') {
            $id = $_POST['id'];
            $keyword = sanitize_input($_POST['keyword']);
            $response = sanitize_input($_POST['response']);
            $active = isset($_POST['active']) ? 1 : 0;

            $sql = "UPDATE chatbot_responses SET keyword = :keyword, response = :response, active = :active WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':keyword', $keyword, PDO::PARAM_STR);
            $stmt->bindParam(':response', $response, PDO::PARAM_STR);
            $stmt->bindParam(':active', $active, PDO::PARAM_INT);
            $stmt->execute();

            $success_message = "Chatbot response updated successfully!";

        } elseif ($action === 'delete') {
            $id = $_POST['id'];

            $sql = "DELETE FROM chatbot_responses WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $success_message = "Chatbot response deleted successfully!";

        } elseif ($action === 'toggle') {
            $id = $_POST['id'];

            $sql = "UPDATE chatbot_responses SET active = NOT active WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $success_message = "Chatbot response status updated!";
        }

        // Log the activity
        $logSql = "INSERT INTO activity_log (user_id, activity, ip_address)
                  VALUES (:user_id, :activity, :ip_address)";
        $logStmt = $pdo->prepare($logSql);
        $activity = "Chatbot responses " . $action . "ed";
        $logStmt->bindParam(':user_id', $_SESSION['admin_id'], PDO::PARAM_INT);
        $logStmt->bindParam(':activity', $activity, PDO::PARAM_STR);
        $logStmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
        $logStmt->execute();

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get chatbot responses
try {
    $sql = "SELECT * FROM chatbot_responses ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $responses = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error loading chatbot responses";
    $responses = [];
}

$page_title = "Chatbot Management";
include 'includes/header.php';
?>

<div class="chatbot-container">
    <div class="chatbot-header">
        <h2>Chatbot Management</h2>
        <p>Manage keywords and responses for the website chatbot</p>
    </div>

    <?php if ($success_message): ?>
        <div class="flash-message success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="flash-message error"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <div class="chatbot-content">
        <!-- Add New Response Form -->
        <div class="add-response-card">
            <div class="card-header">
                <h3><i class="fas fa-plus"></i> Add New Response</h3>
            </div>
            <div class="card-content">
                <form method="POST" id="addResponseForm">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="keyword">Keyword/Trigger</label>
                            <input type="text" id="keyword" name="keyword" 
                                   placeholder="e.g., hello, services, contact" required>
                            <p class="form-note">Enter keywords that will trigger this response (comma-separated for multiple)</p>
                        </div>
                        <div class="form-group">
                            <label for="active">Status</label>
                            <select id="active" name="active">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="response">Response</label>
                        <textarea id="response" name="response" rows="4" 
                                  placeholder="Enter the chatbot's response..." required></textarea>
                        <p class="form-note">Enter the message that the chatbot will send when this keyword is triggered</p>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Add Response
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i>
                            Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Responses List -->
        <div class="responses-list-card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Chatbot Responses</h3>
                <div class="card-actions">
                    <span class="response-count"><?php echo count($responses); ?> responses</span>
                </div>
            </div>
            <div class="card-content">
                <?php if (empty($responses)): ?>
                    <div class="no-data">
                        <i class="fas fa-robot"></i>
                        <h4>No Responses Found</h4>
                        <p>Add your first chatbot response using the form above.</p>
                    </div>
                <?php else: ?>
                    <div class="responses-table">
                        <div class="table-header">
                            <div class="table-cell">Keyword</div>
                            <div class="table-cell">Response</div>
                            <div class="table-cell">Status</div>
                            <div class="table-cell">Created</div>
                            <div class="table-cell">Actions</div>
                        </div>
                        
                        <?php foreach ($responses as $response): ?>
                            <div class="table-row">
                                <div class="table-cell">
                                    <strong><?php echo htmlspecialchars($response['keyword']); ?></strong>
                                </div>
                                <div class="table-cell">
                                    <div class="response-text">
                                        <?php echo htmlspecialchars(substr($response['response'], 0, 100)); ?>
                                        <?php if (strlen($response['response']) > 100): ?>
                                            <span class="text-more">...</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="table-cell">
                                    <span class="status-badge <?php echo $response['active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $response['active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                                <div class="table-cell">
                                    <?php echo format_date($response['created_at']); ?>
                                </div>
                                <div class="table-cell">
                                    <div class="action-buttons">
                                        <button class="btn-action btn-edit" onclick="editResponse(<?php echo $response['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-action btn-toggle" onclick="toggleResponse(<?php echo $response['id']; ?>)">
                                            <i class="fas fa-toggle-<?php echo $response['active'] ? 'on' : 'off'; ?>"></i>
                                        </button>
                                        <button class="btn-action btn-delete" onclick="deleteResponse(<?php echo $response['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Response Modal -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Response</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" id="editForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="editId">
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="editKeyword">Keyword/Trigger</label>
                    <input type="text" id="editKeyword" name="keyword" required>
                </div>
                
                <div class="form-group">
                    <label for="editResponse">Response</label>
                    <textarea id="editResponse" name="response" rows="4" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="editActive" name="active" value="1">
                        Active
                    </label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal" id="deleteModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Delete Response</h3>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <form method="POST" id="deleteForm">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteId">
            
            <div class="modal-body">
                <p>Are you sure you want to delete this chatbot response? This action cannot be undone.</p>
            </div>
            
            <div class="modal-footer">
                <button type="submit" class="btn btn-danger">Delete</button>
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.chatbot-container {
    max-width: 120rem;
    margin: 0 auto;
}

.chatbot-header {
    margin-bottom: 3rem;
}

.chatbot-header h2 {
    font-size: 2.8rem;
    margin-bottom: 0.5rem;
}

.chatbot-header p {
    color: var(--gray-color);
    font-size: 1.6rem;
}

.chatbot-content {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 3rem;
}

.add-response-card,
.responses-list-card {
    background-color: var(--light-color);
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
}

.card-header {
    background-color: var(--gray-light);
    padding: 2rem;
    border-bottom: 1px solid var(--gray-light);
    display: flex;
    align-items: center;
    justify-content: space-between;
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

.card-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.response-count {
    background-color: var(--primary-color);
    color: var(--dark-color);
    padding: 0.5rem 1rem;
    border-radius: 1.5rem;
    font-size: 1.2rem;
    font-weight: 600;
}

.card-content {
    padding: 2rem;
}

.form-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
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
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 1.2rem;
    border: 1px solid var(--gray-light);
    border-radius: var(--border-radius);
    font-size: 1.4rem;
    transition: var(--transition);
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
}

.form-note {
    font-size: 1.2rem;
    color: var(--gray-color);
    margin-top: 0.5rem;
    font-style: italic;
}

.form-actions {
    display: flex;
    gap: 1rem;
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

.btn-danger {
    background-color: var(--danger-color);
    color: var(--light-color);
}

.btn-danger:hover {
    background-color: #b91c1c;
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

.responses-table {
    border: 1px solid var(--gray-light);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.table-header {
    display: grid;
    grid-template-columns: 1fr 2fr 1fr 1fr 1fr;
    background-color: var(--gray-light);
    font-weight: 600;
    padding: 1rem;
    border-bottom: 1px solid var(--gray-light);
}

.table-row {
    display: grid;
    grid-template-columns: 1fr 2fr 1fr 1fr 1fr;
    padding: 1.5rem 1rem;
    border-bottom: 1px solid var(--gray-light);
    align-items: center;
}

.table-row:last-child {
    border-bottom: none;
}

.table-cell {
    padding: 0 1rem;
}

.response-text {
    font-size: 1.4rem;
    line-height: 1.4;
}

.text-more {
    color: var(--gray-color);
    font-style: italic;
}

.status-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 1rem;
    font-size: 1.2rem;
    font-weight: 600;
}

.status-badge.active {
    background-color: var(--success-color);
    color: var(--light-color);
}

.status-badge.inactive {
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

.btn-edit {
    background-color: var(--info-color);
    color: var(--dark-color);
}

.btn-edit:hover {
    background-color: var(--primary-dark);
}

.btn-toggle {
    background-color: var(--warning-color);
    color: var(--light-color);
}

.btn-toggle:hover {
    background-color: #e55a2b;
}

.btn-delete {
    background-color: var(--danger-color);
    color: var(--light-color);
}

.btn-delete:hover {
    background-color: #b91c1c;
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

@media (max-width: 992px) {
    .chatbot-content {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .table-header,
    .table-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .table-cell {
        padding: 0.5rem 0;
    }
    
    .modal-content {
        min-width: 90vw;
        margin: 2rem;
    }
}
</style>

<script>
function editResponse(id) {
    // Get response data (you might want to fetch this via AJAX)
    // For now, we'll use the data from the table
    const row = document.querySelector(`[onclick="editResponse(${id})"]`).closest('.table-row');
    const keyword = row.children[0].textContent.trim();
    const response = row.children[1].textContent.trim().replace('...', '');
    const active = row.children[2].textContent.trim() === 'Active';
    
    document.getElementById('editId').value = id;
    document.getElementById('editKeyword').value = keyword;
    document.getElementById('editResponse').value = response;
    document.getElementById('editActive').checked = active;
    
    document.getElementById('editModal').style.display = 'block';
}

function toggleResponse(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function deleteResponse(id) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const editModal = document.getElementById('editModal');
    const deleteModal = document.getElementById('deleteModal');
    
    if (event.target === editModal) {
        closeModal();
    }
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
}

// Form validation
document.getElementById('addResponseForm').addEventListener('submit', function(e) {
    const keyword = document.getElementById('keyword').value.trim();
    const response = document.getElementById('response').value.trim();
    
    if (!keyword || !response) {
        alert('Please fill in all required fields');
        e.preventDefault();
    }
});

document.getElementById('editForm').addEventListener('submit', function(e) {
    const keyword = document.getElementById('editKeyword').value.trim();
    const response = document.getElementById('editResponse').value.trim();
    
    if (!keyword || !response) {
        alert('Please fill in all required fields');
        e.preventDefault();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
