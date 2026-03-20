<?php
// This file is included by admin/index.php
global $conn;

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle mark as read
if (isset($_GET['mark_read']) && isset($_GET['id'])) {
    $message_id = intval($_GET['id']);
    $sql = "UPDATE contact_messages SET is_read = 1 WHERE message_id = $message_id";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['success_message'] = "Message marked as read";
        logActivity('contact_read', "Marked message #$message_id as read");
    }
    header("Location: ?page=contact-messages");
    exit();
}

// Handle mark as unread
if (isset($_GET['mark_unread']) && isset($_GET['id'])) {
    $message_id = intval($_GET['id']);
    $sql = "UPDATE contact_messages SET is_read = 0 WHERE message_id = $message_id";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['success_message'] = "Message marked as unread";
        logActivity('contact_unread', "Marked message #$message_id as unread");
    }
    header("Location: ?page=contact-messages");
    exit();
}

// Handle reply to message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $message_id = intval($_POST['message_id']);
    $reply_text = mysqli_real_escape_string($conn, trim($_POST['reply_text']));
    
    if (!empty($reply_text)) {
        // Get original message details
        $get_sql = "SELECT name, email, subject, message FROM contact_messages WHERE message_id = $message_id";
        $get_result = mysqli_query($conn, $get_sql);
        $original = mysqli_fetch_assoc($get_result);
        
        // Update database
        $update_sql = "UPDATE contact_messages SET 
                       replied = 1, 
                       reply_message = '$reply_text', 
                       replied_by = $user_id, 
                       replied_at = NOW() 
                       WHERE message_id = $message_id";
        
        if (mysqli_query($conn, $update_sql)) {
            // Send reply email to user
            sendReplyEmail($original['email'], $original['name'], $original['subject'], $reply_text);
            
            $_SESSION['success_message'] = "Reply sent successfully!";
            logActivity('contact_replied', "Replied to message #$message_id");
        } else {
            $_SESSION['error_message'] = "Failed to save reply";
        }
    } else {
        $_SESSION['error_message'] = "Reply message cannot be empty";
    }
    
    header("Location: ?page=contact-messages&view=" . $message_id);
    exit();
}

// Handle delete message
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $message_id = intval($_GET['id']);
    $sql = "DELETE FROM contact_messages WHERE message_id = $message_id";
    if (mysqli_query($conn, $sql)) {
        $_SESSION['success_message'] = "Message deleted successfully";
        logActivity('contact_delete', "Deleted message #$message_id");
    } else {
        $_SESSION['error_message'] = "Failed to delete message";
    }
    header("Location: ?page=contact-messages");
    exit();
}

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['message_ids'])) {
    $action = $_POST['bulk_action'];
    $message_ids = $_POST['message_ids'];
    $count = 0;
    
    foreach ($message_ids as $id) {
        $id = intval($id);
        if ($action == 'mark_read') {
            mysqli_query($conn, "UPDATE contact_messages SET is_read = 1 WHERE message_id = $id");
            $count++;
        } elseif ($action == 'mark_unread') {
            mysqli_query($conn, "UPDATE contact_messages SET is_read = 0 WHERE message_id = $id");
            $count++;
        } elseif ($action == 'delete') {
            mysqli_query($conn, "DELETE FROM contact_messages WHERE message_id = $id");
            $count++;
        }
    }
    
    $_SESSION['success_message'] = "$count messages processed successfully";
    header("Location: ?page=contact-messages");
    exit();
}

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Build query
$where_conditions = ["1=1"];

if ($filter == 'unread') {
    $where_conditions[] = "is_read = 0";
} elseif ($filter == 'read') {
    $where_conditions[] = "is_read = 1";
} elseif ($filter == 'replied') {
    $where_conditions[] = "replied = 1";
} elseif ($filter == 'unreplied') {
    $where_conditions[] = "replied = 0";
}

if ($search) {
    $where_conditions[] = "(name LIKE '%$search%' OR email LIKE '%$search%' OR subject LIKE '%$search%' OR message LIKE '%$search%')";
}

