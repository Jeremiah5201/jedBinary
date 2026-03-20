<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Require admin access
requireAdmin();

$page_title = 'Admin Dashboard';
include '../includes/header.php';

// Function to safely execute queries and handle errors
function safeQuery($conn, $sql, $default = null) {
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        error_log("SQL Error: " . mysqli_error($conn) . " in query: " . $sql);
        return $default;
    }
    return $result;
}

// Get statistics with error handling
$stats = [];

// Total users
$users_sql = "SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN user_type = 'admin' THEN 1 ELSE 0 END) as admins,
                SUM(CASE WHEN user_type = 'client' THEN 1 ELSE 0 END) as clients,
                SUM(CASE WHEN DATE(registration_date) = CURDATE() THEN 1 ELSE 0 END) as new_users_today
              FROM users";
$users_result = safeQuery($conn, $users_sql);
$stats['users'] = $users_result ? mysqli_fetch_assoc($users_result) : ['total_users' => 0, 'admins' => 0, 'clients' => 0, 'new_users_today' => 0];

// Total services
$services_sql = "SELECT 
                   COUNT(*) as total_services,
                   SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_services
                 FROM services";
$services_result = safeQuery($conn, $services_sql);
$stats['services'] = $services_result ? mysqli_fetch_assoc($services_result) : ['total_services' => 0, 'active_services' => 0];

// Service requests
$requests_sql = "SELECT 
                   COUNT(*) as total_requests,
                   SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                   SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                   SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                   SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                   SUM(CASE WHEN DATE(request_date) = CURDATE() THEN 1 ELSE 0 END) as today
                 FROM service_requests";
$requests_result = safeQuery($conn, $requests_sql);
$stats['requests'] = $requests_result ? mysqli_fetch_assoc($requests_result) : ['total_requests' => 0, 'pending' => 0, 'in_progress' => 0, 'completed' => 0, 'cancelled' => 0, 'today' => 0];

// Passport assistance
$passport_sql = "SELECT 
                   COUNT(*) as total,
                   SUM(CASE WHEN status = 'application_initiated' THEN 1 ELSE 0 END) as initiated,
                   SUM(CASE WHEN status = 'documents_verified' THEN 1 ELSE 0 END) as verified,
                   SUM(CASE WHEN status = 'appointment_scheduled' THEN 1 ELSE 0 END) as scheduled,
                   SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                 FROM passport_assistance";
$passport_result = safeQuery($conn, $passport_sql);
$stats['passport'] = $passport_result ? mysqli_fetch_assoc($passport_result) : ['total' => 0, 'initiated' => 0, 'verified' => 0, 'scheduled' => 0, 'completed' => 0];

// Revenue statistics
$revenue_sql = "SELECT 
                  COALESCE(SUM(budget), 0) as total_revenue,
                  COALESCE(SUM(CASE WHEN MONTH(request_date) = MONTH(CURDATE()) THEN budget ELSE 0 END), 0) as monthly_revenue,
                  COALESCE(SUM(CASE WHEN DATE(request_date) = CURDATE() THEN budget ELSE 0 END), 0) as today_revenue
                FROM service_requests 
                WHERE status = 'completed'";
$revenue_result = safeQuery($conn, $revenue_sql);
$stats['revenue'] = $revenue_result ? mysqli_fetch_assoc($revenue_result) : ['total_revenue' => 0, 'monthly_revenue' => 0, 'today_revenue' => 0];

// Recent activities - with error handling for missing table
$recent_activities = [];
$table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'user_activity_log'");
if (mysqli_num_rows($table_exists) > 0) {
    $activities_sql = "SELECT 
                         ul.*,
                         u.full_name,
                         u.email
                       FROM user_activity_log ul
                       JOIN users u ON ul.user_id = u.user_id
                       ORDER BY ul.created_at DESC
                       LIMIT 10";
    $activities_result = safeQuery($conn, $activities_sql);
    if ($activities_result) {
        $recent_activities = mysqli_fetch_all($activities_result, MYSQLI_ASSOC);
    }
}

// Recent service requests
$recent_requests_sql = "SELECT 
                          sr.*,
                          u.full_name as user_name,
                          u.email as user_email,
                          s.service_name
                        FROM service_requests sr
                        JOIN users u ON sr.user_id = u.user_id
                        JOIN services s ON sr.service_id = s.service_id
                        ORDER BY sr.request_date DESC
                        LIMIT 5";
$recent_requests_result = safeQuery($conn, $recent_requests_sql);
$recent_requests = $recent_requests_result ? mysqli_fetch_all($recent_requests_result, MYSQLI_ASSOC) : [];

