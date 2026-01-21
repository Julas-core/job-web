<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobLaunch | About Us</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="page-wrap">
    <?php $basePath = ''; include __DIR__ . '/partials/header.php'; ?>

    <main class="container">
        <section class="about">
            <div>
                <div class="eyebrow">Who Are We?</div>
                <h2>Who Are <span>We?</span></h2>
                <p>We are founded by a team of passionate Full-Stack Developers in our third year of Computer Science, born out of a desire to bridge the gap between talent and opportunity. As developers, we don't just build platforms; we engineer solutions. Our youth is our strength—we bring the latest tech stacks and a fresh perspective to the evolving job market.</p>
            </div>
            <div class="about-figure">
                <img src="./assets/collabs.png" alt="Students collaborating">
            </div>
        </section>
    </main>
</body>
</html>