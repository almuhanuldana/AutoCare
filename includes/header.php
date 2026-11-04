
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? 'AutoCare';
$activePage = $activePage ?? '';
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!doctype html>
<html lang="kk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | AutoCare</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/app.js" defer></script>
</head>
<body>

<header class="site-header">
    <a class="brand" href="index.php">
        <span class="brand-mark">A</span>
        <span>
            Auto<span class="brand-accent">Care</span>
            <small>АВТОСЕРВИС ОРТАЛЫҒЫ</small>
        </span>
    </a>

    <button class="menu-toggle"
            aria-label="Мәзірді ашу"
            aria-expanded="false">☰</button>

    <nav class="main-nav">
        <a class="<?= $activePage === 'home' ? 'active' : '' ?>"
           href="index.php">Басты бет</a>

        <a class="<?= $activePage === 'services' ? 'active' : '' ?>"
           href="services.php">Қызметтер</a>

        <a class="<?= $activePage === 'booking' ? 'active' : '' ?>"
           href="booking.php">Жазылу</a>

        <?php if ($isLoggedIn): ?>
            <a href="logout.php">Шығу</a>
        <?php else: ?>
            <a class="<?= $activePage === 'login' ? 'active' : '' ?>"
               href="login.php">Кіру</a>

            <a class="nav-cta <?= $activePage === 'register' ? 'active-cta' : '' ?>"
               href="register.php">
                Тіркелу <span>↗</span>
            </a>
        <?php endif; ?>
    </nav>
</header>
