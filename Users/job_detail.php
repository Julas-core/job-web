
<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$dashboardLink = 'seeker_dashboard.php';

// Check for role (singular 'role' as set in login.php)
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';


if ($role == 'company') {
    $dashboardLink = 'employer_dashboard.php'; 
} elseif ($role == 'job_seeker') {
    $dashboardLink = 'seeker_dashboard.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>JobLaunch | Job Details</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="../style.css">
</head>
<body class="page-wrap">
  <?php $basePath = '..'; include __DIR__ . '/../partials/header.php'; ?>

  <?php
    // Fetch job by id (if provided) and populate variables for server-side rendering
    $jobId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $job = null;
    if ($jobId > 0) {
        include __DIR__ . '/../backendwithphp/db_conection.php';

        // Try common table name first, then try to discover a jobs-like table
        $table = null;
        $pk = null; // primary key column name

        $res = $conect->query("SHOW TABLES LIKE 'jobs'");
        if ($res && $res->num_rows > 0) {
            $table = 'jobs';
            // prefer 'id' if present
            $desc = $conect->query("DESCRIBE $table");
            $cols = [];
            while ($c = $desc->fetch_assoc()) $cols[] = $c['Field'];
            $pk = in_array('id', $cols) ? 'id' : (in_array('job_id', $cols) ? 'job_id' : null);
        } else {
            // Discover any table that looks like a job table (has 'title' and either 'job_id' or 'id')
            $tablesRes = $conect->query("SHOW TABLES");
            while ($trow = $tablesRes->fetch_row()) {
                $tname = $trow[0];
                $desc = $conect->query("DESCRIBE $tname");
                $cols = [];
                while ($c = $desc->fetch_assoc()) {
                    $cols[] = $c['Field'];
                }
                if (in_array('title', $cols) && (in_array('job_id', $cols) || in_array('id', $cols))) {
                    $table = $tname;
                    $pk = in_array('id', $cols) ? 'id' : 'job_id';
                    break;
                }
            }
        }


if ($table && $pk) {
            // Build dynamic query and join company if possible
            $join = "LEFT JOIN company c ON $table.company_id = c.company_id";
            $sql = "SELECT $table.*, c.company_name FROM $table $join WHERE $table.$pk = ? LIMIT 1";
            $stmt = $conect->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('i', $jobId);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) {
                    $job = $res->fetch_assoc();
                    // Normalize column names for templates
                    if (isset($job['descriptions']) && !isset($job['description'])) $job['description'] = $job['descriptions'];
                    if (isset($job['job_type']) && !isset($job['type'])) $job['type'] = $job['job_type'];
                    if (isset($job['locatons']) && !isset($job['location'])) $job['location'] = $job['locatons'];
                    if (isset($job['responsibility']) && !isset($job['responsibilities'])) $job['responsibilities'] = $job['responsibility'];
                    if (isset($job['responsibilites']) && !isset($job['responsibilities'])) $job['responsibilities'] = $job['responsibilites'];
                    if (isset($job['job_responsibilities']) && !isset($job['responsibilities'])) $job['responsibilities'] = $job['job_responsibilities'];
                    if (isset($job['job_requirements']) && !isset($job['requirements'])) $job['requirements'] = $job['job_requirements'];
                    // Normalize primary id to 'id'
                    if (!isset($job['id'])) {
                        if (isset($job['job_id'])) $job['id'] = $job['job_id'];
                    }
                }
                $stmt->close();
            }
        } else {
            // No jobs-like table found; handle gracefully by leaving $job null
            // (template will show default placeholders)
        }
    }
  ?>

  <?php
    function split_list_items($text) {
        $text = (string)$text;
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[•·]/', "\n", $text);
        $text = preg_replace('/\s*[,;]\s*/', "\n", $text);
        $parts = array_map('trim', explode("\n", $text));
        return array_values(array_filter($parts, function($p) { return $p !== ''; }));
    }

    // Helper for display
    $companyName = htmlspecialchars($job['company_name'] ?? 'Company');
    $logoLetter = strtoupper(substr($companyName, 0, 1));
    $postedDate = isset($job['created_at']) ? date('M j, Y', strtotime($job['created_at'])) : '--';
    
    // Calculate time ago
    $postedText = $postedDate;
    if (isset($job['created_at'])) {
        $createdAt = new DateTime($job['created_at']);
        $now = new DateTime();
        $interval = $now->diff($createdAt);
        $daysAgo = $interval->days;
        $postedText = ($daysAgo == 0) ? "Posted Today" : (($daysAgo == 1) ? "Posted 1 day ago" : "Posted $daysAgo days ago");
    }
  ?>

  <main class="container" style="padding:60px 0; display: flex; justify-content: center;">
    <div style="width: 100%; max-width: 800px; display: flex; flex-direction: column; gap: 20px;">
    <a href="javascript:history.back()" class="subtle" style="font-weight:700;display:inline-flex;gap:8px;align-items:center; color: var(--muted); text-decoration: none;">
      <i class="fa-solid fa-arrow-left"></i> Back to listings
    </a>


