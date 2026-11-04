<?php
$pageTitle = 'Тіркелу';
$activePage = 'register';
require_once __DIR__ . '/config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $phone === '' || $password === '') {
        $error = 'Барлық өрісті толтырыңыз.';
    } elseif (mb_strlen($name) < 3) {
        $error = 'Аты-жөні кемінде 3 таңбадан тұруы керек.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email мекенжайын дұрыс енгізіңіз.';
    } elseif (!preg_match('/^[+]?[0-9\s()\-]{10,18}$/', $phone)) {
        $error = 'Телефон нөмірінің форматын тексеріңіз.';
    } elseif (strlen($password) < 8) {
        $error = 'Құпиясөз кемінде 8 таңбадан тұруы керек.';
    } else {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'Бұл email бұрын тіркелген.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare('INSERT INTO users (full_name, email, phone, password_hash) VALUES (?, ?, ?, ?)');
            $insert->execute([$name, $email, $phone, $hash]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            header('Location: booking.php');
            exit;
        }
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<main class="auth-main">
  <div class="auth-decoration"><span class="auth-orbit orbit-one"></span><span class="auth-orbit orbit-two"></span><div class="auth-mark">A</div><p>AutoCare қауымдастығына<br><strong>қосылыңыз.</strong></p><span class="auth-small">ҚАРАПАЙЫМ · ЫҢҒАЙЛЫ · СЕНІМДІ</span></div>
  <section class="auth-panel">
    <span class="step-label">ЖАҢА АККАУНТ</span><h1>Тіркелу <span>формасы</span></h1>
    <p class="auth-subtitle">Жеке кабинет ашу үшін мәліметтерді енгізіңіз.</p>
    <?php if ($error): ?><div class="notice"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <form class="auth-form" method="POST" action="register.php">
      <label>Аты-жөніңіз<input type="text" name="name" placeholder="Аты-жөніңіз" required minlength="3" value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
      <label>Email мекенжайы<input type="email" name="email" placeholder="name@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
      <label>Телефон нөмірі<input type="tel" name="phone" placeholder="+7 7XX XXX XX XX" required pattern="[\+]?[0-9\s\-\(\)]{10,18}" value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
      <label>Құпиясөз<input type="password" name="password" placeholder="Кемінде 8 таңба" required minlength="8"></label>
      <button class="button button-primary full-button" type="submit">Аккаунт ашу <span>→</span></button>
    </form>
    <p class="auth-switch">Аккаунтыңыз бар ма? <a href="login.php">Жүйеге кіру</a></p>
    <a class="back-link" href="index.php">← Басты бетке оралу</a>
  </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
