<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
    exit();
}

$project_id = intval($_GET['id']);

$sql = "SELECT * FROM portfolio WHERE project_id = $project_id";
$result = mysqli_query($conn, $sql);

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode(['success' => true, 'project' => $row]);
} else {
    echo json_encode(['success' => false, 'message' => 'Project not found']);
}
?>