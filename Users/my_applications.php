
<?php
session_start();
include_once "../backendwithphp/db_conection.php";
$isLoggedIn = isset($_SESSION['user_id']);

// Check role logic expanded for clarity
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

if ($role === 'company' || $role === 'employer') {
    $dashboardLink = 'employer_dashboard.php';
} else {
    $dashboardLink = 'seeker_dashboard.php';
}

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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>JobLaunch | My Applications</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="../style.css">
  <script>
    function toggleDesc(id, event) {
        // Prevent clicking the card link
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

<section class="dashboard-section" style="padding:0; max-width:100%; margin:0;">
  <div class="dashboard-shell">

    <!-- Sidebar -->
    <aside class="sidebar">
       <?php if(!empty($profile_image)): ?>
            <div class="avatar" style="background-image: url('../<?php echo htmlspecialchars($profile_image); ?>'); background-size: cover; background-position: center;"></div>
        <?php else: ?>
            <div class="avatar" style="background:#dcdcdc;"></div>
        <?php endif; ?>
      <h3><?php echo htmlspecialchars($fullname); ?></h3>
      <nav>
        <a href="seeker_dashboard.php">Personal Info</a>
        <a class="active" href="my_applications.php">Applied Jobs List</a>
        <a href="../backendwithphp/skill.php">Skills</a>
        <a href="settings.php">Settings</a>
        <a href="more.php">More</a>
      </nav>
    </aside>

    <!-- Main -->
    <main class="dashboard-main">
      <h1>My Applications</h1>
      <p class="subtle">Track the status of jobs you have applied for</p>

      <div class="applications-list">

        <?php
        // Show logged-in user's applications
        if (!isset($_SESSION['user_id'])) {
            echo '<p>Please <a href="../login.html">log in</a> to view your applications.</p>';
        } else {
            include_once __DIR__ . '/../backendwithphp/db_conection.php';
            $user_id = intval($_SESSION['user_id']);

            // Determine how applications are linked to users (user_id or seeker_id)
            $cols = [];
            $cols_res = $conect->query("DESCRIBE applications");
            if ($cols_res) {
                while ($c = $cols_res->fetch_assoc()) $cols[] = $c['Field'];
            }

            $filter_col = null;
            $filter_val = null;
            if (in_array('user_id', $cols)) {
                $filter_col = 'user_id';
                $filter_val = $user_id;
            } elseif (in_array('seeker_id', $cols)) {
                // try to resolve seeker's id
                $sstmt = $conect->prepare("SELECT seeker_id FROM job_seeker WHERE user_id = ? LIMIT 1"


);
                if ($sstmt) {
                    $sstmt->bind_param('i', $user_id);
                    $sstmt->execute();
                    $sres = $sstmt->get_result();
                    if ($sres && $sres->num_rows > 0) {
                        $srow = $sres->fetch_assoc();
                        $filter_col = 'seeker_id';
                        $filter_val = intval($srow['seeker_id']);
                    }
                    $sstmt->close();
                }
            }

            $applications = [];
            if ($filter_col !== null) {
                // Choose an available column to order by (prefer timestamp-like columns)
                $order_candidates = ['created_at', 'applied_at', 'created', 'timestamp', 'id'];
                $order_col = null;
                foreach ($order_candidates as $c) {
                    if (in_array($c, $cols)) { $order_col = $c; break; }
                }
                $order_sql = $order_col ? "ORDER BY `$order_col` DESC" : '';

                $sql = "SELECT * FROM applications WHERE `$filter_col` = ? $order_sql";
                $stmt = $conect->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param('i', $filter_val);
                    $stmt->execute();
                    $ares = $stmt->get_result();
                    while ($arow = $ares->fetch_assoc()) $applications[] = $arow;
                    $stmt->close();
                }
            }

            // Detect jobs table to fetch job titles
            $jobTable = null; 
            $jobPk = 'id';
            
            // First check for standard 'jobs' table
            $res = $conect->query("SHOW TABLES LIKE 'jobs'");
            if ($res && $res->num_rows > 0) {
                $jobTable = 'jobs';
                // Fetch columns to determine PK
                $desc = $conect->query("DESCRIBE `jobs`");
                $tmpCols = [];
                if ($desc) {
                    while ($cc = $desc->fetch_assoc()) {
                         $tmpCols[] = $cc['Field'];
                    }
                }
                
                if (in_array('id', $tmpCols)) {
                    $jobPk = 'id';
                } elseif (in_array('job_id', $tmpCols)) {
                    $jobPk = 'job_id';
                }
            } else {
                // Try to discover table
                $tablesRes = $conect->query("SHOW TABLES");
                if ($tablesRes) {
                    while ($trow = $tablesRes->fetch_row()) {
                        $tname = $trow[0];
                        $desc = $conect->query("DESCRIBE `$tname`"); 
                        $tmpCols = [];
                        if ($desc) {
                            while ($cc = $desc->fetch_assoc()) {
                                $tmpCols[] = $cc['Field'];
                            }
                        }
                        
                        // Check for required columns
                        $has_title = false;
                        $has_id = false;
                        $has_job_id = false;
                        
                        foreach ($tmpCols as $col) {
                            if ($col === 'title') $has_title = true;
                            if ($col === 'id') $has_id = true;
                            if ($col === 'job_id') $has_job_id = true;
                        }

                        if ($has_title && ($has_id || $has_job_id)) {
                            $jobTable = $tname;
                            $jobPk = $has_id ? 'id' : 'job_id';
                            break;
                        }
                    }
                }
            }


if (empty($applications)) {
                echo '<p style="text-align:center;">No applications found.</p>';
            } else {
                foreach ($applications as $app) {
                    // helper to read fields with synonyms
                    $get = function($arr, $candidates) {
                        foreach ($candidates as $c) {
                            if (isset($arr[$c]) && $arr[$c] !== null) return $arr[$c];
                        }
                        return null;
                    };

                    $applied_at = $get($app, ['created_at','applied_at']);
                    $expected_salary = $get($app, ['expected_salary','salary']);
                    $resume = $get($app, ['resume_path','resume','resumee']);
                    $cover = $get($app, ['cover','cover_letter']);
                    $status = $get($app, ['job_status', 'status']);
                    $job_id_ref = isset($app['job_id']) ? intval($app['job_id']) : 0;

                    $job_title = 'Unknown';
                    $company_name = 'Confidential';
                    $profile_image = '';
                    if ($jobTable && $job_id_ref > 0) {
                        $jsql = "SELECT $jobPk as pk, title, company_id, salary FROM $jobTable WHERE $jobPk = ? LIMIT 1";
                        $jstmt = $conect->prepare($jsql);
                        if ($jstmt) {
                            $jstmt->bind_param('i', $job_id_ref);
                            $jstmt->execute();
                            $jres = $jstmt->get_result();
                            if ($jres && $jres->num_rows > 0) {
                                $jrow = $jres->fetch_assoc();
                                // normalize title
                                if (isset($jrow['title'])) $job_title = $jrow['title'];
                                if (!empty($jrow['company_id'])) {
                                    $cstmt = $conect->prepare("SELECT company_name, profile_image FROM company WHERE company_id = ? LIMIT 1");
                                    if ($cstmt) {
                                        $cstmt->bind_param('i', $jrow['company_id']);
                                        $cstmt->execute();
                                        $cres = $cstmt->get_result();
                                        if ($cres && $cres->num_rows > 0) {
                                            $crow = $cres->fetch_assoc();
                                            $company_name = $crow['company_name'];
                                            $profile_image = $crow['profile_image'] ?? '';
                                        }
                                        $cstmt->close();
                                    }
                                }
                            }
                            $jstmt->close();
                        }
                    }

                    // Prepare display variables
                    $logoLetter = strtoupper(substr($company_name, 0, 1));
                    $uniqueId = 'app-' . (isset($app['id']) ? $app['id'] : uniqid());
                    
                    // Status Badge Logic
                    $statusBadgeClass = 'badge-orange'; // Pending default
                    $statusText = 'Pending';
                    if (!empty($status)) {
                        $sLower = strtolower($status);
                        if ($sLower === 'accepted') {
                            $statusBadgeClass = 'badge-green';
                            $statusText = 'Accepted';
                        } elseif ($sLower === 'rejected') {
                            $statusBadgeClass = 'badge-purple'; 
                            $statusText = 'Rejected';
                        } else {
                            $statusText = ucfirst($sLower);
                        }
                    }


// Card with onclick to toggle description
                    echo '<div class="job-card" onclick="toggleDesc(\'' . $uniqueId . '\', null)" style="cursor:pointer;">';
                    
                    // Header
                    echo '  <div class="card-header">';
                    echo '      <div class="header-left">';
                    if (!empty($profile_image)) {
                         echo '          <div class="company-logo-placeholder" style="background-image: url(\'../'.htmlspecialchars($profile_image).'\'); background-size: cover; background-position: center;"></div>';
                    } else {
                         echo '          <div class="company-logo-placeholder">' . $logoLetter . '</div>';
                    }
                    echo '          <div class="title-info">';
                    echo '             <h3 class="job-title">' . htmlspecialchars($job_title) . '</h3>';
                    echo '             <p class="company">' . htmlspecialchars($company_name) . '</p>';
                    echo '          </div>';
                    echo '      </div>';
                    echo '  </div>';

                    // Tags / Meta Row
                    echo '  <div class="tags-row">';
                    echo '      <span class="badge ' . $statusBadgeClass . '">' . htmlspecialchars($statusText) . '</span>';
                    if (!empty($expected_salary)) {
                        echo '      <span class="badge badge-purple">Exp: ' . htmlspecialchars($expected_salary) . '</span>';
                    }
                    echo '  </div>';

                    // Content - Cover Letter Snippet
                    if (!empty($cover)) {
                        $shortCover = (strlen($cover) > 100) ? substr($cover, 0, 100) . '...' : $cover;
                        $fullCover = $cover;
                        
                        echo '  <div class="job-desc" style="font-style: italic; color:#666; margin-bottom: 10px;">';
                        echo '      <span id="short-' . $uniqueId . '">"' . htmlspecialchars($shortCover) . '"</span>';
                        echo '      <span id="full-' . $uniqueId . '" style="display:none;">"' . nl2br(htmlspecialchars($fullCover)) . '"</span>';
                        if (strlen($cover) > 100) {
                            echo '  <button style="background:none; border:none; color:var(--accent-teal); cursor:pointer; font-size:0.85rem; font-weight:bold; margin-left:5px; z-index:2; position:relative; padding:0;">Read more</button>';
                        }
                        echo '  </div>';
                    } else {
                        echo '  <p class="job-desc" style="color:#aaa;">No cover letter provided.</p>';
                    }

                    // Footer
                    echo '  <div class="card-footer">';
                    echo '      <div class="posted-date"><i class="fa-regular fa-clock"></i> Applied on ' . htmlspecialchars($applied_at) . '</div>';
                    // Optional: Link to original job if needed
                    // echo '      <a href="job_detail.php?id=' . $job_id_ref . '" style="z-index:2; text-decoration:underline; font-size:0.85rem;" onclick="event.stopPropagation()">View Job</a>';
                    echo '  </div>';


echo '</div>'; // End card
                }
            }
        }
        ?>
      </div>
    </main>
  </div>
</section>
</body>
</html>
            