<!-- Main Detail Card -->
    <section class="job-card" style="cursor: default; transform: none; box-shadow: 0 20px 50px rgba(0,0,0,0.3);">
      
      <!-- Card Header -->
      <div class="card-header">
        <div class="header-left">
          <div class="company-logo-placeholder" style="width: 64px; height: 64px; font-size: 2rem; background: #f0f0f0; color: #333;">
            <?php echo $logoLetter; ?>
          </div>
          <div class="title-info">
            <h1 class="job-title" style="font-size: 1.8rem; margin-bottom: 6px;"><?php echo htmlspecialchars($job['title'] ?? 'Job Title'); ?></h1>
            <p class="company" style="font-size: 1rem;">
                <i class="fa-solid fa-building"></i> <?php echo $companyName; ?> 
                <span class="dot">•</span> 
                <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($job['location'] ?? 'Location'); ?>
            </p>
          </div>
        </div>
        <div class="posted-date" style="align-self: flex-start; margin-top: 10px;">
            <?php echo $postedText; ?>
        </div>
      </div>

      <!-- Tags & Meta -->
      <div class="tags-row" style="margin-top: 16px;">
        <span class="badge badge-green"><i class="fa-solid fa-briefcase"></i> <?php echo htmlspecialchars($job['type'] ?? 'Type'); ?></span>
        <span class="badge badge-purple"><i class="fa-solid fa-layer-group"></i> <?php echo htmlspecialchars($job['category'] ?? 'General'); ?></span>
        <span class="badge badge-orange"><i class="fa-solid fa-money-bill"></i> <?php echo htmlspecialchars($job['salary'] ?? 'Negotiable'); ?></span>
      </div>

      <hr style="border: 0; border-top: 1px solid #eee; margin: 24px 0;">

      <!-- Description -->
      <div style="color: #333;">
          <h3 style="margin-bottom: 12px; color: #111;">Description</h3>
          <p class="job-desc" style="min-height:auto; -webkit-line-clamp: unset; font-size: 1rem; color: #444;">
            <?php echo nl2br(htmlspecialchars($job['description'] ?? 'No description available.')); ?>
          </p>
      </div>

      <!-- Requirements -->
      <div style="margin-top: 24px; color: #333;">
          <h3 style="margin-bottom: 12px; color: #111;">Requirements</h3>
          <ul style="margin-left: 20px; color: #444; line-height: 1.6; list-style-type: disc;">
            <?php
              $requirementItems = !empty($job['requirements']) ? split_list_items($job['requirements']) : [];
              if (!empty($requirementItems)) {
                foreach ($requirementItems as $r) {
                  echo '<li style="margin-bottom: 6px;">' . htmlspecialchars($r) . '</li>';
                }
              } else {
                echo '<li>No specific requirements listed.</li>';
              }
            ?>
          </ul>
      </div>

      <!-- Footer / Action -->
      <div class="card-footer" style="padding-top: 24px; margin-top: 24px;">
        <div class="salary" style="font-size: 1.2rem;"><?php echo htmlspecialchars($job['salary'] ?? ''); ?></div>
        <?php if (!$isLoggedIn): ?>
            <a class="btn" href="../login.html">Login to Apply</a>
        <?php elseif ($role == 'company'): ?>
            <button class="btn btn-ghost" disabled style="cursor:not-allowed; opacity: 0.6;">Employers cannot apply</button>
        <?php else: ?>
            <a class="btn" data-apply-link href="apply_form.php?job_id=<?php echo intval($jobId); ?>">Apply Now</a>
        <?php endif; ?>
      </div>

    </section>
    </div>
  </main>
  <script src="../script.js"></script>
<script src="../assets/js/responsive.js" defer></script>
</body>
</html>