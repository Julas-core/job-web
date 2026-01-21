<?php
session_start();
include "db_conection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role_type']; // 'seeker' or 'employer'
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirmPassword'];

    // Basic validation
    if ($password !== $confirm_password) {
        die("Passwords do not match.");
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // 1. Insert into users table
    // Check if email exists first
    $check = $conect->prepare("SELECT user_id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    
    if ($check->num_rows > 0) {
        die("Email already registered.");
    }
    $check->close();

    // Insert User
    $stmt = $conect->prepare("INSERT INTO users (email, password_hash, roles) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $password_hash, $role);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id; // Get the ID of the new user
        $stmt->close();

        // 2. Insert into specific profile table based on role
        if ($role === 'job_seeker') {
            $fullname = $_POST['fullName'];
            $title = $_POST['title'];
            $skill_level = $_POST['skillLevel'];
            $city = $_POST['city'];
            $interest = $_POST['interest'];
            $bio = $_POST['bio'];

            $seeker_stmt = $conect->prepare("INSERT INTO job_seeker (user_id, fullname, profession_title, skill_level, city, primary_interest, bio) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $seeker_stmt->bind_param("issssss", $user_id, $fullname, $title, $skill_level, $city, $interest, $bio);
            $roleinst =  $conect->prepare("INSERT INTO users (roles) VALUES (?)");
            $roleinst->bind_param("s", $role);

            if ($seeker_stmt->execute()) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['role'] = 'seeker';
                header("Location: ../Users/seeker_dashboard.php");
                exit();
            } else {
                echo "Error creating seeker profile: " . $seeker_stmt->error;
            }
            $seeker_stmt->close();

        } elseif ($role === 'company') {
            $company_name = $_POST['company'];
            $contact_name = $_POST['contactName'];
            $job_role = $_POST['role']; // HR Manager etc
            $company_size = $_POST['companySize'];
            $rep = isset($_POST['rep']) ? $_POST['rep'] : 'Representative';
            $location = $_POST['country'];

            $emp_stmt = $conect->prepare("INSERT INTO company (user_id, company_name, contact_name, description, representative, location) VALUES (?, ?, ?, ?, ?, ?)");
            // Mapping 'role' to description for now, or you can add a column
            $emp_stmt->bind_param("isssss", $user_id, $company_name, $contact_name, $job_role, $rep, $location);
            $roleinst =  $conect->prepare("INSERT INTO users (roles) VALUES (?)");
            $roleinst->bind_param("s", $role);
            
            if ($emp_stmt->execute()) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['role'] = 'employer';
                header("Location: ../Users/post_job.html");
                exit();
            } else {
                echo "Error creating company profile: " . $emp_stmt->error;
            }
            $emp_stmt->close();
        }

    } else {
        echo "Error registering user: " . $stmt->error;
    }
}
$conect->close();
?>