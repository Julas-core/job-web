
<?php
session_start();
include "backendwithphp/db_conection.php";

$isLoggedIn = false;
$userName = "User";
$dashboardLink = "#";

if (isset($_SESSION['user_id'])) {
    $isLoggedIn = true;
    $userId = $_SESSION['user_id'];
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

    if ($role == 'company' || $role == 'employer') {
        $stmt = $conect->prepare("SELECT company_name FROM company WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $userName = $row['company_name'];
        }
        $dashboardLink = "Users/employer_dashboard.html";
        $stmt->close();
    } else {
        // Assume seeker
        $stmt = $conect->prepare("SELECT fullname FROM job_seeker WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $userName = $row['fullname'];
        }
        $dashboardLink = "Users/seeker_dashboard.html";
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobLaunch | Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="page-wrap">
    <?php $basePath = ''; include dirname(__FILE__) . '/partials/header.php'; ?>

    <main class="container">
        <section class="hero"> 
            <h1>Apply <span class="accent-purple">Here</span>,<br>Find Your Future Job!</h1>
            <p class="hero-copy">Search thousands of roles, connect with top companies, and take the next step in your career.</p>
            <form action="index.php" method="GET" class="search-row">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                                <input class="search-input" type="text" name="search" placeholder="Job Title" aria-label="Search jobs by title" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                <button type="submit" class="btn hero-btn">Find Job</button>
            </form>
        </section>

        <!-- New Job Listings Section -->
        <section class="job-listings" style="margin-top: 50px;">
            <h2 style="text-align: center; margin-bottom: 30px;">Latest Job Openings</h2>
            <div class="job-grid">
                <?php
                // Fetch jobs with company info and applicant count
                $search = isset($_GET['search']) ? $_GET['search'] : '';
                $sql = "SELECT j.*, c.company_name, c.profile_image, 
                        (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.job_id) as applicant_count 
                        FROM jobs j 
                        LEFT JOIN company c ON j.company_id = c.company_id";
                
                if (!empty($search)) {
                    $sql .= " WHERE j.title LIKE ? OR j.descriptions LIKE ? OR c.company_name LIKE ?";
                }
                
                $sql .= " ORDER BY j.created_at DESC";

                if (!empty($search)) {
                    $searchTerm = "%" . $search . "%";
                    $stmt = $conect->prepare($sql);
                    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
                    $stmt->execute();
                    $result = $stmt->get_result();
                } else {
                    $result = $conect->query($sql);
                }


if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $compName = !empty($row['company_name']) ? htmlspecialchars($row['company_name']) : 'Confidential';
                        $profileImage = !empty($row['profile_image']) ? $row['profile_image'] : '';
                        $jobTitle = htmlspecialchars($row['title'] ?? 'Untitled Role');
                        $location = htmlspecialchars($row['location'] ?? $row['locatons'] ?? 'Remote');
                        $type = htmlspecialchars($row['type'] ?? $row['job_type'] ?? 'Full-Time');
                        $salary = htmlspecialchars($row['salary'] ?? 'Negotiable');
                        $applicants = $row['applicant_count'] ?? 0;
                        
                        $rawDescription = $row['description'] ?? $row['descriptions'] ?? '';
                        $trimmedDescription = trim($rawDescription);
                        $descSnippet = $trimmedDescription !== ''
                            ? htmlspecialchars(strlen($trimmedDescription) > 100 ? substr($trimmedDescription, 0, 100) . '...' : $trimmedDescription)
                            : 'No description available.';
                            
                        $jobId = isset($row['job_id']) ? $row['job_id'] : (isset($row['id']) ? $row['id'] : 0);
                        
                        // Calculate "Posted X days ago"
                        $createdAt = new DateTime($row['created_at']);
                        $now = new DateTime();
                        $interval = $now->diff($createdAt);
                        $daysAgo = $interval->days;
                        $postedText = ($daysAgo == 0) ? "Today" : (($daysAgo == 1) ? "1 day ago" : "$daysAgo days ago");

                        // Colors for badges based on type
                        $typeClass = 'badge-green';
                        if (stripos($type, 'Part') !== false) $typeClass = 'badge-orange';
                        if (stripos($type, 'Intern') !== false) $typeClass = 'badge-purple';

                        // Default logo letter
                        $logoLetter = strtoupper(substr($compName, 0, 1));

                        echo '<div class="job-card">';
                        
                        // Header
                        echo '  <div class="card-header">';
                        echo '      <div class="header-left">';
                        if (!empty($profileImage)) {
                            echo '          <div class="company-logo-placeholder" style="background-image: url(\''.htmlspecialchars($profileImage).'\'); background-size: cover; background-position: center;"></div>';
                        } else {
                            echo '          <div class="company-logo-placeholder">' . $logoLetter . '</div>';
                        }
                        echo '          <div class="title-info">';
                        echo '             <h3 class="job-title">' . $jobTitle . '</h3>';
                        echo '             <p class="company">' . $compName . ' <span class="dot">•</span> ' . $applicants . ' Applicants</p>';
                        echo '          </div>';
                        echo '      </div>';
                        echo '      <button class="favorite-btn"><i class="fa-regular fa-heart"></i></button>';
                        echo '  </div>';


// Tags
                        echo '  <div class="tags-row">';
                        // echo '      <span class="badge badge-purple">Intermediate</span>'; // hardcoded removed
                        echo '      <span class="badge ' . $typeClass . '">' . $type . '</span>';
                        
                        if (stripos($location, 'Remote') !== false) {
                            echo '<span class="badge badge-orange">Remote</span>';
                        } else {
                           echo '<span class="badge badge-outline">' . $location . '</span>';
                        }
                        echo '  </div>';

                        // Description
                        echo '  <p class="job-desc">' . $descSnippet . '</p>';

                        // Footer
                        echo '  <div class="card-footer">';
                        echo '      <span class="salary">' . $salary . '/hr</span>'; // Assuming hourly as per image, or just raw salary
                        echo '      <div class="posted-date"><i class="fa-regular fa-clock"></i> ' . $postedText . '</div>';
                        echo '  </div>';
                        
                        echo '  <a href="Users/job_detail.php?id=' . $jobId . '" class="stretched-link"></a>'; // Clickable card
                        echo '</div>';
                    }
                } else {
                    echo '<p style="text-align:center; grid-column: 1/-1;">No jobs posted yet.</p>';
                }
                ?>
            </div>
        </section>
    </main>
</body>
</html>