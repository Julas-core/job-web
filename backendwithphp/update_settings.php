Julas, [1/20/2026 3:07 AM]
<?php
session_start();
include "../backendwithphp/db_conection.php";

$isLoggedIn = isset($_SESSION['user_id']);
$dashboardLink = (isset($_SESSION['role']) && ($_SESSION['role'] == 'company' || $_SESSION['role'] == 'employer')) ? 'employer_dashboard.php' : 'seeker_dashboard.php';

// Fetch Company Name
$company_name = "Company Name";
if ($isLoggedIn) {
   $user_id = $_SESSION['user_id'];
   $stmt = $conect->prepare("SELECT company_name FROM company WHERE user_id = ?");
   $stmt->bind_param("i", $user_id);
   $stmt->execute();
   $res = $stmt->get_result();
   if ($row = $res->fetch_assoc()) {
       $company_name = $row['company_name'];
   }
   $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>JobLaunch | Post Job</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="../style.css">
</head>
<body class="page-wrap">
    <?php $basePath = '..'; include __DIR__ . '/../partials/header.php'; ?>

  <section class="dashboard-section">
    <div class="dashboard-shell">
      <aside class="sidebar">
        <div class="avatar" style="background:#dcdcdc;"></div>
        <h3><?php echo htmlspecialchars($company_name); ?></h3>
        <nav>
          <a class="active" href="post_job.php">Post New Job</a>
          <a href="my_posting.php">Total Job Posted</a>
          <a href="view_applications.php">Total Applicants</a>
          <!-- <a href="my_posting.php">Manage Jobs</a> -->
          <a href="settings.php">Setting</a>
          <a href="more.php">More</a>
        </nav>
      </aside>

      <div class="dashboard-main">
        <div class="dashboard-header-actions">
          <a href="../backendwithphp/logout.php">Sign Out</a>
          <a href="../catagories.html">Job Listings</a>
        </div>

        <h1 style="color:white; margin-bottom: 5px;">Admin Dashboard</h1>
        <h2 class="section-title" style="text-align: left; margin-top: 0;">Post a New Job</h2>

        <!-- Added action, method, and name attributes -->
        <form class="company-form" action="../backendwithphp/new_jobs.php" method="POST">
          <input class="input" type="text" name="title" placeholder="Job Title / Responsibility" required>
          <textarea class="input" name="description" style="grid-column:2/-1;min-height:180px;" placeholder="Full Job Description" required></textarea>
          <input class="input" type="text" name="location" placeholder="Location" required>
          
          <!-- Changed to Select Dropdown for ENUM -->
          <select class="input" name="type" required style="color: #fff; background-color: #1f1f1f;">
            <option value="" disabled selected>Select Job Type</option>
            <option value="Full Time">Full Time</option>
            <option value="Part Time">Part Time</option>
            <option value="Contract">Contract</option>
            <option value="Freelance">Freelance</option>
            <option value="Internship">Internship</option>
          </select>

          <input class="input full" type="text" name="requirements" placeholder="Requirements">
          <input class="input full" type="text" name="salary" placeholder="Salary (Optional)">
          
          <div style="margin-top:10px; text-align:right; grid-column: 1 / -1;">
            <button class="btn" type="submit">Save Job</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</body>
</html>


<?php
session_start();
include "db_conection.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role']; 
    // Normalize role locally
    if ($role == 'employer') $role = 'company';
    if ($role == 'seeker') $role = 'job_seeker';

    $action = $_POST['action_type'];

    if ($action == 'update_profile') {
        
        if ($role == 'company') {
            $company_name = $_POST['company_name'];
            $contact_name = $_POST['contact_name'];
            $representative = $_POST['representative'];
            $location = $_POST['location'];
            $description = $_POST['description'];

            // Handle Image Upload
            $image_sql = ""; 
            $image_val = "";
            $has_image = false;

            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['profile_image']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $new_filename = uniqid('cv_', true) . "." . $ext;
                    $upload_dir = "../uploads/profiles/";
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $new_filename)) {
                        $image_val = "uploads/profiles/" . $new_filename;
                        $has_image = true;
                    }
                }
            }

            // Update Company Table
            // Check if record exists first (it should, but just in case)
            $check = $conect->prepare("SELECT user_id FROM company WHERE user_id = ?");
            $check->bind_param("i", $user_id);
            $check->execute();
            $res = $check->get_result();
            
            if ($res->num_rows > 0) {
                if ($has_image) {
                    $stmt = $conect->prepare("UPDATE company SET company_name=?, contact_name=?, representative=?, location=?, description=?, profile_image=? WHERE user_id=?");
                    $stmt->bind_param("ssssssi", $company_name, $contact_name, $representative, $location, $description, $image_val, $user_id);
                } else {
                    $stmt = $conect->prepare("UPDATE company SET company_name=?, contact_name=?, representative=?, location=?, description=? WHERE user_id=?");
                    $stmt->bind_param("sssssi", $company_name, $contact_name, $representative, $location, $description, $user_id);
                }
            } else {
                // Insert if missing
                if ($has_image) {
                    $stmt = $conect->prepare("INSERT INTO company (company_name, contact_name, representative, location, description, profile_image, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssssi", $company_name, $contact_name, $representative, $location, $description, $image_val, $user_id);
                } else {
                    $stmt = $conect->prepare("INSERT INTO company (company_name, contact_name, representative, location, description, user_id) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssi", $company_name, $contact_name, $representative, $location, $description, $user_id);
                }
            }

            if ($stmt->execute()) {
                header("Location: ../Users/settings.php?msg=Profile updated successfully");
            } else {
                header("Location: ../Users/settings.php?err=Error updating profile: " . $stmt->error);
            }


} elseif ($role == 'job_seeker') {
            $fullname = $_POST['fullname'];
            $profession_title = $_POST['profession_title'];
            $skill_level = $_POST['skill_level'];
            $city = $_POST['city'];
            $primary_interest = $_POST['primary_interest'];
            $bio = $_POST['bio'];

            // Handle Image Upload
            $image_sql = ""; 
            $image_val = "";
            $has_image = false;

            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['profile_image']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $new_filename = uniqid('cv_', true) . "." . $ext;
                    $upload_dir = "../uploads/profiles/";
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $new_filename)) {
                        $image_val = "uploads/profiles/" . $new_filename;
                        $has_image = true;
                    }
                }
            }

            $check = $conect->prepare("SELECT user_id FROM job_seeker WHERE user_id = ?");
            $check->bind_param("i", $user_id);
            $check->execute();
            $res = $check->get_result();

             if ($res->num_rows > 0) {
                if ($has_image) {
                     $stmt = $conect->prepare("UPDATE job_seeker SET fullname=?, profession_title=?, skill_level=?, city=?, primary_interest=?, bio=?, profile_image=? WHERE user_id=?");
                     $stmt->bind_param("sssssssi", $fullname, $profession_title, $skill_level, $city, $primary_interest, $bio, $image_val, $user_id);
                } else {
                     $stmt = $conect->prepare("UPDATE job_seeker SET fullname=?, profession_title=?, skill_level=?, city=?, primary_interest=?, bio=? WHERE user_id=?");
                     $stmt->bind_param("ssssssi", $fullname, $profession_title, $skill_level, $city, $primary_interest, $bio, $user_id);
                }
            } else {
                // Insert if missing
                if ($has_image) {
                    $stmt = $conect->prepare("INSERT INTO job_seeker (fullname, profession_title, skill_level, city, primary_interest, bio, profile_image, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssssi", $fullname, $profession_title, $skill_level, $city, $primary_interest, $bio, $image_val, $user_id);
                } else {
                    $stmt = $conect->prepare("INSERT INTO job_seeker (fullname, profession_title, skill_level, city, primary_interest, bio, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssssi", $fullname, $profession_title, $skill_level, $city, $primary_interest, $bio, $user_id);
                }
            }

            if ($stmt->execute()) {
                header("Location: ../Users/settings.php?msg=Profile updated successfully");
            } else {
                header("Location: ../Users/settings.php?err=Error updating profile: " . $stmt->error);
            }
        }

    } elseif ($action == 'update_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_new_password = $_POST['confirm_new_password'];

        if ($new_password !== $confirm_new_password) {
            header("Location: ../Users/settings.php?err=New passwords do not match");
            exit();
        }

        // Verify current password
        $stmt = $conect->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();


if ($user && password_verify($current_password, $user['password_hash'])) {
            // Update password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $up_stmt = $conect->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $up_stmt->bind_param("si", $new_hash, $user_id);
            
            if ($up_stmt->execute()) {
                header("Location: ../Users/settings.php?msg=Password updated successfully");
            } else {
               header("Location: ../Users/settings.php?err=Database error updating password");
            }
        } else {
            header("Location: ../Users/settings.php?err=Current password is incorrect");
        }
    }

} else {
    // If accessed directly
    header("Location: ../Users/settings.php");
}
?>