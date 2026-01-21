<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user_id']);
if (!isset($basePath)) {
    $basePath = '';
}
// Ensure proper prefix (no leading slash when basePath is empty)
$pfx = ($basePath === '') ? '' : rtrim($basePath, '/') . '/';

// Determine current page for active state if not set
if (!isset($currentPage)) {
    $currentPage = basename($_SERVER['PHP_SELF']);
}
?>
<header class="navbar">
    <div class="brand">JobLaunch</div>
    <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false"><span></span></button>
    <div class="nav-drawer">
        <div class="auth-links">
            <?php if ($isLoggedIn): ?>
                <?php
                $role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
                // 'company' redirects to post_job.php in login.php, so treating it as dashboard
                $dashLink = ($role === 'company' || $role === 'employer') ? 'Users/post_job.php' : 'Users/seeker_dashboard.php';
                ?>
                <a href="<?php echo $pfx . $dashLink; ?>">Dashboard</a>
                <span class="divider">|</span>
                <a href="<?php echo $pfx; ?>backendwithphp/logout.php">Logout</a>
            <?php else: ?>
                <a href="<?php echo $pfx; ?>login.html">Login</a>
                <span class="divider">|</span>
                <a href="<?php echo $pfx; ?>register.html">Register</a>
            <?php endif; ?>
        </div>
        <div class="nav-links">
            <a class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>" href="<?php echo $pfx; ?>index.php">Home</a>
            <a class="<?php echo ($currentPage == 'catagories.php') ? 'active' : ''; ?>" href="<?php echo $pfx; ?>catagories.php">Categories</a>
            <a class="<?php echo ($currentPage == 'about.php') ? 'active' : ''; ?>" href="<?php echo $pfx; ?>about.php">AboutUs</a>
            <a class="<?php echo ($currentPage == 'contact.php') ? 'active' : ''; ?>" href="<?php echo $pfx; ?>contact.php">ContactUs</a>
        </div>
    </div>
</header>
<script src="<?php echo $pfx; ?>assets/js/responsive.js" defer></script>