// Monthly statistics for chart
$monthly_stats = [];
$monthly_stats_sql = "SELECT 
                        DATE_FORMAT(request_date, '%Y-%m') as month,
                        COUNT(*) as total_requests,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(budget) as revenue
                      FROM service_requests
                      WHERE request_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                      GROUP BY DATE_FORMAT(request_date, '%Y-%m')
                      ORDER BY month DESC";
$monthly_stats_result = safeQuery($conn, $monthly_stats_sql);
if ($monthly_stats_result) {
    $monthly_stats = mysqli_fetch_all($monthly_stats_result, MYSQLI_ASSOC);
}

// If no monthly stats, create sample data for display
if (empty($monthly_stats)) {
    $monthly_stats = [];
    for ($i = 5; $i >= 0; $i--) {
        $date = date('Y-m', strtotime("-$i months"));
        $monthly_stats[] = [
            'month' => $date,
            'total_requests' => 0,
            'completed' => 0,
            'revenue' => 0
        ];
    }
}
?>

<!-- Admin Header -->
<div class="admin-header bg-primary text-white py-3 mb-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h2>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="me-3">Welcome, <?php echo $_SESSION['user_name']; ?></span>
                <span class="badge bg-light text-dark"><?php echo date('d M Y'); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Admin Content -->
