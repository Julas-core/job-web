
<?php
session_start();
include "../backendwithphp/db_conection.php";

// Auth Check: Ensure user is logged in as company or employer
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || ($_SESSION['role'] !== 'company' && $_SESSION['role'] !== 'employer')) {
    header("Location: ../login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch Company Details
$stmt = $conect->prepare("SELECT company_id, company_name, profile_image FROM company WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    // Fallback if execution fails
    die("Error fetching company details.");
}
$res = $stmt->get_result();
$company = $res->fetch_assoc();

// If no company profile is found (e.g. fresh registration), handle gracefully
if (!$company) {
    echo "<script>alert('Please complete your company profile first.'); window.location.href='settings.php';</script>";
    exit();
}

$company_id = $company['company_id'];
$company_name = $company['company_name'];
$profile_image = $company['profile_image'];
$stmt->close();

// Job Filtering
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$header_text = "Total Applicants (All Jobs)";
$sql_filter = "";

if ($job_id > 0) {
    // Verify job belongs to this company
    $check = $conect->prepare("SELECT title FROM jobs WHERE job_id = ? AND company_id = ?");
    $check->bind_param("ii", $job_id, $company_id);
    $check->execute();
    $jres = $check->get_result();
    if ($jrow = $jres->fetch_assoc()) {
        $header_text = "Applicants for: <span style='color:var(--accent-teal);'>" . htmlspecialchars($jrow['title']) . "</span>";
        $sql_filter = " AND j.job_id = ?"; 
    }
    $check->close();
}

// Fetch Applications
// We join 'jobs' to ensure we only get applications for this company's jobs
$query = "SELECT a.*, j.title as job_title 
          FROM applications a 
          JOIN jobs j ON a.job_id = j.job_id 
          WHERE j.company_id = ?";

if ($job_id > 0 && !empty($sql_filter)) {
    $query .= " AND j.job_id = ?";
}

$query .= " ORDER BY a.applied_at DESC";

$stmt = $conect->prepare($query);

if ($job_id > 0 && !empty($sql_filter)) {
    $stmt->bind_param("ii", $company_id, $job_id);
} else {
    $stmt->bind_param("i", $company_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>JobLaunch | View Applicants</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="../style.css"> 
  <style>
      .app-card {
          background: #1f1f1f;
          border-radius: 10px;
          padding: 20px;
          margin-bottom: 15px;
          border: 1px solid #333;
          display: grid;
          grid-template-columns: 1fr auto;
          gap: 15px;
          align-items: center;
      }
      .app-info h4 { margin: 0 0 5px 0; color: white; font-size: 1.1rem; }
      .app-meta { color: #aaa; font-size: 0.9rem; margin-bottom: 8px; }
      .app-meta i { margin-right: 6px; color: var(--accent-teal); }
      .app-actions { display: flex; gap: 10px; }
      .status-badge {
          display: inline-block;
          padding: 4px 12px;
          border-radius: 20px;
          font-size: 0.8rem;
          font-weight: bold;
          text-transform: uppercase;
      }
      .status-pending { background: #ffc107; color: #000; }
      .status-accepted { background: #00d12d; color: #000; }
      .status-rejected { background: #f44336; color: white; }

      .status-indicator {
          position: absolute;
          top: 15px;
          right: 15px;
          height: 12px;
          width: 12px;
          border-radius: 50%;
      }
      .indicator-pending { background-color: #ffc107; box-shadow: 0 0 8px #ffc107; }
      .indicator-accepted { background-color: #00d12d; box-shadow: 0 0 8px #00d12d; }
      .indicator-rejected { background-color: #f44336; box-shadow: 0 0 8px #f44336; }


.status-select {
          padding: 6px 10px;
          border-radius: 5px;
          border: 1px solid #555;
          background: #2a2a2a;
          color: white;
          cursor: pointer;
          font-weight: bold;
      }
      .status-select:focus { outline: none; border-color: var(--accent-teal); }
  </style>
  <script>
    function updateStatus(selectStats, appId) {
        const newStatus = selectStats.value;
        
        let statusToSend = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
        
        fetch('../backendwithphp/update_application_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ application_id: appId, status: statusToSend })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                // Update indicator color
                const indicator = document.getElementById('indicator-' + appId);
                indicator.className = 'status-indicator indicator-' + newStatus.toLowerCase();
                
                // Update select border color optionally or leave as is
                // alert('Status updated');
            } else {
                alert('Failed to update status: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(err => console.error(err));
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
          <a href="my_posting.php">Total Job Posted</a>
          <a class="active" href="view_applications.php">Total Applicants</a>
          <!-- <a href="my_posting.php">Manage Jobs</a> -->
          <a href="settings.php">Settings</a>
          
        </nav>
      </aside>


<div class="dashboard-main">
        <div class="dashboard-header-actions">
          
          <a href="../catagories.html">Job Listings</a>
        </div>
        
        <h1 style="color:white; margin-bottom: 5px;">Admin Dashboard</h1>
        <h2 class="section-title" style="text-align: left; margin-top: 0;">
            <?php echo $header_text; ?>
        </h2>
        
        <a href="my_posting.php" class="btn btn-ghost" style="width: fit-content; align-self: flex-start; margin-bottom: 20px;">
            &larr; Back to Postings
        </a>

        <div class="applications-list">
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="app-card" style="position:relative;">
                        <div class="status-indicator indicator-<?php echo strtolower($row['job_status']); ?>" id="indicator-<?php echo $row['app_id']; ?>"></div>
                        <div class="app-info">
                            <h4><?php echo htmlspecialchars($row['fullname']); ?></h4>
                            <div class="app-meta">
                                <span><i class="fa-solid fa-briefcase"></i> Applied for: <strong><?php echo htmlspecialchars($row['job_title']); ?></strong></span>
                            </div>
                            <div class="app-meta">
                                <span><i class="fa-brands fa-telegram"></i> <?php echo htmlspecialchars($row['telegram']); ?></span>
                                <span style="margin-left: 15px;"><i class="fa-solid fa-layer-group"></i> Exp: <?php echo htmlspecialchars($row['skill_level']); ?></span>
                                <span style="margin-left: 15px;"><i class="fa-solid fa-money-bill"></i> Exp. Salary: <?php echo htmlspecialchars($row['expected_salary']); ?></span>
                            </div>
                            <?php if(!empty($row['cover_letter'])): ?>
                                <p style="font-size:0.9rem; color:#ccc; margin: 10px 0; font-style: italic;">
                                    "<?php echo htmlspecialchars(substr($row['cover_letter'], 0, 100)) . (strlen($row['cover_letter']) > 100 ? '...' : ''); ?>"
                                </p>
                            <?php endif; ?>
                            
                            <div style="margin-top: 10px;">
                                <select onchange="updateStatus(this, <?php echo $row['app_id']; ?>)" class="status-select">
                                    <option value="pending" <?php echo strtolower($row['job_status']) == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="accepted" <?php echo strtolower($row['job_status']) == 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                    <option value="rejected" <?php echo strtolower($row['job_status']) == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="app-actions" style="flex-direction: column;">
                            <a href="<?php echo htmlspecialchars($row['resumee']); ?>" target="_blank" class="btn btn-sm" style="background:transparent; border:1px solid #aaa; color:white; padding: 8px 12px; font-size: 0.9rem;">
                                <i class="fa-solid fa-file-pdf"></i> Resume
                            </a>
                            <a href="<?php echo htmlspecialchars($row['portfolio']); ?>" target="_blank" class="btn btn-sm" style="background:transparent; border:1px solid #aaa; color:white; padding: 8px 12px; font-size: 0.9rem;">
                                <i class="fa-solid fa-link"></i> Portfolio
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>


<div style="padding: 40px; text-align: center; background: #1f1f1f; border-radius: 10px;">
                    <h3 style="color: #aaa;">No applicants found yet.</h3>
                    <p>When job seekers apply to your active job postings, they will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
</body>
</html>