$where_clause = implode(" AND ", $where_conditions);

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$records_per_page = 15;
$offset = ($page - 1) * $records_per_page;

// Get total records
$count_sql = "SELECT COUNT(*) as total FROM contact_messages WHERE $where_clause";
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get messages
$sql = "SELECT * FROM contact_messages 
        WHERE $where_clause 
        ORDER BY 
            CASE WHEN is_read = 0 THEN 0 ELSE 1 END,
            submitted_at DESC 
        LIMIT $offset, $records_per_page";
$result = mysqli_query($conn, $sql);
$messages = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get statistics
$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_count,
                SUM(CASE WHEN replied = 1 THEN 1 ELSE 0 END) as replied_count,
                SUM(CASE WHEN replied = 0 THEN 1 ELSE 0 END) as unreplied_count,
                COUNT(DISTINCT email) as unique_senders,
                COUNT(*) as messages_this_month
              FROM contact_messages
              WHERE MONTH(submitted_at) = MONTH(CURDATE())";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// Get single message view
$view_message = null;
if (isset($_GET['view'])) {
    $view_id = intval($_GET['view']);
    $view_sql = "SELECT * FROM contact_messages WHERE message_id = $view_id";
    $view_result = mysqli_query($conn, $view_sql);
    $view_message = mysqli_fetch_assoc($view_result);
    
    // Mark as read if viewing
    if ($view_message && !$view_message['is_read']) {
        mysqli_query($conn, "UPDATE contact_messages SET is_read = 1 WHERE message_id = $view_id");
        $view_message['is_read'] = 1;
    }
}
?>

<style>
.message-card {
    transition: all 0.3s ease;
    cursor: pointer;
    border-left: 4px solid transparent;
}

.message-card.unread {
    border-left-color: #007bff;
    background: #f8f9fa;
}

.message-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.message-stats {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}