<div class="container-fluid px-4">
    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <!-- Users Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Total Users</h6>
                            <h2 class="mb-0"><?php echo $stats['users']['total_users'] ?? 0; ?></h2>
                            <small class="text-white-50"><?php echo $stats['users']['new_users_today'] ?? 0; ?> new today</small>
                        </div>
                        <i class="fas fa-users fa-3x text-white-50"></i>
                    </div>
                    <div class="mt-3 small">
                        <span class="me-2">Admins: <?php echo $stats['users']['admins'] ?? 0; ?></span>
                        <span>Clients: <?php echo $stats['users']['clients'] ?? 0; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Service Requests Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Service Requests</h6>
                            <h2 class="mb-0"><?php echo $stats['requests']['total_requests'] ?? 0; ?></h2>
                            <small class="text-white-50"><?php echo $stats['requests']['today'] ?? 0; ?> today</small>
                        </div>
                        <i class="fas fa-tasks fa-3x text-white-50"></i>
                    </div>
                    <div class="mt-3 small">
                        <span class="me-2">Pending: <?php echo $stats['requests']['pending'] ?? 0; ?></span>
                        <span class="me-2">In Progress: <?php echo $stats['requests']['in_progress'] ?? 0; ?></span>
                        <span>Completed: <?php echo $stats['requests']['completed'] ?? 0; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Revenue Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-dark-50 mb-2">Total Revenue</h6>
                            <h2 class="mb-0">$<?php echo number_format($stats['revenue']['total_revenue'] ?? 0, 2); ?></h2>
                            <small class="text-dark-50">$<?php echo number_format($stats['revenue']['today_revenue'] ?? 0, 2); ?> today</small>
                        </div>
                        <i class="fas fa-dollar-sign fa-3x text-dark-50"></i>
                    </div>
                    <div class="mt-3 small">
                        Monthly: $<?php echo number_format($stats['revenue']['monthly_revenue'] ?? 0, 2); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Passport Assistance Card -->
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Passport Applications</h6>
                            <h2 class="mb-0"><?php echo $stats['passport']['total'] ?? 0; ?></h2>
                        </div>
                        <i class="fas fa-passport fa-3x text-white-50"></i>
                    </div>
                    <div class="mt-3 small">
                        <span class="me-2">Initiated: <?php echo $stats['passport']['initiated'] ?? 0; ?></span>
                        <span class="me-2">Verified: <?php echo $stats['passport']['verified'] ?? 0; ?></span>
                        <span>Completed: <?php echo $stats['passport']['completed'] ?? 0; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts and Tables Row -->
    <div class="row mb-4">
        <!-- Monthly Statistics Chart -->
        <div class="col-xl-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2 text-primary"></i>Monthly Statistics</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="col-xl-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2 text-primary"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="manage-services.php?action=add" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add New Service
                        </a>
                        <a href="manage-requests.php?status=pending" class="btn btn-warning">
                            <i class="fas fa-clock me-2"></i>View Pending Requests
                            <?php if (($stats['requests']['pending'] ?? 0) > 0): ?>
                                <span class="badge bg-light text-dark ms-2"><?php echo $stats['requests']['pending']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="reports.php" class="btn btn-info">
                            <i class="fas fa-file-pdf me-2"></i>Generate Reports
                        </a>
                        <a href="users.php" class="btn btn-success">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </a>

                        <li class="nav-item">
                            <a class="nav-link <?php echo $page == 'contact-messages' ? 'active' : ''; ?>" href="?page=contact-messages">
                                <i class="fas fa-envelope"></i> 
                                Contact Messages
                                <?php
                                // Get unread count
                                $unread_sql = "SELECT COUNT(*) as unread FROM contact_messages WHERE is_read = 0";
                                $unread_result = mysqli_query($conn, $unread_sql);
                                if ($unread_result) {
                                    $unread_data = mysqli_fetch_assoc($unread_result);
                                    if ($unread_data['unread'] > 0) {
                                        echo '<span class="badge bg-danger ms-2">' . $unread_data['unread'] . '</span>';
                                    }
                                }
                                ?>
                            </a>
                        </li>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dynamic Content -->
                            <?php
                            // Include the appropriate page based on the parameter
                            $page_file = __DIR__ . '/' . $page . '.php';

                            if (file_exists($page_file)) {
                                include $page_file;
                            } else {
                                echo '<div class="alert alert-danger">Page not found: ' . htmlspecialchars($page) . '</div>';
                                echo '<p>The requested file: ' . htmlspecialchars($page_file) . '</p>';
                            }
                            ?>
                                
    <!-- Recent Activity and Requests Row -->
    <div class="row">
        <!-- Recent Service Requests -->
        <div class="col-xl-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clipboard-list me-2 text-primary"></i>Recent Service Requests</h5>
                    <a href="manage-requests.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Service</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_requests)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">No service requests found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_requests as $request): ?>
                                    <tr>
                                        <td>#<?php echo $request['request_id']; ?></td>
                                        <td>
                                            <?php echo $request['user_name']; ?>
                                            <br><small class="text-muted"><?php echo $request['user_email']; ?></small>
                                        </td>
                                        <td><?php echo $request['service_name']; ?></td>
                                        <td>
                                            <?php
                                            $status_class = [
                                                'pending' => 'warning',
                                                'confirmed' => 'info',
                                                'in_progress' => 'primary',
                                                'completed' => 'success',
                                                'cancelled' => 'danger'
                                            ];
                                            $class = $status_class[$request['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $class; ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($request['request_date'])); ?></td>
                                        <td>
                                            <a href="manage-requests.php?view=<?php echo $request['request_id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activities -->
        <div class="col-xl-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="fas fa-history me-2 text-primary"></i>Recent Activities</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (empty($recent_activities)): ?>
                            <div class="list-group-item text-center py-4">
                                <i class="fas fa-info-circle me-2"></i>
                                No recent activities found
                                <?php if (!mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'user_activity_log'"))) { ?>
                                    <br><small class="text-muted">The activity log table is not yet created. It will be created automatically when users perform actions.</small>
                                <?php } ?>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recent_activities as $activity): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-circle text-<?php 
                                            echo $activity['action'] == 'login' ? 'success' : 
                                                ($activity['action'] == 'logout' ? 'secondary' : 
                                                ($activity['action'] == 'register' ? 'info' : 'primary')); 
                                        ?> me-2 small"></i>
                                        <strong><?php echo $activity['full_name']; ?></strong>
                                        <span class="text-muted mx-2">-</span>
                                        <span><?php echo ucfirst($activity['action']); ?></span>
                                        <?php if ($activity['details']): ?>
                                            <br><small class="text-muted ms-4"><?php echo $activity['details']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted"><?php echo function_exists('timeAgo') ? timeAgo($activity['created_at']) : date('d M Y H:i', strtotime($activity['created_at'])); ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Monthly Statistics Chart
const ctx = document.getElementById('monthlyChart').getContext('2d');
const monthlyData = <?php echo json_encode(array_reverse($monthly_stats)); ?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels: monthlyData.map(item => item.month),
        datasets: [
            {
                label: 'Total Requests',
                data: monthlyData.map(item => item.total_requests),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            },
            {
                label: 'Completed',
                data: monthlyData.map(item => item.completed),
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.1
            },
            {
                label: 'Revenue ($)',
                data: monthlyData.map(item => item.revenue),
                borderColor: 'rgb(255, 159, 64)',
                backgroundColor: 'rgba(255, 159, 64, 0.2)',
                tension: 0.1,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: {
                position: 'top',
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Number of Requests'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Revenue ($)'
                },
                grid: {
                    drawOnChartArea: false
                }
            }
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>