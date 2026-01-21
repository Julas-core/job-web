
<?php
session_start();
include "../backendwithphp/db_conection.php";

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
// Normalize role from session (handle potential inconsistencies)
$role = $_SESSION['role']; 
// Standardize for local logic
if ($role == 'employer') $role = 'company';
if ($role == 'seeker') $role = 'job_seeker';


// Fetch User Basic Info (Email)
$stmt = $conect->prepare("SELECT email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user_row = $res->fetch_assoc();
$current_email = $user_row ? $user_row['email'] : '';
$stmt->close();

// Fetch Profile Info
$profile_data = [];
$success_msg = isset($_GET['msg']) ? $_GET['msg'] : '';
$error_msg = isset($_GET['err']) ? $_GET['err'] : '';

if ($role == 'company') {
    // Check if profile exists
    $stmt = $conect->prepare("SELECT * FROM company WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $p_res = $stmt->get_result();
    if ($p_res->num_rows > 0) {
        $profile_data = $p_res->fetch_assoc();
    } else {
        // Initialize empty if not found (shouldn't happen if registered correctly)
        $profile_data = ['company_name'=>'', 'contact_name'=>'', 'description'=>'', 'location'=>'', 'representative'=>''];
    }
    $stmt->close();
} elseif ($role == 'job_seeker') {
    $stmt = $conect->prepare("SELECT * FROM job_seeker WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $p_res = $stmt->get_result();
    if ($p_res->num_rows > 0) {
        $profile_data = $p_res->fetch_assoc();
    } else {
         $profile_data = ['fullname'=>'', 'profession_title'=>'', 'skill_level'=>'', 'city'=>'', 'primary_interest'=>'', 'bio'=>''];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings - JobLaunch</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="../style.css">
</head>
<body class="page-wrap">
  <header class="navbar">
        <div class="brand">JobLaunch</div>
        <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false"><span></span></button>
        <div class="nav-drawer">
            <div class="auth-links">
                <a href="<?php echo ($role == 'company') ? 'employer_dashboard.html' : 'seeker_dashboard.html'; ?>">Dashboard</a>
                <span class="divider">|</span>
                <a href="../backendwithphp/logout.php">Logout</a>
            </div>
            <div class="nav-links">
                <a href="../index.php">Home</a>
                <a href="../catagories.html">Categories</a>
                <a href="../about.html">AboutUs</a>
                <a href="../contact.html">ContactUs</a>
            </div>
        </div>
    </header>


<section class="dashboard-section" style="padding:0; max-width:100%; margin:0;">
    <div class="dashboard-shell">
      <aside class="sidebar">
        <!-- Dynamic Sidebar based on Role -->
        <?php if(!empty($profile_data['profile_image'])): ?>
            <div class="avatar" style="background-image: url('../<?php echo htmlspecialchars($profile_data['profile_image']); ?>'); background-size: cover; background-position: center;"></div>
        <?php else: ?>
            <div class="avatar" style="background:#dcdcdc;"></div>
        <?php endif; ?>
        
        <?php if ($role == 'company'): ?>
            <h3><?php echo htmlspecialchars($profile_data['company_name'] ?? 'Company'); ?></h3>
            <nav>
                <a href="post_job.php">Post New Job</a>
                <a href="my_posting.php">Total Job Posted</a>
                <a href="view_applications.php">Total Applicants</a>
                <a class="active" href="settings.php">Settings</a>
                
            </nav>
        <?php else: ?>
            <h3><?php echo htmlspecialchars($profile_data['fullname'] ?? 'Job Seeker'); ?></h3>
            <nav>
                <a href="seeker_dashboard.php">Personal Info</a>
                <a href="my_applications.php">Applied Jobs List</a>
                <a href="../backendwithphp/skill.php">Skills</a>
                <a class="active" href="settings.php">Settings</a>
              
            </nav>
        <?php endif; ?>
      </aside>

      <div class="dashboard-main">
        <div class="dashboard-header-actions">
         
           <a href="../catagories.php">Job Listings</a>
        </div>

        <h1 style="color:white; margin-bottom: 5px;">Admin Dashboard</h1>
        
        <?php if($success_msg): ?>
            <div style="background: rgba(0, 200, 81, 0.2); border: 1px solid #00c851; color: #00c851; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>
        <?php if($error_msg): ?>
            <div style="background: rgba(255, 68, 68, 0.2); border: 1px solid #ff4444; color: #ff4444; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <!-- PROFILE SETTINGS FORM -->
        <h2 class="section-title" style="text-align: left; margin-top: 0;">Edit Profile</h2>
        <form class="company-form" action="../backendwithphp/update_settings.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action_type" value="update_profile">
            <input type="hidden" name="role" value="<?php echo $role; ?>">

            <!-- Profile Image Upload (Company Only) -->
            <?php if ($role == 'company'): ?>
            <div class="full" style="margin-bottom: 20px;">
                <label style="display:block; margin-bottom:8px; color:#ccc;">Profile Image</label>
                <input class="input" type="file" name="profile_image" accept="image/*">
                <?php if(!empty($profile_data['profile_image'])): ?>
                    <div style="margin-top:10px;">
                        <img src="../<?php echo htmlspecialchars($profile_data['profile_image']); ?>" style="width:100px; height:100px; object-fit:cover; border-radius:50%; border:2px solid var(--accent-teal);">
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>


<?php if ($role == 'company'): ?>
                <!-- Company Fields -->
                <input class="input" type="text" name="company_name" value="<?php echo htmlspecialchars($profile_data['company_name'] ?? ''); ?>" placeholder="Company Name" required>
                <input class="input" type="text" name="contact_name" value="<?php echo htmlspecialchars($profile_data['contact_name'] ?? ''); ?>" placeholder="Contact Person" required>
                
                <select class="input" name="representative" style="color: #fff; background-color: #1f1f1f;">
                    <option value="General Manager" <?php echo (isset($profile_data['representative']) && $profile_data['representative'] == 'General Manager') ? 'selected' : ''; ?>>General Manager</option>
                    <option value="Representative" <?php echo (isset($profile_data['representative']) && $profile_data['representative'] == 'Representative') ? 'selected' : ''; ?>>Representative</option>
                    <option value="HR" <?php echo (isset($profile_data['representative']) && $profile_data['representative'] == 'HR') ? 'selected' : ''; ?>>HR</option>
                </select>
                
                <input class="input" type="text" name="location" value="<?php echo htmlspecialchars($profile_data['location'] ?? ''); ?>" placeholder="Location">
                
                <textarea class="input full" name="description" placeholder="About Company"><?php echo htmlspecialchars($profile_data['description'] ?? ''); ?></textarea>

         <?php else: ?>
                <!-- Job Seeker Fields -->
                <input class="input" type="text" name="fullname" value="<?php echo htmlspecialchars($profile_data['fullname'] ?? ''); ?>" placeholder="Full Name" required>
                <input class="input" type="text" name="profession_title" value="<?php echo htmlspecialchars($profile_data['profession_title'] ?? ''); ?>" placeholder="Professional Title (e.g. Web Developer)">
                
                <select class="input" name="skill_level" style="color: #fff; background-color: #1f1f1f;">
                    <option value="" disabled <?php echo empty($profile_data['skill_level']) ? 'selected' : ''; ?>>Select Skill Level</option>
                    <option value="Bignner" <?php echo (isset($profile_data['skill_level']) && $profile_data['skill_level'] == 'Bignner') ? 'selected' : ''; ?>>Beginner</option>
                    <option value="Intermidate" <?php echo (isset($profile_data['skill_level']) && $profile_data['skill_level'] == 'Intermidate') ? 'selected' : ''; ?>>Intermediate</option>
                    <option value="Advanced" <?php echo (isset($profile_data['skill_level']) && $profile_data['skill_level'] == 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                </select>
                
                <input class="input" type="text" name="city" value="<?php echo htmlspecialchars($profile_data['city'] ?? ''); ?>" placeholder="Location">
                <input class="input full" type="text" name="primary_interest" value="<?php echo htmlspecialchars($profile_data['primary_interest'] ?? ''); ?>" placeholder="Primary Job Interest">
                
                <textarea class="input full" name="bio" placeholder="About Me"><?php echo htmlspecialchars($profile_data['bio'] ?? ''); ?></textarea>
            <?php endif; ?>

            <div style="margin-top:10px; text-align:right; grid-column: 1 / -1;">
                <button type="submit" class="btn">Save Changes</button>
            </div>
        </form>

        <br><br>


<!-- ACCOUNT SECURITY FORM -->
        <h2 class="section-title" style="text-align: left; margin-top: 0;">Security Options</h2>
        <form class="company-form" action="../backendwithphp/update_settings.php" method="POST">
            <input type="hidden" name="action_type" value="update_password">
            
            <div class="full" style="display:flex; flex-direction:column; gap:5px;">
                <label style="color:var(--text-gray); font-size:0.9rem; margin-left:5px;">Email Address (Cannot be changed)</label>
                <input class="input" type="email" value="<?php echo htmlspecialchars($current_email); ?>" disabled style="opacity: 0.7; cursor: not-allowed;">
            </div>
            
            <input class="input full" type="password" name="current_password" placeholder="Current Password" required>
            <input class="input" type="password" name="new_password" placeholder="New Password" required>
            <input class="input" type="password" name="confirm_new_password" placeholder="Confirm New Password" required>

            <div style="margin-top:10px; text-align:right; grid-column: 1 / -1;">
                <button type="submit" class="btn btn-green">Update Password</button>
            </div>
        </form>

      </div>
    </div>
  </section>

  <script src="../assets/js/responsive.js"></script>
</body>
</html>