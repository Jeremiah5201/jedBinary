<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Require admin access
requireAdmin();

$page_title = 'Reports';
include '../includes/header.php';

// Get date range
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$report_type = isset($_GET['type']) ? $_GET['type'] : 'summary';

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] == 'pdf') {
    exportToPDF($date_from, $date_to, $report_type);
    exit();
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    exportToCSV($date_from, $date_to, $report_type);
    exit();
}

// Get summary statistics
$summary = [];

// Total revenue
$revenue_sql = "SELECT 
                  COALESCE(SUM(budget), 0) as total_revenue,
                  COUNT(*) as total_requests,
                  COALESCE(AVG(budget), 0) as avg_budget
                FROM service_requests 
                WHERE DATE(request_date) BETWEEN '$date_from' AND '$date_to'
                AND status = 'completed'";
$revenue_result = mysqli_query($conn, $revenue_sql);
$summary['revenue'] = mysqli_fetch_assoc($revenue_result);

// Requests by status
$status_sql = "SELECT 
                 status,
                 COUNT(*) as count,
                 SUM(budget) as revenue
               FROM service_requests 
               WHERE DATE(request_date) BETWEEN '$date_from' AND '$date_to'
               GROUP BY status";
$status_result = mysqli_query($conn, $status_sql);
$summary['by_status'] = mysqli_fetch_all($status_result, MYSQLI_ASSOC);

// Requests by service
$service_sql = "SELECT 
                  s.service_name,
                  COUNT(*) as request_count,
                  SUM(sr.budget) as revenue,
                  AVG(sr.budget) as avg_budget
                FROM service_requests sr
                JOIN services s ON sr.service_id = s.service_id
                WHERE DATE(sr.request_date) BETWEEN '$date_from' AND '$date_to'
                GROUP BY sr.service_id, s.service_name
                ORDER BY request_count DESC";
$service_result = mysqli_query($conn, $service_sql);
$summary['by_service'] = mysqli_fetch_all($service_result, MYSQLI_ASSOC);

// Daily statistics
$daily_sql = "SELECT 
                DATE(request_date) as date,
                COUNT(*) as requests,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                COALESCE(SUM(budget), 0) as revenue
              FROM service_requests 
              WHERE DATE(request_date) BETWEEN '$date_from' AND '$date_to'
              GROUP BY DATE(request_date)
              ORDER BY date DESC";
$daily_result = mysqli_query($conn, $daily_sql);
$summary['daily'] = mysqli_fetch_all($daily_result, MYSQLI_ASSOC);

// New users registered
$users_sql = "SELECT 
                COUNT(*) as new_users,
                SUM(CASE WHEN user_type = 'client' THEN 1 ELSE 0 END) as new_clients,
                SUM(CASE WHEN user_type = 'admin' THEN 1 ELSE 0 END) as new_admins
              FROM users 
              WHERE DATE(registration_date) BETWEEN '$date_from' AND '$date_to'";
$users_result = mysqli_query($conn, $users_sql);
$summary['users'] = mysqli_fetch_assoc($users_result);

// Passport applications
$passport_sql = "SELECT 
                   COUNT(*) as total_applications,
                   SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                   SUM(CASE WHEN passport_type = 'new' THEN 1 ELSE 0 END) as new_passports,
                   SUM(CASE WHEN passport_type = 'renewal' THEN 1 ELSE 0 END) as renewals
                 FROM passport_assistance 
                 WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'";
$passport_result = mysqli_query($conn, $passport_sql);
$summary['passport'] = mysqli_fetch_assoc($passport_result);

// Top clients
$clients_sql = "SELECT 
                  u.full_name,
                  u.email,
                  COUNT(sr.request_id) as total_requests,
                  SUM(sr.budget) as total_spent,
                  MAX(sr.request_date) as last_request
                FROM users u
                JOIN service_requests sr ON u.user_id = sr.user_id
                WHERE DATE(sr.request_date) BETWEEN '$date_from' AND '$date_to'
                GROUP BY u.user_id, u.full_name, u.email
                ORDER BY total_spent DESC
                LIMIT 10";
