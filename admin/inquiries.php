<?php
/**
 * Advanced Inquiries Management for AI-Solution Admin Panel
 * Features: Email replies, popup modals, status management, reply history
 */

session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

// Define admin username from session
$admin_username = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Unknown Admin';

$page_title = "Inquiries Management";

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_inquiry_details':
            $inquiry_id = (int)$_POST['inquiry_id'];
            
            try {
                // Get inquiry details
                $inquiry_sql = "SELECT * FROM inquiries WHERE id = :id";
                $inquiry_stmt = $pdo->prepare($inquiry_sql);
                $inquiry_stmt->bindParam(':id', $inquiry_id, PDO::PARAM_INT);
                $inquiry_stmt->execute();
                $inquiry = $inquiry_stmt->fetch();
                
                if (!$inquiry) {
                    echo json_encode(['success' => false, 'message' => 'Inquiry not found']);
                    exit;
                }
                
                // Get replies for this inquiry
                $replies_sql = "SELECT * FROM inquiry_replies WHERE inquiry_id = :inquiry_id ORDER BY created_at DESC";
                $replies_stmt = $pdo->prepare($replies_sql);
                $replies_stmt->bindParam(':inquiry_id', $inquiry_id, PDO::PARAM_INT);
                $replies_stmt->execute();
                $replies = $replies_stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'inquiry' => $inquiry,
                    'replies' => $replies
                ]);
                
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
            
        case 'send_reply':
            $inquiry_id = (int)$_POST['inquiry_id'];
            $subject = sanitize_input($_POST['subject']);
            $message = sanitize_input($_POST['message']);
            
            try {
                // Get inquiry details
                $inquiry_sql = "SELECT * FROM inquiries WHERE id = :id";
                $inquiry_stmt = $pdo->prepare($inquiry_sql);
                $inquiry_stmt->bindParam(':id', $inquiry_id, PDO::PARAM_INT);
                $inquiry_stmt->execute();
                $inquiry = $inquiry_stmt->fetch();
                
                if (!$inquiry) {
                    echo json_encode(['success' => false, 'message' => 'Inquiry not found']);
                    exit;
                }
                
                // Save reply to database
                $reply_sql = "INSERT INTO inquiry_replies (inquiry_id, admin_user, subject, message) 
                             VALUES (:inquiry_id, :admin_user, :subject, :message)";
                $reply_stmt = $pdo->prepare($reply_sql);
                $reply_stmt->bindParam(':inquiry_id', $inquiry_id, PDO::PARAM_INT);
                $reply_stmt->bindParam(':admin_user', $admin_username, PDO::PARAM_STR);
                $reply_stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
                $reply_stmt->bindParam(':message', $message, PDO::PARAM_STR);
                $reply_stmt->execute();
                
                $reply_id = $pdo->lastInsertId();
                
                // Update inquiry status and last reply time
                $inquiry_update = "UPDATE inquiries SET status = 'replied', last_reply_at = NOW() WHERE id = :id";
                $inquiry_update_stmt = $pdo->prepare($inquiry_update);
                $inquiry_update_stmt->bindParam(':id', $inquiry_id, PDO::PARAM_INT);
                $inquiry_update_stmt->execute();
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Reply saved successfully!',
                    'reply_id' => $reply_id
                ]);
                
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
    }
}

// Process status update if requested
if (isset($_GET['update_status'])) {
    $id = (int)$_GET['id'];
    $status = sanitize_input($_GET['status']);

    try {
        $sql = "UPDATE inquiries SET status = :status WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        set_flash_message('success', 'Inquiry status updated successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Error updating inquiry status: ' . $e->getMessage());
    }

    header("Location: inquiries.php");
    exit;
}

// Process inquiry deletion if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    try {
        $sql = "DELETE FROM inquiries WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        set_flash_message('success', 'Inquiry deleted successfully.');
    } catch (PDOException $e) {
        set_flash_message('danger', 'Error deleting inquiry: ' . $e->getMessage());
    }

    header("Location: inquiries.php");
    exit;
}

// Fetch all inquiries with reply count
$sql = "SELECT i.*, 
        COUNT(r.id) as reply_count,
        MAX(r.created_at) as last_reply_date
        FROM inquiries i 
        LEFT JOIN inquiry_replies r ON i.id = r.inquiry_id 
        GROUP BY i.id 
        ORDER BY i.created_at DESC";
$stmt = $pdo->query($sql);
$inquiries = $stmt->fetchAll();

// Status options
$status_options = [
    'new' => 'New',
    'read' => 'Read',
    'replied' => 'Replied',
    'archived' => 'Archived'
];

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
    SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
    SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_count,
    SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived_count
    FROM inquiries";
$stats_stmt = $pdo->query($stats_sql);
$stats = $stats_stmt->fetch();
?>

