<?php
session_start();
include "../backendwithphp/db_conection.php";

$isLoggedIn = isset($_SESSION['user_id']);
$dashboardLink = (isset($_SESSION['role']) && ($_SESSION['role'] == 'company' || $_SESSION['role'] == 'employer')) ? 'employer_dashboard.php' : 'seeker_dashboard.php';

$fullname = "User";
$profile_image = "";
if ($isLoggedIn) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conect->prepare("SELECT fullname, profile_image FROM job_seeker WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $fullname = $row['fullname'] ?: "User";
        $profile_image = $row['profile_image'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>JobLaunch | User Profile</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="../style.css">
</head>
<body class="page-wrap">
  <?php $basePath = '..'; include __DIR__ . '/../partials/header.php'; ?>

  <section class="dashboard-section" style="padding:0; max-width:100%; margin:0;">
    <div class="dashboard-shell">
      <aside class="sidebar">
        <?php if(!empty($profile_image)): ?>
            <div class="avatar" style="background-image: url('../<?php echo htmlspecialchars($profile_image); ?>'); background-size: cover; background-position: center;"></div>
        <?php else: ?>
            <div class="avatar" style="background:#dcdcdc;"></div>
        <?php endif; ?>
        <h3><?php echo htmlspecialchars($fullname); ?></h3>
        <nav>
          <a class="active" href="seeker_dashboard.php">Personal Info</a>
          <a href="my_applications.php">Applied Jobs List</a>
          <a href="../backendwithphp/skill.php">Skills</a>
          <a href="settings.php">Settings</a>
         
        </nav>
      </aside>

      <div class="dashboard-main">
        <div class="profile-hero">
          <?php if(!empty($profile_image)): ?>
              <div class="big-avatar" style="background-image: url('../<?php echo htmlspecialchars($profile_image); ?>'); background-size: cover; background-position: center;"></div>
          <?php else: ?>
              <div class="big-avatar"></div>
          <?php endif; ?>
          <h1>Welcome Back,<br><?php echo htmlspecialchars($fullname); ?></h1>
        </div>

        

        <div class="profile-jobs">
          <!--Recommended Jobs will show up here-->
          </article>
        </div>
      </div>
    </div>
  </section>
</body>
</html>