$clients_result = mysqli_query($conn, $clients_sql);
$summary['top_clients'] = mysqli_fetch_all($clients_result, MYSQLI_ASSOC);
?>

<!-- Admin Header -->
<div class="admin-header bg-primary text-white py-3 mb-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</h2>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="btn-group">
                    <button type="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-download me-2"></i>Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="?<?php echo $_SERVER['QUERY_STRING']; ?>&export=pdf">
                            <i class="fas fa-file-pdf text-danger me-2"></i>Export as PDF
                        </a></li>
                        <li><a class="dropdown-item" href="?<?php echo $_SERVER['QUERY_STRING']; ?>&export=csv">
                            <i class="fas fa-file-csv text-success me-2"></i>Export as CSV
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="window.print(); return false;">
                            <i class="fas fa-print text-primary me-2"></i>Print Report
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-4">
    <!-- Date Range Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Report Type</label>
                    <select name="type" class="form-select">
                        <option value="summary" <?php echo $report_type == 'summary' ? 'selected' : ''; ?>>Summary Report</option>
                        <option value="detailed" <?php echo $report_type == 'detailed' ? 'selected' : ''; ?>>Detailed Report</option>
                        <option value="financial" <?php echo $report_type == 'financial' ? 'selected' : ''; ?>>Financial Report</option>
                        <option value="clients" <?php echo $report_type == 'clients' ? 'selected' : ''; ?>>Client Report</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50">Total Revenue</h6>
                            <h3>$<?php echo number_format($summary['revenue']['total_revenue'], 2); ?></h3>
                            <small>Period: <?php echo date('d M Y', strtotime($date_from)); ?> - <?php echo date('d M Y', strtotime($date_to)); ?></small>
                        </div>
                        <i class="fas fa-dollar-sign fa-3x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50">Total Requests</h6>
                            <h3><?php echo $summary['revenue']['total_requests']; ?></h3>
                            <small>Avg. Budget: $<?php echo number_format($summary['revenue']['avg_budget'], 2); ?></small>
                        </div>
                        <i class="fas fa-clipboard-list fa-3x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-dark-50">New Users</h6>
                            <h3><?php echo $summary['users']['new_users'] ?? 0; ?></h3>
                            <small>Clients: <?php echo $summary['users']['new_clients'] ?? 0; ?></small>
                        </div>
                        <i class="fas fa-users fa-3x text-dark-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50">Passport Apps</h6>
                            <h3><?php echo $summary['passport']['total_applications'] ?? 0; ?></h3>
                            <small>Completed: <?php echo $summary['passport']['completed'] ?? 0; ?></small>
                        </div>
                        <i class="fas fa-passport fa-3x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-xl-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2 text-primary"></i>Requests by Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-xl-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Requests by Service</h5>
                </div>
                <div class="card-body">
                    <canvas id="serviceChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Daily Statistics Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0"><i class="fas fa-calendar-alt me-2 text-primary"></i>Daily Statistics</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Total Requests</th>
                            <th>Completed</th>
                            <th>Pending</th>
                            <th>In Progress</th>
                            <th>Cancelled</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summary['daily'] as $day): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($day['date'])); ?></td>
                            <td><?php echo $day['requests']; ?></td>
                            <td><?php echo $day['completed']; ?></td>
                            <td>
                                <?php
                                $pending = $day['requests'] - $day['completed'];
                                echo $pending;
                                ?>
                            </td>
                            <td>-</td>
                            <td>-</td>
                            <td>$<?php echo number_format($day['revenue'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Service Breakdown -->
    <div class="row">
        <div class="col-xl-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="fas fa-cogs me-2 text-primary"></i>Service Performance</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Service</th>
                                    <th>Requests</th>
                                    <th>Revenue</th>
                                    <th>Avg. Budget</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($summary['by_service'] as $service): ?>
                                <tr>
                                    <td><?php echo $service['service_name']; ?></td>
                                    <td><?php echo $service['request_count']; ?></td>
                                    <td>$<?php echo number_format($service['revenue'], 2); ?></td>
                                    <td>$<?php echo number_format($service['avg_budget'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Clients -->
        <div class="col-xl-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="fas fa-trophy me-2 text-primary"></i>Top Clients</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th>Requests</th>
                                    <th>Total Spent</th>
                                    <th>Last Request</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($summary['top_clients'] as $client): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $client['full_name']; ?></strong>
                                        <br><small class="text-muted"><?php echo $client['email']; ?></small>
                                    </td>
                                    <td><?php echo $client['total_requests']; ?></td>
                                    <td>$<?php echo number_format($client['total_spent'], 2); ?></td>
                                    <td><?php echo date('d M Y', strtotime($client['last_request'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusData = <?php echo json_encode($summary['by_status']); ?>;

new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: statusData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1)),
        datasets: [{
            data: statusData.map(item => item.count),
            backgroundColor: [
                '#ffc107', // pending
                '#17a2b8', // confirmed
                '#007bff', // in_progress
                '#28a745', // completed
                '#dc3545'  // cancelled
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Service Chart
const serviceCtx = document.getElementById('serviceChart').getContext('2d');
const serviceData = <?php echo json_encode($summary['by_service']); ?>;

new Chart(serviceCtx, {
    type: 'bar',
    data: {
        labels: serviceData.map(item => item.service_name),
        datasets: [
            {
                label: 'Number of Requests',
                data: serviceData.map(item => item.request_count),
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                yAxisID: 'y'
            },
            {
                label: 'Revenue ($)',
                data: serviceData.map(item => item.revenue),
                backgroundColor: 'rgba(255, 159, 64, 0.5)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 1,
                yAxisID: 'y1',
                type: 'line'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Requests'
                }
            },
            y1: {
                position: 'right',
                beginAtZero: true,
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

<?php
// Export functions
function exportToPDF($date_from, $date_to, $report_type) {
    // This would integrate with a PDF library like TCPDF or Dompdf
    // For now, redirect with message
    $_SESSION['info_message'] = "PDF export functionality will be implemented with a PDF library";
    header("Location: reports.php?date_from=$date_from&date_to=$date_to&type=$report_type");
    exit();
}

function exportToCSV($date_from, $date_to, $report_type) {
    global $conn, $summary;
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add report header
    fputcsv($output, ['JED BINARY TECH SOLUTIONS - Report']);
    fputcsv($output, ['Period', $date_from, 'to', $date_to]);
    fputcsv($output, []);
    
    // Summary
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['Total Revenue', '$' . number_format($summary['revenue']['total_revenue'], 2)]);
    fputcsv($output, ['Total Requests', $summary['revenue']['total_requests']]);
    fputcsv($output, ['Average Budget', '$' . number_format($summary['revenue']['avg_budget'], 2)]);
    fputcsv($output, ['New Users', $summary['users']['new_users'] ?? 0]);
    fputcsv($output, []);
    
    // Status breakdown
    fputcsv($output, ['REQUESTS BY STATUS']);
    fputcsv($output, ['Status', 'Count', 'Revenue']);
    foreach ($summary['by_status'] as $status) {
        fputcsv($output, [
            $status['status'],
            $status['count'],
            '$' . number_format($status['revenue'] ?? 0, 2)
        ]);
    }
    fputcsv($output, []);
    
    // Service breakdown
    fputcsv($output, ['REQUESTS BY SERVICE']);
    fputcsv($output, ['Service', 'Requests', 'Revenue', 'Avg Budget']);
    foreach ($summary['by_service'] as $service) {
        fputcsv($output, [
            $service['service_name'],
            $service['request_count'],
            '$' . number_format($service['revenue'], 2),
            '$' . number_format($service['avg_budget'], 2)
        ]);
    }
    
    fclose($output);
    exit();
}

include '../includes/footer.php'; 
?>