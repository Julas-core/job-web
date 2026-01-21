<?php
session_start();
include "db_conection.php";

// Auth Check: Ensure user is logged in as company or employer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || ($_SESSION['role'] !== 'company' && $_SESSION['role'] !== 'employer')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $application_id = isset($data['application_id']) ? intval($data['application_id']) : 0;
    $new_status = isset($data['status']) ? $data['status'] : '';

    if ($application_id <= 0 || empty($new_status)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit();
    }

    // Allowed statuses
    $allowed_statuses = ['pending', 'accepted', 'rejected'];
    $status_lower = strtolower($new_status);
    
    if (!in_array($status_lower, $allowed_statuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }

    // Verify application belongs to a job posted by this company
    // First get company_id
    $user_id = $_SESSION['user_id'];
    $stmt = $conect->prepare("SELECT company_id FROM company WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $company = $res->fetch_assoc();
    $company_id = $company['company_id'];
    $stmt->close();

    // Check ownership
    $check_sql = "
        SELECT a.app_id 
        FROM applications a 
        JOIN jobs j ON a.job_id = j.job_id 
        WHERE a.app_id = ? AND j.company_id = ?
    ";
    
    // Note: application primary key is 'app_id'
    
    $stmt = $conect->prepare($check_sql);
    $stmt->bind_param("ii", $application_id, $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Permission denied or application not found']);
        exit();
    }
    $stmt->close();

    // Update status
    $update_sql = "UPDATE applications SET job_status = ? WHERE app_id = ?";
    $stmt = $conect->prepare($update_sql);
    $stmt->bind_param("si", $status_lower, $application_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    $stmt->close();
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>