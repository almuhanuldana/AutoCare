<?php
$pageTitle = 'Жүйеге кіру';
$activePage = 'login';
require_once __DIR__ . '/config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Email мен құпиясөзді дұрыс енгізіңіз.';
    } else {
        $stmt = $pdo->prepare('SELECT id, full_name, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            header('Location: booking.php');
            exit;
        } else {
            $error = 'Email немесе құпиясөз қате.';
        }
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<main class="auth-main">
  <div class="auth-decoration"><span class="auth-orbit orbit-one"></span><span class="auth-orbit orbit-two"></span><div class="auth-mark">A</div><p>Көлігіңізге күтім жасау<br><strong>осы жерден басталады.</strong></p><span class="auth-small">AUTOCARE · SMART SERVICE</span></div>
  <section class="auth-panel">
    <span class="step-label">ҚАЙТА ҚОШ КЕЛДІҢІЗ</span><h1>Аккаунтқа <span>кіру</span></h1>
    <p class="auth-subtitle">Жазылуларыңызды басқару үшін жүйеге кіріңіз.</p>
    <?php if ($error): ?><div class="notice"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <form class="auth-form" method="POST" action="login.php">
      <label>Email мекенжайы<input type="email" name="email" placeholder="name@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
      <label>Құпиясөз<input type="password" name="password" placeholder="Құпиясөзіңіз" required></label>
      <button class="button button-primary full-button" type="submit">Кіру <span>→</span></button>
    </form>
    <p class="auth-switch">Аккаунтыңыз жоқ па? <a href="register.php">Тіркелу</a></p>
    <a class="back-link" href="index.php">← Басты бетке оралу</a>
  </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