<?php include 'includes/header.php'; ?>
<body>
            <!-- Flash Messages -->
            <?php display_flash_message(); ?>

            <!-- Stats Section -->
            <section class="admin-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Inquiries</h3>
                        <div class="stat-value"><?php echo $stats['total']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>New</h3>
                        <div class="stat-value"><?php echo $stats['new_count']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Read</h3>
                        <div class="stat-value"><?php echo $stats['read_count']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Replied</h3>
                        <div class="stat-value"><?php echo $stats['replied_count']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Archived</h3>
                        <div class="stat-value"><?php echo $stats['archived_count']; ?></div>
                    </div>
                </div>
            </section>

            <!-- Inquiries List Section -->
            <section class="admin-section">
                <h3>All Inquiries</h3>

                <?php if (count($inquiries) > 0): ?>
                    <?php foreach ($inquiries as $inquiry): ?>
                        <div class="inquiry-card <?php echo $inquiry['status']; ?>">
                            <div class="inquiry-header">
                                <h4 class="inquiry-title"><?php echo htmlspecialchars($inquiry['name']); ?></h4>
                                <div class="inquiry-meta">
                                    <span class="inquiry-meta-item">
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($inquiry['email']); ?>
                                    </span>
                                    <span class="inquiry-meta-item">
                                        <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($inquiry['created_at'])); ?>
                                    </span>
                                    <span class="status-badge status-<?php echo $inquiry['status']; ?>">
                                        <?php echo $status_options[$inquiry['status']]; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="inquiry-body">
                                <?php if (!empty($inquiry['company'])): ?>
                                    <p><strong>Company:</strong> <?php echo htmlspecialchars($inquiry['company']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($inquiry['job_title'])): ?>
                                    <p><strong>Job Title:</strong> <?php echo htmlspecialchars($inquiry['job_title']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($inquiry['country'])): ?>
                                    <p><strong>Country:</strong> <?php echo htmlspecialchars($inquiry['country']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($inquiry['phone'])): ?>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($inquiry['phone']); ?></p>
                                <?php endif; ?>
                                
                                <h5>Message:</h5>
                                <div class="inquiry-message"><?php echo htmlspecialchars($inquiry['message']); ?></div>
                            </div>

                            <div class="inquiry-actions">
                                <div>
                                    <?php if ($inquiry['reply_count'] > 0): ?>
                                        <span class="inquiry-meta-item">
                                            <i class="fas fa-reply"></i> <?php echo $inquiry['reply_count']; ?> replies
                                        </span>
                                        <span class="inquiry-meta-item">
                                            <i class="fas fa-clock"></i> Last reply: <?php echo date('M d, Y', strtotime($inquiry['last_reply_date'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inquiry-meta-item">
                                            <i class="fas fa-info-circle"></i> No replies yet
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <button class="btn btn-primary btn-reply" data-id="<?php echo $inquiry['id']; ?>">
                                        <i class="fas fa-reply"></i> Reply
                                    </button>
                                    <button class="btn btn-info btn-view" data-id="<?php echo $inquiry['id']; ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <div class="dropdown" style="display: inline-block;">
                                        <button class="btn btn-secondary dropdown-toggle" type="button" id="statusDropdown<?php echo $inquiry['id']; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fas fa-cog"></i> Status
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="statusDropdown<?php echo $inquiry['id']; ?>">
                                            <?php foreach ($status_options as $value => $label): ?>
                                                <a class="dropdown-item" href="inquiries.php?update_status&id=<?php echo $inquiry['id']; ?>&status=<?php echo $value; ?>">
                                                    <?php echo $label; ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <a href="inquiries.php?delete=<?php echo $inquiry['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this inquiry?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No Inquiries Found</h3>
                        <p>There are no inquiries to display at this time.</p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Inquiry Modal -->
    <div id="inquiryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Inquiry Details</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body" id="inquiryModalBody">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary modal-close">Close</button>
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <div id="replyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Reply to Inquiry</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form id="replyForm">
                <div class="modal-body">
                    <input type="hidden" id="replyInquiryId" name="inquiry_id">
                    
                    <div class="form-group">
                        <label for="replySubject">Subject</label>
                        <input type="text" id="replySubject" name="subject" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="replyMessage">Message</label>
                        <textarea id="replyMessage" name="message" class="form-control" rows="8" required></textarea>
                    </div>
                    
                    <div id="replyHistory" class="reply-list">
                        <!-- Previous replies will be shown here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Reply</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Modal functionality
            const inquiryModal = $('#inquiryModal');
            const replyModal = $('#replyModal');
            
            // Open inquiry modal
            $('.btn-view').click(function() {
                const inquiryId = $(this).data('id');
                
                $.ajax({
                    url: 'inquiries.php',
                    method: 'POST',
                    data: {
                        action: 'get_inquiry_details',
                        inquiry_id: inquiryId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const inquiry = response.inquiry;
                            let html = `
                                <div class="inquiry-details">
                                    <h4>${inquiry.name}</h4>
                                    <p><strong>Email:</strong> ${inquiry.email}</p>
                                    ${inquiry.phone ? `<p><strong>Phone:</strong> ${inquiry.phone}</p>` : ''}
                                    ${inquiry.company ? `<p><strong>Company:</strong> ${inquiry.company}</p>` : ''}
                                    ${inquiry.job_title ? `<p><strong>Job Title:</strong> ${inquiry.job_title}</p>` : ''}
                                    ${inquiry.country ? `<p><strong>Country:</strong> ${inquiry.country}</p>` : ''}
                                    <p><strong>Date:</strong> ${new Date(inquiry.created_at).toLocaleDateString()}</p>
                                    <p><strong>Status:</strong> <span class="status-badge status-${inquiry.status}">${inquiry.status.charAt(0).toUpperCase() + inquiry.status.slice(1)}</span></p>
                                    
                                    <h5>Message:</h5>
                                    <div class="inquiry-message">${inquiry.message.replace(/\n/g, '<br>')}</div>
                                </div>
                            `;
                            
                            if (response.replies.length > 0) {
                                html += `<div class="reply-list"><h5>Replies (${response.replies.length}):</h5>`;
                                
                                response.replies.forEach(reply => {
                                    html += `
                                        <div class="reply-item">
                                            <div class="reply-header">
                                                <span class="reply-user">${reply.admin_user}</span>
                                                <span class="reply-date">${new Date(reply.created_at).toLocaleString()}</span>
                                            </div>
                                            <h6>${reply.subject}</h6>
                                            <div class="reply-message">${reply.message.replace(/\n/g, '<br>')}</div>
                                        </div>
                                    `;
                                });
                                
                                html += `</div>`;
                            }
                            
                            $('#inquiryModalBody').html(html);
                            inquiryModal.show();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error loading inquiry details: ' + error);
                    }
                });
            });
            
            // Open reply modal
            $('.btn-reply').click(function() {
                const inquiryId = $(this).data('id');
                $('#replyInquiryId').val(inquiryId);
                
                $.ajax({
                    url: 'inquiries.php',
                    method: 'POST',
                    data: {
                        action: 'get_inquiry_details',
                        inquiry_id: inquiryId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const inquiry = response.inquiry;
                            $('#replySubject').val('Re: Your inquiry from ' + new Date(inquiry.created_at).toLocaleDateString());
                            
                            let replyHistoryHtml = '';
                            if (response.replies.length > 0) {
                                replyHistoryHtml = '<h5>Previous Replies:</h5>';
                                
                                response.replies.forEach(reply => {
                                    replyHistoryHtml += `
                                        <div class="reply-item">
                                            <div class="reply-header">
                                                <span class="reply-user">${reply.admin_user}</span>
                                                <span class="reply-date">${new Date(reply.created_at).toLocaleString()}</span>
                                            </div>
                                            <h6>${reply.subject}</h6>
                                            <div class="reply-message">${reply.message.replace(/\n/g, '<br>')}</div>
                                        </div>
                                    `;
                                });
                            }
                            
                            $('#replyHistory').html(replyHistoryHtml);
                            replyModal.show();
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error loading inquiry details: ' + error);
                    }
                });
            });
            
            // Submit reply form
            $('#replyForm').submit(function(e) {
                e.preventDefault();
                
                const formData = {
                    action: 'send_reply',
                    inquiry_id: $('#replyInquiryId').val(),
                    subject: $('#replySubject').val(),
                    message: $('#replyMessage').val()
                };
                
                $.ajax({
                    url: 'inquiries.php',
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert(response.message);
                            replyModal.hide();
                            location.reload(); // Refresh to show the new reply
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error sending reply: ' + error);
                    }
                });
            });
            
            // Close modals
            $('.modal-close').click(function() {
                inquiryModal.hide();
                replyModal.hide();
            });
            
            // Close modal when clicking outside
            $(window).click(function(e) {
                if (e.target === inquiryModal[0]) {
                    inquiryModal.hide();
                }
                if (e.target === replyModal[0]) {
                    replyModal.hide();
                }
            });
        });
    </script>
    <style>
        /* CSS Variables */
