<?php
/**
 * Inquiries Management for AI-Solutions Admin Panel
 */

session_start();
require_once 'includes/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

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
                
                echo json_encode([
                    'success' => true,
                    'inquiry' => $inquiry
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

// Fetch all inquiries
$sql = "SELECT * FROM inquiries ORDER BY created_at DESC";
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

include 'includes/header.php';
?>

<!-- Stats Section -->
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

<!-- Inquiries List -->
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
                    <span class="inquiry-meta-item">
                        <i class="fas fa-info-circle"></i> ID: <?php echo $inquiry['id']; ?>
                    </span>
                </div>
                <div class="table-actions">
                    <button class="btn btn-info btn-action btn-view" data-id="<?php echo $inquiry['id']; ?>">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-secondary btn-action dropdown-toggle">
                            <i class="fas fa-cog"></i> Status
                        </button>
                        <div class="dropdown-menu">
                            <?php foreach ($status_options as $value => $label): ?>
                                <a class="dropdown-item" href="inquiries.php?update_status&id=<?php echo $inquiry['id']; ?>&status=<?php echo $value; ?>">
                                    <?php echo $label; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <a href="inquiries.php?delete=<?php echo $inquiry['id']; ?>" class="btn btn-danger btn-action btn-delete">
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
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
                    
                    $('#inquiryModalBody').html(html);
                    $('#inquiryModal').css('display', 'flex');
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr, status, error) {
                alert('Error loading inquiry details: ' + error);
            }
        });
    });
    
    // Close modals
    $('.modal-close').click(function() {
        $('.modal').hide();
    });
    
    // Close modal when clicking outside
    $(window).click(function(e) {
        if ($(e.target).hasClass('modal')) {
            $('.modal').hide();
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>