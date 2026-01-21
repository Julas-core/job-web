<?php
session_start();
include "db_conection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = $_POST['email'];
        $pass = $_POST['password'];
        $selected_role = isset($_POST['role_type']) ? $_POST['role_type'] : ''; // Get the role from the form

        // Use mysqli_query
        $res = mysqli_query($conect, "SELECT * FROM users WHERE email='$email'");
        $user = mysqli_fetch_assoc($res);

        // Verify password (ensure column name matches your DB, e.g., 'password' or 'password_hash')
        if($user && password_verify($pass, $user['password_hash'])){ 
            
            // NEW: Check if the selected role matches the database role
            if ($selected_role && $selected_role !== $user['roles']) {
                echo "<script>alert('Error: You are trying to login as a " . ucfirst($selected_role) . ", but this email is registered as a " . ucfirst($user['roles']) . ".'); window.location.href='../login.html';</script>";
                exit();
            }

            $_SESSION['role'] = $user['roles'];
            $_SESSION['user_id'] = $user['user_id']; 
            
            // Redirect based on role
            if($user['roles'] == 'company') {
                 header("Location: ../Users/post_job.php");
            } else {
                 header("Location: ../Users/seeker_dashboard.php");
            }
            exit();
        } else {
            echo "<script>alert('Login failed! Invalid email or password.'); window.location.href='../login.html';</script>";
        }
    }
}

?>