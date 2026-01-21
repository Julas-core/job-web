<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$dashboardLink = (isset($_SESSION['role']) && ($_SESSION['role'] == 'company' || $_SESSION['role'] == 'employer')) ? 'employer_dashboard.php' : 'seeker_dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>JobLaunch | Company Profile</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="../style.css">
</head>
<body class="page-wrap">
  <header class="navbar">
        <div class="brand">JobLaunch</div>
        <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false"><span></span></button>
        <div class="nav-drawer">
            <div class="auth-links">
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo $dashboardLink; ?>">Dashboard</a>
                    <span class="divider">|</span>
                    <a href="../backendwithphp/logout.php">Logout</a>
                <?php else: ?>
                    <a href="../login.html">Login</a>
                    <span class="divider">|</span>
                    <a href="../register.html">Register</a>
                <?php endif; ?>
            </div>
            <div class="nav-links">
                <a class="active" href="../index.php">Home</a>
                <a href="../catagories.html">Categories</a>
                <a href="../about.html">AboutUs</a>
                <a href="../contact.html">ContactUs</a>
            </div>
        </div>
    </header>
  <section class="dashboard-section container">
    <div class="dashboard-shell">
      <aside class="sidebar">
        <div class="avatar" style="background:#dcdcdc;"></div>
        <h3>Company Name</h3>
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
          <a href="../catagories.html">Job Listings</a>
        </div>


        <h1 class="section-title" style="text-align: left; margin-top: 0;">Company Dashboard</h1>

        <p>Welcome to your company dashboard. Use the sidebar to manage jobs and view applicants.</p>

      </div>
    </div>
  </section>
<script src="../assets/js/responsive.js" defer></script>
</body>
</html>