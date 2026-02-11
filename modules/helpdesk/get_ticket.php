<?php
require_once '../../includes/auth.php';
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No ID provided']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT t.*, 
        u.fullname as requester_name, 
        d.name as dept_name,
        c.name as cat_name, 
        tech.fullname as tech_name,
        a.name as asset_name_db, 
        l.name as location_name
        FROM tickets t 
        LEFT JOIN users u ON t.user_id = u.id 
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN users tech ON t.assigned_to = tech.id
        LEFT JOIN assets a ON t.asset_code = a.asset_code
        LEFT JOIN locations l ON a.location_id = l.id
        WHERE t.id = ?");
    
    $stmt->execute([$_GET['id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode(['status' => 'success', 'data' => $row]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}