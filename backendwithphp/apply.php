<?php
session_start();
include "db_conection.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

$job_id = intval($_POST['job_id'] ?? 0);
$fullname = trim($_POST['fullname'] ?? '');
$skill_level = trim($_POST['skill_level'] ?? '');
$salary = trim($_POST['salary'] ?? '');
$cover = trim($_POST['cover'] ?? '');
$telegram = trim($_POST['telegram'] ?? '');
$portfolio = trim($_POST['portfolio'] ?? '');
// Accept resume from file upload OR a resume URL/text field
// Accept resume URL (resume_url) or file (resume_file)
$resume_input = isset($_POST['resume_url']) ? trim($_POST['resume_url']) : null;
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// Better validation with explicit missing fields
$missing = [];
if ($job_id <= 0) $missing[] = 'job';
if (empty($fullname)) $missing[] = 'name';
if (empty($skill_level)) $missing[] = 'skill level';
if (empty($salary)) $missing[] = 'expected salary';
if (empty($cover)) $missing[] = 'cover letter';

if (!empty($missing)) {
    echo "<script>alert('Please fill required fields: " . implode(', ', $missing) . "'); window.history.back();</script>";
    exit();
}

// Handle resume upload or accept resume URL/text
$upload_dir = __DIR__ . '/../uploads/resumes';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$resume_path = null;
// Check for uploaded file first (resume_file), then resume_url
if (isset($_FILES['resume_file']) && isset($_FILES['resume_file']['tmp_name']) && $_FILES['resume_file']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['resume_file']['tmp_name'];
    $name = basename($_FILES['resume_file']['name']);
    $ext = pathinfo($name, PATHINFO_EXTENSION);
    $allowed = ['pdf','doc','docx','txt'];
    if (!in_array(strtolower($ext), $allowed)) {
        echo "<script>alert('Invalid resume type. Allowed: pdf, doc, docx, txt'); window.history.back();</script>";
        exit();
    }
    $newname = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $target = $upload_dir . '/' . $newname;
    if (move_uploaded_file($tmp, $target)) {
        // Store a relative path so it works in web pages
        $resume_path = 'uploads/resumes/' . $newname;
    } else {
        echo "<script>alert('Failed to upload resume.'); window.history.back();</script>";
        exit();
    }
} elseif (!empty($resume_input)) {
    // Accept a URL or a text path
    $resume_path = $resume_input;
} else {
    echo "<script>alert('Please provide a resume (upload a file or paste a resume URL).'); window.history.back();</script>";
    exit();
} 

// Ensure applications table exists (attempt to create a modern schema if not present)
$create_app_sql = "CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT NULL,
    seeker_id INT NULL,
    fullname VARCHAR(255),
    skill_level VARCHAR(100),
    expected_salary VARCHAR(100),
    resumee VARCHAR(500),
    cover TEXT,
    telegram VARCHAR(255),
    portfolio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB";
$conect->query($create_app_sql);

// Inspect existing applications table columns to decide which FK column to use
$cols_res = $conect->query("DESCRIBE applications");
$app_cols = [];
$app_cols_info = [];
if ($cols_res) {
    while ($r = $cols_res->fetch_assoc()) {
        $app_cols[] = $r['Field'];
        $app_cols_info[$r['Field']] = $r; // keep full metadata (Null, Type, etc.)
    }
}

// If the table uses seeker_id (legacy), resolve the seeker's id for this user (may be null)
$seeker_id = null;
if (in_array('seeker_id', $app_cols)) {
    if ($user_id > 0) {
        $sstmt = $conect->prepare("SELECT seeker_id FROM job_seeker WHERE user_id = ? LIMIT 1");
        if ($sstmt) {
            $sstmt->bind_param('i', $user_id);
            $sstmt->execute();
            $sres = $sstmt->get_result();
            if ($sres && $sres->num_rows > 0) {
                $srow = $sres->fetch_assoc();
                $seeker_id = intval($srow['seeker_id']);
            }
            $sstmt->close();
        }
    }
}

// Build insert dynamically depending on available columns
if (in_array('seeker_id', $app_cols)) {
    // If seeker_id exists and we have a matching seeker record, use it
    if ($seeker_id !== null) {
        $insert_sql = "INSERT INTO applications (job_id, seeker_id, fullname, skill_level, expected_salary, resumee, cover_letter, telegram, portfolio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conect->prepare($insert_sql);
        if (!$stmt) {
            echo "Error preparing statement: " . $conect->error;
            exit();
        }
        $stmt->bind_param("iisssssss", $job_id, $seeker_id, $fullname, $skill_level, $salary, $resume_path, $cover, $telegram, $portfolio);
    } else {
        // No seeker record found for this user
        // If seeker_id column is NOT NULL and 'user_id' column exists, fall back to using user_id
        $seeker_notnull = (isset($app_cols_info['seeker_id']) && ($app_cols_info['seeker_id']['Null'] === 'NO'));
        if ($seeker_notnull && in_array('user_id', $app_cols)) {
            // Insert using user_id instead
            $insert_sql = "INSERT INTO applications (job_id, seeker_id, fullname, skill_level, expected_salary, resumee, cover_letter, telegram, portfolio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conect->prepare($insert_sql);
            if (!$stmt) {
                echo "Error preparing statement: " . $conect->error;
                exit();
            }
            $stmt->bind_param("iisssssss", $job_id, $user_id, $fullname, $skill_level, $salary, $resume_path, $cover, $telegram, $portfolio);
        } else {
            // Insert with NULL seeker_id to avoid FK constraint; use explicit NULL in query
            $insert_sql = "INSERT INTO applications (job_id, seeker_id, fullname, skill_level, expected_salary, resumee, cover_letter, telegram, portfolio) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conect->prepare($insert_sql);
            if (!$stmt) {
                echo "Error preparing statement: " . $conect->error;
                exit();
            }
            // bind without seeker_id parameter (8 params total)
            $stmt->bind_param("isssssss", $job_id, $fullname, $skill_level, $salary, $resume_path, $cover, $telegram, $portfolio);
        }
    }
} else {
    // Use user_id column (modern schema)
    $insert_sql = "INSERT INTO applications (job_id, user_id, fullname, skill_level, expected_salary, resumee, cover_letter, telegram, portfolio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conect->prepare($insert_sql);
    if (!$stmt) {
        echo "Error preparing statement: " . $conect->error;
        exit();
    }
    $user_id_for_bind = intval($user_id);
    $stmt->bind_param("iisssssss", $job_id, $user_id_for_bind, $fullname, $skill_level, $salary, $resume_path, $cover, $telegram, $portfolio);
}

if ($stmt->execute()) {
    echo "<script>alert('Application submitted successfully.'); window.location.href='../Users/my_applications.php';</script>";
} else {
    $errno = $conect->errno;
    $dberr = $conect->error;
    if ($errno === 1452 || stripos($dberr, 'foreign key') !== false) {
        // Helpful message for FK failure (missing seeker profile for this user)
        echo "<script>alert('Database constraint error: your account may not have a linked job seeker profile required to apply. Please complete your seeker profile or contact support. (DB error: " . addslashes($dberr) . ")'); window.history.back();</script>";
    } else {
        // Generic DB error
        echo "<script>alert('Error submitting application: " . addslashes($dberr) . "'); window.history.back();</script>";
    }
}
$stmt->close();