:root {
    --primary-color: #007bff;
    --secondary-color: #6c757d;
    --dark-color: #343a40;
    --gray-color: #6c757d;
    --light-color: #f8f9fa;
    --success-color: #28a745;
    --danger-color: #dc3545;
    --info-color: #17a2b8;
}

/* General Styles */
.inquiries-container {
    max-width: 120rem;
    margin: 0 auto;
    padding: 2rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(25rem, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: var(--light-color);
    border-radius: 1rem;
    padding: 2.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 2rem;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(255, 215, 0, 0.15);
}

.stat-icon {
    width: 6rem;
    height: 6rem;
    border-radius: 1rem;
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.4rem;
    color: var(--dark-color);
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 3.2rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 1.4rem;
    color: var(--gray-color);
}
/* Inquiry Cards */
.inquiry-card {
    background: var(--light-color);
    border-radius: 1rem;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.inquiry-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.inquiry-card.new { border-left: 5px solid var(--primary-color); }
.inquiry-card.read { border-left: 5px solid var(--info-color); }
.inquiry-card.replied { border-left: 5px solid var(--success-color); }
.inquiry-card.archived { border-left: 5px solid var(--gray-color); }

.inquiry-header {
    margin-bottom: 1.5rem;
}

.inquiry-title {
    font-size: 2rem;
    margin: 0 0 1rem 0;
    color: var(--dark-color);
}

.inquiry-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    font-size: 1.4rem;
    color: var(--gray-color);
}

.inquiry-meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 1.2rem;
    font-weight: 500;
}

