
<?php
session_start();
// Include db connection if needed for dynamic categories later
include('backendwithphp/db_conection.php');
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Categories</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Page specific background to match the image nebula look */
        .categories-bg {
            background: #0f0f0f;
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(0, 100, 200, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 60%, rgba(0, 100, 200, 0.1) 0%, transparent 40%);
            min-height: calc(100vh - 80px); /* Adjust based on header height */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }

        /* Custom icon colors */
        .icon-red { color: var(--accent-red); }
        .icon-blue { color: #00c7fa; }
        .icon-white { color: #fff; }
        .icon-green { color: var(--accent-green); }
        .icon-purple { color: var(--accent-purple); }
        .icon-teal { color: var(--accent-teal); }
    </style>
</head>
<body class="page-wrap">
    <?php $basePath = ''; include __DIR__ . '/partials/header.php'; ?>

    <main class="categories-bg">
        <section class="categories-section">
            <h1 class="section-title">Job <span class="highlight">Categories</span></h1>
            
            <div class="cards-grid">
                <!-- Software Development -->
                <div class="glass-card">
                    <div class="card-icon-box">
                        <i class="fa-solid fa-code icon-blue"></i>
                    </div>
                    <h3 class="card-title">Software Development</h3>
                    <p class="card-desc">Build software solutions, mobile apps, and web platforms using modern technologies.</p>
                </div>

                <!-- Marketing & Sales -->
                <div class="glass-card">
                    <div class="card-icon-box">
                        <i class="fa-solid fa-bullhorn icon-red"></i>
                    </div>
                    <h3 class="card-title">Marketing & Sales</h3>
                    <p class="card-desc">Drive growth through strategic marketing campaigns, sales initiatives, and brand management.</p>
                </div>

                <!-- Graphic & Design -->
                <div class="glass-card">
                    <div class="card-icon-box">
                        <i class="fa-solid fa-palette icon-purple"></i>
                    </div>
                    <h3 class="card-title">Graphic & Design</h3>
                    <p class="card-desc">Create visual concepts, UI/UX designs, and artistic content for digital and print media.</p>
                </div>

                <!-- Customer Service -->
                <div class="glass-card">
                    <div class="card-icon-box">
                        <i class="fa-solid fa-headset icon-green"></i>
                    </div>
                    <h3 class="card-title">Customer Service</h3>
                    <p class="card-desc">Provide support to customers, resolve issues, and ensure a positive user experience.</p>
                </div>

                <!-- Data Science -->
                <div class="glass-card">
                    <div class="card-icon-box">
                        <i class="fa-solid fa-database icon-teal"></i>
                    </div>
                    <h3 class="card-title">Data Science</h3>
                    <p class="card-desc">Analyze complex data sets to uncover trends, insights, and drive data-driven decision making.</p>
                </div>


<!-- Human Resources -->
                <div class="glass-card">
                    <div class="card-icon-box">
                        <i class="fa-solid fa-users icon-white"></i>
                    </div>
                    <h3 class="card-title">Human Resources</h3>
                    <p class="card-desc">Manage recruitment, employee relations, benefits, and organizational culture.</p>
                </div>

                <!-- Finance -->
                <div class="glass-card">
                    <div class="card-icon-box">
                        <i class="fa-solid fa-chart-line icon-green"></i>
                    </div>
                    <h3 class="card-title">Finance</h3>
                    <p class="card-desc">Oversee financial planning, accounting, investment strategies, and fiscal reporting.</p>
                </div>

                <!-- Engineering -->
                <div class="glass-card">
                    <div class="card-icon-box">
                        <i class="fa-solid fa-gears icon-red"></i>
                    </div>
                    <h3 class="card-title">Engineering</h3>
                    <p class="card-desc">Design and build complex systems, machinery, and infrastructure across various disciplines.</p>
                </div>

            </div>
        </section>
    </main>

</body>
</html>