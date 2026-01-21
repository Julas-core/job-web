<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobLaunch | Contact Us</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="page-wrap">
    <?php $basePath = ''; include __DIR__ . '/partials/header.php'; ?>

        <main class="contact-section">
                <h1 class="section-title">ContactUs</h1>
                <form class="contact-form contact-card" data-validate>
                        <div class="contact-grid">
                            <div class="contact-stack">
                                <label class="subtle" for="fullName">Full Name</label>
                                <input class="input" id="fullName" type="text" name="fullName" placeholder="Abel Hadush" aria-label="Full Name" required>

                                <label class="subtle" for="email">Email Address</label>
                                <input class="input" id="email" type="email" name="email" placeholder="you@example.com" aria-label="Email Address" required>

                                <label class="subtle" for="phone">Phone Number</label>
                                <input class="input" id="phone" type="tel" name="phone" placeholder="(+251) 900-000-000" aria-label="Phone Number">
                            </div>
                            <div class="contact-textarea">
                                <label class="subtle" for="message">Message</label>
                                <textarea class="input contact-message" id="message" name="message" placeholder="Tell us how we can help" aria-label="Your Message" required></textarea>
                            </div>
                        </div>
                        <div class="contact-actions">
                            <button class="btn contact-submit" type="submit">Submit</button>
                        </div>
                </form>
        </main>
    <script src="script.js"></script>
</body>
</html>