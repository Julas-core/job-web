<?php
session_start();
include "db_conection.php";

// Check if user is logged in as employer/company
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'company' && $_SESSION['role'] !== 'employer')) {
    echo "<script>alert('Access denied. Please login as an employer.'); window.location.href='../login.html';</script>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['job_id'])) {
        die("Error: Job ID is missing.");
    }

    $user_id = $_SESSION['user_id'];
    $job_id = intval($_POST['job_id']);

    // 1. Get company_id (and verify ownership implicitly by using it in specific UPDATE query if needed, 
    //    but safer to get company_id first to ensure the job belongs to THIS company)
    $stmt = $conect->prepare("SELECT company_id FROM company WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die("Error: Company profile not found for this user.");
    }
    
    $row = $result->fetch_assoc();
    $company_id = $row['company_id'];
    $stmt->close();

    // 2. Get form data
    $title = $_POST['title'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $type = $_POST['type'];
    $requirements = $_POST['requirements'];
    $salary = $_POST['salary'];

    // 3. Update job
    // We update ONLY if the job belongs to this company_id
    $update_stmt = $conect->prepare("UPDATE jobs SET title=?, description=?, location=?, type=?, requirements=?, salary=? WHERE job_id=? AND company_id=?");
    $update_stmt->bind_param("ssssssii", $title, $description, $location, $type, $requirements, $salary, $job_id, $company_id);

    if ($update_stmt->execute()) {
        if ($update_stmt->affected_rows >= 0) { // >= 0 because maybe nothing changed but query was success
            echo "<script>alert('Job updated successfully!'); window.location.href='../Users/my_posting.php';</script>";
        } else {
             // Should not happen if query succeeded, unless logic error (job doesn't belong to company)
             // But execute() returns true unless SQL error. 
             // If affected_rows is 0, it means either no change or row not found.
             // We can check if it was found.
             echo "<script>alert('Update successful (or no changes made).'); window.location.href='../Users/my_posting.php';</script>";
        }
    } else {
        echo "Error updating job: " . $update_stmt->error;
    }
    $update_stmt->close();
} else {
    header("Location: ../Users/my_posting.php");
    exit();
}
?>