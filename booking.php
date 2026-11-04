<?php
$pageTitle = 'Жазылу';
$activePage = 'booking';
require_once __DIR__ . '/config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$error = '';
$success = '';
$servicesStmt = $pdo->query('SELECT id, service_name FROM services WHERE is_active = 1 ORDER BY id');
$serviceRows = $servicesStmt->fetchAll();
$selectedService = trim($_GET['service'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $make = trim($_POST['car_make'] ?? '');
    $model = trim($_POST['car_model'] ?? '');
    $serviceId = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT);
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $validTimes = ['09:00','10:00','11:00','12:00','14:00','15:00','16:00','17:00'];

    if ($make === '' || $model === '' || !$serviceId || $date === '' || $time === '') {
        $error = 'Барлық міндетті өрісті толтырыңыз.';
    } elseif (!in_array($time, $validTimes, true)) {
        $error = 'Уақытты тізімнен таңдаңыз.';
    } elseif ($date < date('Y-m-d')) {
        $error = 'Өткен күнге жазылуға болмайды.';
    } else {
        $serviceCheck = $pdo->prepare('SELECT id FROM services WHERE id = ? AND is_active = 1');
        $serviceCheck->execute([$serviceId]);
        if (!$serviceCheck->fetch()) {
            $error = 'Қызмет дұрыс таңдалмаған.';
        } else {
            try {
                $pdo->beginTransaction();
                $vehicle = $pdo->prepare('INSERT INTO vehicles (user_id, make, model) VALUES (?, ?, ?)');
                $vehicle->execute([$_SESSION['user_id'], $make, $model]);
                $vehicleId = (int)$pdo->lastInsertId();
                $appointment = $pdo->prepare('INSERT INTO appointments (user_id, vehicle_id, service_id, appointment_date, appointment_time, notes) VALUES (?, ?, ?, ?, ?, ?)');
                $appointment->execute([$_SESSION['user_id'], $vehicleId, $serviceId, $date, $time, $notes !== '' ? $notes : null]);
                $pdo->commit();
                $success = 'Өтінім сәтті сақталды! Жазылу нөмірі: ' . $pdo->lastInsertId();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log($e->getMessage());
                $error = 'Өтінімді сақтау кезінде қате пайда болды.';
            }
        }
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<main class="page-main">
<section class="page-hero compact-hero"><div class="eyebrow">AUTOCARE / ОНЛАЙН ЖАЗЫЛУ</div><h1>Қызметке <span>жазылыңыз.</span></h1><p>Көлігіңіз туралы мәліметті енгізіп, қызмет пен ыңғайлы уақытты таңдаңыз.</p></section>
<section class="booking-layout">
  <form class="form-panel" method="POST" action="booking.php">
    <div class="form-heading"><div><span class="step-label">01 / МӘЛІМЕТТЕР</span><h2>Жазылу формасы</h2></div><span class="form-badge">● Онлайн өтінім</span></div>
    <?php if ($error): ?><div class="notice"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if ($success): ?><div class="notice"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <div class="form-grid">
      <label>Көлік маркасы<input name="car_make" type="text" placeholder="Toyota, Hyundai..." required maxlength="80" value="<?= htmlspecialchars($_POST['car_make'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
      <label>Көлік моделі<input name="car_model" type="text" placeholder="Camry, Elantra..." required maxlength="80" value="<?= htmlspecialchars($_POST['car_model'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
      <label class="wide-field">Қажетті қызмет<select name="service_id" required><option value="">Қызметті таңдаңыз</option>
        <?php foreach ($serviceRows as $row): ?>
          <option value="<?= (int)$row['id'] ?>" <?= (($selectedService === $row['service_name']) || ((string)($_POST['service_id'] ?? '') === (string)$row['id'])) ? 'selected' : '' ?>><?= htmlspecialchars($row['service_name'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select></label>
      <label>Күні<input name="date" type="date" min="<?= date('Y-m-d') ?>" required value="<?= htmlspecialchars($_POST['date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></label>
      <label>Уақыты<select name="time" required><option value="">Уақытты таңдаңыз</option><?php foreach (['09:00','10:00','11:00','12:00','14:00','15:00','16:00','17:00'] as $t): ?><option <?= (($_POST['time'] ?? '') === $t) ? 'selected' : '' ?>><?= $t ?></option><?php endforeach; ?></select></label>
      <label class="wide-field">Қосымша түсініктеме <span class="optional">(міндетті емес)</span><textarea name="notes" rows="3" maxlength="1000" placeholder="Көліктегі мәселені қысқаша сипаттаңыз..."><?= htmlspecialchars($_POST['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea></label>
    </div>
    <label class="check-row"><input type="checkbox" required> <span>Енгізілген мәліметтердің дұрыстығын растаймын.</span></label>
    <button class="button button-primary full-button" type="submit">Өтінімді жіберу <span>→</span></button>
  </form>
  <aside class="booking-aside"><div class="aside-dark"><span class="aside-icon">◷</span><span class="step-label">БІЗДІҢ ЖҰМЫС УАҚЫТЫ</span><h3>Көлігіңізге<br>уақыт табамыз.</h3><p>Қызмет көрсету уақытын алдын ала таңдап, кезекте күтуді азайтыңыз.</p><div class="hours-row"><span>Дүйсенбі – Жұма</span><strong>09:00 – 18:00</strong></div><div class="hours-row"><span>Сенбі</span><strong>10:00 – 16:00</strong></div><div class="hours-row"><span>Жексенбі</span><strong>Демалыс</strong></div></div><div class="aside-contact"><span class="contact-symbol">✆</span><div><small>СҰРАҚТАРЫҢЫЗ БАР МА?</small><strong>+7 (700) 123-45-67</strong><p>Оқу жобасының демо нөмірі</p></div></div></aside>
</section></main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
