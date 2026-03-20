<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 9;
$offset = ($page - 1) * $per_page;

$sql = "SELECT * FROM portfolio ORDER BY completion_date DESC LIMIT $offset, $per_page";
$result = mysqli_query($conn, $sql);

$projects = [];
while ($row = mysqli_fetch_assoc($result)) {
    $projects[] = $row;
}

// Check if there are more projects
$count_sql = "SELECT COUNT(*) as total FROM portfolio";
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$total = $count_row['total'];

$has_more = ($offset + $per_page) < $total;

echo json_encode([
    'success' => true,
    'projects' => $projects,
    'hasMore' => $has_more,
    'page' => $page,
    'total' => $total
]);
?>