.stat-badge {
    background: white;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    flex: 1;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.stat-badge h3 {
    margin: 0;
    font-size: 1.8rem;
}

.stat-badge p {
    margin: 5px 0 0;
    color: #666;
    font-size: 0.85rem;
}

.message-preview {
    max-height: 60px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.reply-box {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin-top: 20px;
}

.filter-btn.active {
    background: #007bff;
    color: white;
}

.message-content {
    max-height: 400px;
    overflow-y: auto;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    margin: 15px 0;
}

.reply-history {
    background: #e8f4fd;
    padding: 15px;
    border-radius: 10px;
    margin-top: 15px;
}
</style>

<div class="row">
    <div class="col-12">
        <!-- Statistics Cards -->
        <div class="message-stats">
            <div class="stat-badge">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Messages</p>
            </div>
            <div class="stat-badge">
                <h3 class="text-primary"><?php echo $stats['unread']; ?></h3>
                <p>Unread</p>
            </div>
            <div class="stat-badge">
                <h3 class="text-success"><?php echo $stats['replied_count']; ?></h3>
                <p>Replied</p>
            </div>
            <div class="stat-badge">
                <h3 class="text-warning"><?php echo $stats['unreplied_count']; ?></h3>
                <p>Awaiting Reply</p>
            </div>
            <div class="stat-badge">
                <h3><?php echo $stats['unique_senders']; ?></h3>
                <p>Unique Senders</p>
            </div>
        </div>
        
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($view_message): ?>
            <!-- Single Message View -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-envelope-open-text me-2 text-primary"></i>
                        Message from <?php echo htmlspecialchars($view_message['name']); ?>
                    </h5>
                    <div>
                        <a href="?page=contact-messages" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to List
                        </a>
                        <a href="?delete=1&id=<?php echo $view_message['message_id']; ?>" 
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Delete this message?')">
                            <i class="fas fa-trash me-1"></i>Delete
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="120">Name:</th>
                                    <td><?php echo htmlspecialchars($view_message['name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td><a href="mailto:<?php echo $view_message['email']; ?>"><?php echo htmlspecialchars($view_message['email']); ?></a></td>
                                </tr>
                                <tr>
                                    <th>Phone:</th>
                                    <td><?php echo $view_message['phone'] ?: 'Not provided'; ?></td>
                                </tr>
                                <tr>
                                    <th>Subject:</th>
                                    <td><?php echo htmlspecialchars($view_message['subject'] ?: 'No subject'); ?></td>
                                </tr>
                                <tr>
                                    <th>Service Interest:</th>
                                    <td><?php echo $view_message['service_interest'] ?: 'Not specified'; ?></td>
                                </tr>
                                <tr>
                                    <th>Submitted:</th>
                                    <td><?php echo date('F d, Y h:i A', strtotime($view_message['submitted_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <?php if ($view_message['is_read']): ?>
                                            <span class="badge bg-success">Read</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Unread</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($view_message['replied']): ?>
                                            <span class="badge bg-info ms-2">Replied</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <strong><i class="fas fa-comment me-2"></i>Message</strong>
                                </div>
                                <div class="card-body message-content">
                                    <?php echo nl2br(htmlspecialchars($view_message['message'])); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reply Section -->
                    <div class="reply-box">
                        <h6><i class="fas fa-reply me-2"></i>Reply to this message</h6>
                        <form method="POST" action="">
                            <input type="hidden" name="message_id" value="<?php echo $view_message['message_id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">Your Reply</label>
                                <textarea name="reply_text" class="form-control" rows="5" required 
                                          placeholder="Type your reply here..."></textarea>
                            </div>
                            <button type="submit" name="reply_message" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Send Reply
                            </button>
                        </form>
                    </div>
                    
                    <!-- Reply History -->
                    <?php if ($view_message['replied'] && $view_message['reply_message']): ?>
                    <div class="reply-history">
                        <h6><i class="fas fa-history me-2"></i>Reply History</h6>
                        <div class="bg-white p-3 rounded">
                            <p class="mb-2"><strong>Replied by:</strong> 
                                <?php 
                                if ($view_message['replied_by']) {
                                    $admin_sql = "SELECT full_name FROM users WHERE user_id = " . $view_message['replied_by'];
                                    $admin_result = mysqli_query($conn, $admin_sql);
                                    $admin = mysqli_fetch_assoc($admin_result);
                                    echo $admin['full_name'];
                                } else {
                                    echo 'Admin';
                                }
                                ?>
                            </p>
                            <p><strong>Replied at:</strong> <?php echo date('F d, Y h:i A', strtotime($view_message['replied_at'])); ?></p>
                            <hr>
                            <p><?php echo nl2br(htmlspecialchars($view_message['reply_message'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Messages List View -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h5 class="mb-0"><i class="fas fa-envelope me-2 text-primary"></i>Contact Messages</h5>
                        </div>
                        <div class="col-md-8">
                            <form method="GET" action="" class="d-flex gap-2">
                                <input type="hidden" name="page" value="contact-messages">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search by name, email, subject..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                                <?php if ($search): ?>
                                    <a href="?page=contact-messages" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Tabs -->
                <div class="card-header bg-white border-top py-2">
                    <div class="btn-group" role="group">
                        <a href="?page=contact-messages&filter=all" 
                           class="btn btn-sm <?php echo $filter == 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            All (<?php echo $stats['total']; ?>)
                        </a>
                        <a href="?page=contact-messages&filter=unread" 
                           class="btn btn-sm <?php echo $filter == 'unread' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Unread (<?php echo $stats['unread']; ?>)
                        </a>
                        <a href="?page=contact-messages&filter=unreplied" 
                           class="btn btn-sm <?php echo $filter == 'unreplied' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Awaiting Reply (<?php echo $stats['unreplied_count']; ?>)
                        </a>
                        <a href="?page=contact-messages&filter=replied" 
                           class="btn btn-sm <?php echo $filter == 'replied' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Replied (<?php echo $stats['replied_count']; ?>)
                        </a>
                    </div>
                    
                    <!-- Bulk Actions -->
                    <div class="float-end">
                        <form method="POST" action="" id="bulkActionForm" class="d-inline">
                            <select name="bulk_action" class="form-select form-select-sm d-inline-block w-auto" onchange="if(this.value) document.getElementById('bulkActionForm').submit()">
                                <option value="">Bulk Actions</option>
                                <option value="mark_read">Mark as Read</option>
                                <option value="mark_unread">Mark as Unread</option>
                                <option value="delete">Delete</option>
                            </select>
                            <input type="hidden" name="message_ids" id="selectedMessages" value="">
                        </form>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                                    </th>
                                    <th>Status</th>
                                    <th>From</th>
                                    <th>Subject</th>
                                    <th>Message</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($messages)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                            <h5>No messages found</h5>
                                            <p class="text-muted">When users send messages from the contact page, they will appear here.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($messages as $message): ?>
                                    <tr class="<?php echo !$message['is_read'] ? 'fw-bold' : ''; ?>">
                                        <td>
                                            <input type="checkbox" class="message-checkbox" value="<?php echo $message['message_id']; ?>">
                                        </td>
                                        <td>
                                            <?php if (!$message['is_read']): ?>
                                                <span class="badge bg-primary">New</span>
                                            <?php endif; ?>
                                            <?php if ($message['replied']): ?>
                                                <span class="badge bg-info">Replied</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($message['name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($message['email']); ?></small>
                                            <?php if ($message['phone']): ?>
                                                <br><small><?php echo $message['phone']; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($message['subject'] ?: 'No subject'); ?></td>
                                        <td class="message-preview">
                                            <?php echo htmlspecialchars(substr($message['message'], 0, 100)); ?>
                                            <?php if (strlen($message['message']) > 100): ?>...<?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('d M Y', strtotime($message['submitted_at'])); ?>
                                            <br>
                                            <small class="text-muted"><?php echo timeAgo($message['submitted_at']); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="?page=contact-messages&view=<?php echo $message['message_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if (!$message['is_read']): ?>
                                                    <a href="?mark_read=1&id=<?php echo $message['message_id']; ?>" 
                                                       class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="?mark_unread=1&id=<?php echo $message['message_id']; ?>" 
                                                       class="btn btn-sm btn-outline-warning">
                                                        <i class="fas fa-undo"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="?delete=1&id=<?php echo $message['message_id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Delete this message?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="card-footer bg-white">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=contact-messages&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page-1; ?>">
                                    Previous
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= min($total_pages, 5); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=contact-messages&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=contact-messages&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page+1; ?>">
                                    Next
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.message-checkbox');
    
    checkboxes.forEach(cb => {
        cb.checked = selectAll.checked;
    });
    
    updateSelectedMessages();
}

function updateSelectedMessages() {
    const checkboxes = document.querySelectorAll('.message-checkbox:checked');
    const selectedIds = Array.from(checkboxes).map(cb => cb.value);
    document.getElementById('selectedMessages').value = selectedIds.join(',');
}

// Add event listeners to checkboxes
document.querySelectorAll('.message-checkbox').forEach(cb => {
    cb.addEventListener('change', updateSelectedMessages);
});
</script>

<?php
// Function to send reply email
function sendReplyEmail($to, $name, $subject, $reply) {
    $email_subject = "Re: " . $subject;
    
    $message = "
    <html>
    <head>
        <title>Reply to your inquiry</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .reply { background: #e8f4fd; padding: 15px; margin: 15px 0; border-left: 4px solid #007bff; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>JED BINARY TECH SOLUTIONS</h2>
            </div>
            <div class='content'>
                <p>Dear $name,</p>
                <p>Thank you for contacting JED BINARY TECH SOLUTIONS. We appreciate your inquiry and have responded below:</p>
                
                <div class='reply'>
                    <strong>Our Response:</strong><br>
                    " . nl2br($reply) . "
                </div>
                
                <p>If you have any further questions, please don't hesitate to contact us again.</p>
                
                <p>Best Regards,<br>
                <strong>JED BINARY TECH Team</strong></p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " JED BINARY TECH SOLUTIONS. All rights reserved.</p>
                <p>This is an automated response. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    sendEmail($to, $email_subject, $message);
}
?>