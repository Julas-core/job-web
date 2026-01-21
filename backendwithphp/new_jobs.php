<?php
session_start();
include "db_conection.php";

// Ensure 'jobs' table exists and has expected columns (create/alter as needed)
$create_jobs_sql = "CREATE TABLE IF NOT EXISTS jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT,
    title VARCHAR(255),
    description TEXT,
    location VARCHAR(255),
    type VARCHAR(100),
    requirements TEXT,
    salary VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB";
$conect->query($create_jobs_sql);

// Add any missing columns (safe no-op if already present)
$expected_cols = [
    'company_id' => 'INT',
    'title' => 'VARCHAR(255)',
    'description' => 'TEXT',
    'location' => 'VARCHAR(255)',
    'type' => 'VARCHAR(100)',
    'requirements' => 'TEXT',
    'salary' => 'VARCHAR(255)'
];
$res = $conect->query("DESCRIBE jobs");
$existing_cols = [];
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $existing_cols[] = $r['Field'];
    }
    foreach ($expected_cols as $col => $def) {
        if (!in_array($col, $existing_cols)) {
            $conect->query("ALTER TABLE jobs ADD COLUMN $col $def");
        }
    }
}

// Check if user is logged in and is an employer
// Handling both 'company' (from login) and 'employer' (from register) for robustness
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'company' && $_SESSION['role'] !== 'employer')) {
    echo "<script>alert('Access denied. Please login as an employer.'); window.location.href='../login.html';</script>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    
    // 1. Get company_id from company table
    // We assume the company table has a 'company_id' primary key and 'user_id' foreign key
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

    // 3. Insert into jobs table
    // We assume the 'jobs' table exists with these columns
    // ENSURING 'location' is singular as per standard convention and other tables
    $insert_stmt = $conect->prepare("INSERT INTO jobs (company_id, title, description, location, type, requirements, salary) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $insert_stmt->bind_param("issssss", $company_id, $title, $description, $location, $type, $requirements, $salary);

    if ($insert_stmt->execute()) {
        // Redirect to the "Total Job Posted" page (my_posting.php)
        echo "<script>alert('Job posted successfully!'); window.location.href='../Users/my_posting.php';</script>";
    } else {
        echo "Error posting job: " . $insert_stmt->error;
    }
    $insert_stmt->close();
} else {
    // If accessed directly without POST, redirect back
    header("Location: ../Users/post_job.html");
    exit();
}
?>