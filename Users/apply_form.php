
<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Redirect if not logged in
if (!$isLoggedIn) {
    header("Location: ../login.html");
    exit();
}

// Redirect if employer/company tries to access apply form
if ($role == 'company' || $role == 'employer') {
    header("Location: employer_dashboard.php");
    exit();
}

$dashboardLink = ($role == 'company' || $role == 'employer') ? 'employer_dashboard.php ' : 'seeker_dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>JobLaunch | Apply</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="../style.css">
</head>
<body class="page-wrap">
 <?php $basePath = '..'; include __DIR__ . '/../partials/header.php'; ?>

  <?php
    // Fetch job title for display if job_id provided
    $job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
    $job_title = '';
    if ($job_id > 0) {
        include __DIR__ . '/../backendwithphp/db_conection.php';
        $stmt = $conect->prepare("SELECT title, company_id FROM jobs WHERE job_id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $job_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $job_title = $row['title'];
            }
            $stmt->close();
        }
    }
  ?>

  <section class="apply-section">
    <div style="max-width:760px;margin:0 auto 24px;">
      <?php if (!empty($job_title)): ?>
        <h2 style="margin:0 0 6px;">Apply for: <?php echo htmlspecialchars($job_title); ?></h2>
      <?php endif; ?>
    </div>

    <form class="form-card apply-card" action="../backendwithphp/apply.php" method="post" enctype="multipart/form-data" data-validate data-apply-form>
      <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
      <div class="apply-row">
        <input class="input" name="fullname" type="text" placeholder="Full Name" aria-label="Full Name" required>
      </div>

      <div class="apply-row">
        <select class="input" name="skill_level" aria-label="Skill Level" required>
            <option value="" disabled selected>Select Skill Level</option>
            <option value="Entry">Entry</option>
            <option value="Junior">Junior</option>
            <option value="Mid">Mid</option>
            <option value="Senior">Senior</option>
        </select>
      </div>

      <div class="apply-row">
        <input class="input" name="salary" type="number" placeholder="Expected Salary (ETB)" aria-label="Expected Salary" required>
      </div>

      <div class="apply-row">
        <label class="subtle" style="text-align:left;">Resume (Upload file OR paste a public URL)</label>
        <input class="input" name="resume_file" type="file" aria-label="Upload Resume (file)">
        <input class="input" name="resume_url" type="text" placeholder="https://example.com/resume.pdf (optional)" aria-label="Resume URL">
      </div>


<label class="subtle" style="text-align:left;">Please Enter Your Cover Letter</label>
      <div class="apply-textarea">
        <textarea name="cover" placeholder="Cover Letter..." aria-label="Cover Letter" required></textarea>
      </div>

      <div class="apply-row">
        <input class="input" name="telegram" type="text" placeholder="Telegram Username (Optional)" aria-label="Telegram Username (Optional)">
      </div>

      <div class="apply-row">
        <label class="subtle">Please Enter Your Portfolio links</label>
        <input class="input" name="portfolio" type="url" placeholder="Portfolio Link 1" aria-label="Portfolio Link 1">
        <div class="apply-actions">
          <button class="btn-ghost" type="button">Add Portfolio Link</button>
        </div>
      </div>

      <div style="text-align:center; margin-top:10px;">
        <button class="btn" type="submit">Submit</button>
      </div>
    </form>
  </section>
  <script src="../script.js"></script>
<script src="../assets/js/responsive.js" defer></script>
</body>
</html>