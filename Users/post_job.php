
<?php
session_start();
include "../backendwithphp/db_conection.php";

$isLoggedIn = isset($_SESSION['user_id']);
$dashboardLink = (isset($_SESSION['role']) && ($_SESSION['role'] == 'company' || $_SESSION['role'] == 'employer')) ? 'employer_dashboard.php' : 'seeker_dashboard.php';

// Fetch Company Name & Image
$company_name = "Company Name";
$profile_image = "";
if ($isLoggedIn) {
   $user_id = $_SESSION['user_id'];
   $stmt = $conect->prepare("SELECT company_name, profile_image FROM company WHERE user_id = ?");
   $stmt->bind_param("i", $user_id);
   $stmt->execute();
   $res = $stmt->get_result();
   if ($row = $res->fetch_assoc()) {
       $company_name = $row['company_name'];
       $profile_image = $row['profile_image'];
   }
   $stmt->close();
}

// Edit Mode Logic
$isEdit = false;
$jobData = [
    'title' => '',
    'description' => '',
    'location' => '',
    'type' => '',
    'requirements' => '',
    'salary' => ''
];
$jobId = '';

if (isset($_GET['edit']) && isset($_GET['job_id'])) {
    $isEdit = true;
    $jobId = intval($_GET['job_id']);
    
    // Fetch job details securely ensuring it belongs to the logged-in company
    // Need company_id first
    $stmt = $conect->prepare("SELECT company_id FROM company WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $cRes = $stmt->get_result();
    if ($cRow = $cRes->fetch_assoc()) {
        $compId = $cRow['company_id'];
        
        $jStmt = $conect->prepare("SELECT * FROM jobs WHERE job_id = ? AND company_id = ?");
        $jStmt->bind_param("ii", $jobId, $compId);
        $jStmt->execute();
        $jRes = $jStmt->get_result();
        if ($jRes->num_rows > 0) {
            $jobData = $jRes->fetch_assoc();
        } else {
            // Job not found or not owned by user
            echo "<script>alert('Job not found or access denied.'); window.location.href='my_posting.php';</script>";
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>JobLaunch | <?php echo $isEdit ? 'Edit Job' : 'Post Job'; ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="../style.css">
</head>
<body class="page-wrap">
    <?php $basePath = '..'; include __DIR__. '/../partials/header.php'; ?>

  <section class="dashboard-section">
    <div class="dashboard-shell">
      <aside class="sidebar">
        <?php if(!empty($profile_image)): ?>
            <div class="avatar" style="background-image: url('../<?php echo htmlspecialchars($profile_image); ?>'); background-size: cover; background-position: center;"></div>
        <?php else: ?>
            <div class="avatar" style="background:#dcdcdc;"></div>
        <?php endif; ?>
        <h3><?php echo htmlspecialchars($company_name); ?></h3>
        <nav>
          <a class="<?php echo $isEdit ? '' : 'active'; ?>" href="post_job.php">Post New Job</a>
          <a class="<?php echo $isEdit ? 'active' : ''; ?>" href="my_posting.php">Total Job Posted</a>
          <a href="view_applications.php">Total Applicants</a>
          <!-- <a href="my_posting.php">Manage Jobs</a> -->
          <a href="settings.php">Settings</a>
          
        </nav>
      </aside>

      <div class="dashboard-main">
        <div class="dashboard-header-actions">
          
          <a href="../catagories.html">Job Listings</a>
        </div>

        <h1 style="color:white; margin-bottom: 5px;">Admin Dashboard</h1>
        <h2 class="section-title" style="text-align: left; margin-top: 0;"><?php echo $isEdit ? 'Edit Job' : 'Post a New Job'; ?></h2>


<!-- Added action, method, and name attributes -->
        <form class="company-form" action="../backendwithphp/<?php echo $isEdit ? 'update_job.php' : 'new_jobs.php'; ?>" method="POST">
          <?php if($isEdit): ?>
            <input type="hidden" name="job_id" value="<?php echo $jobId; ?>">
          <?php endif; ?>
          
          <input class="input" type="text" name="title" placeholder="Job Title / Responsibility" value="<?php echo htmlspecialchars($jobData['title']); ?>" required>
          <textarea class="input" name="description" style="grid-column:2/-1;min-height:180px;" placeholder="Full Job Description" required><?php echo htmlspecialchars($jobData['description']); ?></textarea>
          <input class="input" type="text" name="location" placeholder="Location" value="<?php echo htmlspecialchars($jobData['location']); ?>" required>
          
          <!-- Changed to Select Dropdown for ENUM -->
          <select class="input" name="type" required style="color: #fff; background-color: #1f1f1f;">
            <option value="" disabled <?php echo empty($jobData['type']) ? 'selected' : ''; ?>>Select Job Type</option>
            <option value="Full Time" <?php echo ($jobData['type'] == 'Full Time') ? 'selected' : ''; ?>>Full Time</option>
            <option value="Part Time" <?php echo ($jobData['type'] == 'Part Time') ? 'selected' : ''; ?>>Part Time</option>
            <option value="Contract" <?php echo ($jobData['type'] == 'Contract') ? 'selected' : ''; ?>>Contract</option>
            <option value="Freelance" <?php echo ($jobData['type'] == 'Freelance') ? 'selected' : ''; ?>>Freelance</option>
            <option value="Internship" <?php echo ($jobData['type'] == 'Internship') ? 'selected' : ''; ?>>Internship</option>
          </select>

          <input class="input full" type="text" name="requirements" placeholder="Requirements" value="<?php echo htmlspecialchars($jobData['requirements']); ?>">
          <input class="input full" type="text" name="salary" placeholder="Salary (Optional)" value="<?php echo htmlspecialchars($jobData['salary']); ?>">
          
          <div style="margin-top:10px; text-align:right; grid-column: 1 / -1;">
            <button class="btn" type="submit"><?php echo $isEdit ? 'Update Job' : 'Save Job'; ?></button>
            <?php if ($isEdit): ?>
                <a href="my_posting.php" class="btn" style="background:#555; text-decoration:none;">Cancel</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </section>
</body>
</html>