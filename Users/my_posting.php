
<?php
session_start();
include "../backendwithphp/db_conection.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'company' && $_SESSION['role'] !== 'employer')) {
    header("Location: ../login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get company_id
$stmt = $conect->prepare("SELECT company_id, company_name, profile_image FROM company WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$company = $res->fetch_assoc();
$company_id = $company['company_id'];
$company_name = $company['company_name'];
$profile_image = $company['profile_image'];
$stmt->close();

// Fetch jobs with applicant count
$job_stmt = $conect->prepare("SELECT j.*, 
                            (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.job_id) as applicant_count 
                            FROM jobs j 
                            WHERE j.company_id = ? ORDER BY j.created_at DESC");
$job_stmt->bind_param("i", $company_id);
$job_stmt->execute();
$jobs_result = $job_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>JobLaunch | My Job Postings</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="../style.css">
  <script>
    function toggleDesc(id, event) {
        if(event) {
            event.preventDefault();
            event.stopPropagation();
        }
        var shortText = document.getElementById('short-' + id);
        var fullText = document.getElementById('full-' + id);
        var btn = event.target;
        
        if (fullText.style.display === 'none') {
            fullText.style.display = 'inline';
            shortText.style.display = 'none';
            btn.innerText = 'Read less';
        } else {
            fullText.style.display = 'none';
            shortText.style.display = 'inline';
            btn.innerText = 'Read more';
        }
    }
  </script>
</head>
<body class="page-wrap">
    <?php $basePath = '..'; include __DIR__ . '/../partials/header.php'; ?>
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
          <a href="post_job.php">Post New Job</a>
          <a class="active" href="my_posting.php">Total Job Posted</a>
          <a href="view_applications.php">Total Applicants</a>
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
        <h2 class="section-title" style="text-align: left; margin-top: 0px;">Active Job Postings</h2>


<div class="job-grid">
            <?php if ($jobs_result->num_rows > 0): ?>
                <?php while($job = $jobs_result->fetch_assoc()): ?>
                    <?php
                        $jobId = $job['job_id'];
                        $jobTitle = htmlspecialchars($job['title']);
                        $location = htmlspecialchars($job['location']);
                        $type = htmlspecialchars($job['type']);
                        $salary = htmlspecialchars($job['salary'] ?? 'Negotiable');
                        $applicants = $job['applicant_count'] ?? 0;
                        $desc = htmlspecialchars($job['description']);
                        $descSnippet = strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc;
                        
                        // Time calculation
                        $createdAt = new DateTime($job['created_at']);
                        $now = new DateTime();
                        $interval = $now->diff($createdAt);
                        $daysAgo = $interval->days;
                        $postedText = ($daysAgo == 0) ? "Today" : (($daysAgo == 1) ? "1 day ago" : "$daysAgo days ago");
                        
                        // Styling logic
                        $typeClass = 'badge-green';
                        if (stripos($type, 'Part') !== false) $typeClass = 'badge-orange';
                        if (stripos($type, 'Intern') !== false) $typeClass = 'badge-purple';
                        
                        $logoLetter = strtoupper(substr($company_name, 0, 1));
                    ?>
                    <div class="job-card" onclick="toggleDesc('job-<?php echo $jobId; ?>', null)" style="cursor:pointer;">
                        <div class="card-header">
                            <div class="header-left">
                                <?php if (!empty($profile_image)): ?>
                                    <div class="company-logo-placeholder" style="background-image: url('../<?php echo htmlspecialchars($profile_image); ?>'); background-size: cover; background-position: center;"></div>
                                <?php else: ?>
                                    <div class="company-logo-placeholder"><?php echo $logoLetter; ?></div>
                                <?php endif; ?>
                                <div class="title-info">
                                    <h3 class="job-title"><?php echo $jobTitle; ?></h3>
                                    <!-- Made applicants clickable -->
                                    <a href="view_applications.php?job_id=<?php echo $jobId; ?>" class="company" style="text-decoration:none; cursor:pointer; z-index:2; position:relative;" onclick="event.stopPropagation()">
                                        <?php echo htmlspecialchars($company_name); ?> <span class="dot">•</span> 
                                        <span style="color:var(--accent-teal); font-weight:bold;"><?php echo $applicants; ?> Applicants</span>
                                    </a>
                                </div>
                            </div>
                            <!-- Edit Action linked to post_job.php with edit params -->
                            <a href="post_job.php?edit=1&job_id=<?php echo $jobId; ?>" class="favorite-btn" title="Edit Job" style="z-index:2;" onclick="event.stopPropagation()"><i class="fa-solid fa-pen-to-square"></i></a>
                        </div>

                        <div class="tags-row">
                            <!-- Removed Intermediate badge -->
                            <span class="badge <?php echo $typeClass; ?>"><?php echo $type; ?></span>
                            <?php if (stripos($location, 'Remote') !== false): ?>
                                <span class="badge badge-orange">Remote</span>
                            <?php else: ?>
                                 <span class="badge badge-outline"><?php echo $location; ?></span>
                            <?php endif; ?>
                        </div>


<div class="job-desc">
                            <?php if (strlen($desc) > 100): ?>
                                <span id="short-job-<?php echo $jobId; ?>"><?php echo $descSnippet; ?></span>
                                <span id="full-job-<?php echo $jobId; ?>" style="display:none;"><?php echo nl2br($desc); ?></span>
                                <button style="background:none; border:none; color:var(--accent-teal); cursor:pointer; font-size:0.85rem; font-weight:bold; margin-left:5px; z-index:2; position:relative; padding:0;">Read more</button>
                            <?php else: ?>
                                <?php echo nl2br($desc); ?>
                            <?php endif; ?>
                        </div>

                        <div class="card-footer">
                            <span class="salary"><?php echo $salary; ?></span>
                            <div style="display:flex; gap:10px; align-items:center;">
                                <div class="posted-date"><i class="fa-regular fa-clock"></i> <?php echo $postedText; ?></div>
                                <a href="job_detail.php?id=<?php echo $jobId; ?>" style="font-size:0.9rem; color:var(--text); text-decoration:none; z-index:2;" onclick="event.stopPropagation()"><i class="fa-solid fa-eye"></i> View Job</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1; color: #fff;">You haven't posted any jobs yet.</p>
            <?php endif; ?>
        </div>

      </div>
    </div>
  </section>
</body>
</html>