.status-new { background: var(--primary-color); color: var(--light-color); }
.status-read { background: var(--info-color); color: var(--light-color); }
.status-replied { background: var(--success-color); color: var(--light-color); }
.status-archived { background: var(--gray-color); color: var(--light-color); }

.inquiry-body h5 {
    font-size: 1.6rem;
    margin: 1.5rem 0 0.5rem;
    color: var(--dark-color);
}

.inquiry-message {
    font-size: 1.4rem;
    color: var(--dark-color);
    line-height: 1.6;
}

.inquiry-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.5rem;
}

.btn {
    padding: 0.8rem 1.5rem;
    border: none;
    border-radius: 0.5rem;
    font-size: 1.4rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary { background: var(--primary-color); color: var(--light-color); }
.btn-primary:hover { background: darken(var(--primary-color), 10%); }
.btn-info { background: var(--info-color); color: var(--light-color); }
.btn-info:hover { background: darken(var(--info-color), 10%); }
.btn-secondary { background: var(--gray-color); color: var(--light-color); }
.btn-secondary:hover { background: darken(var(--gray-color), 10%); }
.btn-danger { background: var(--danger-color); color: var(--light-color); }
.btn-danger:hover { background: darken(var(--danger-color), 10%); }

.dropdown {
    position: relative;
}

.dropdown-toggle::after {
    margin-left: 0.5rem;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--light-color);
    border-radius: 0.5rem;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    min-width: 15rem;
    z-index: 1000;
}

.dropdown-item {
    padding: 0.8rem 1.5rem;
    font-size: 1.4rem;
    color: var(--dark-color);
    text-decoration: none;
    display: block;
}

.dropdown-item:hover {
    background: var(--primary-color);
    color: var(--light-color);
}

/* Modals */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: var(--light-color);
    border-radius: 1rem;
    max-width: 60rem;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--gray-color);
}

.modal-title {
    font-size: 2rem;
    margin: 0;
    color: var(--dark-color);
}

.modal-close {
    background: none;
    border: none;
    font-size: 2rem;
    cursor: pointer;
    color: var(--gray-color);
}

.modal-body {
    padding: 2rem;
}

.modal-footer {
    padding: 1.5rem 2rem;
    border-top: 1px solid var(--gray-color);
    text-align: right;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-control {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid var(--gray-color);
    border-radius: 0.5rem;
    font-size: 1.4rem;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
}

.reply-list {
    margin-top: 2rem;
}

.reply-item {
    border-left: 3px solid var(--primary-color);
    padding-left: 1.5rem;
    margin-bottom: 1.5rem;
}

.reply-header {
    display: flex;
    justify-content: space-between;
    font-size: 1.3rem;
    color: var(--gray-color);
    margin-bottom: 0.5rem;
}

.reply-user {
    font-weight: 600;
}

.reply-date {
    font-style: italic;
}

.reply-message {
    font-size: 1.4rem;
    line-height: 1.6;
    color: var(--dark-color);
}

.empty-state {
    text-align: center;
    padding: 3rem;
    background: var(--light-color);
    border-radius: 1rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.empty-state h3 {
    font-size: 2.4rem;
    margin-bottom: 1rem;
    color: var(--dark-color);
}

.empty-state p {
    font-size: 1.6rem;
    color: var(--gray-color);
}

/* Flash Messages (assuming defined in config.php) */
.alert {
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 2rem;
}

.alert-success {
    background: var(--success-color);
    color: var(--light-color);
}

.alert-danger {
    background: var(--danger-color);
    color: var(--light-color);
}
    </style>
</body>
</html>