
<?php
$pageTitle = 'Жазылуды өңдеу';
$activePage = 'appointments';

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$userId = (int)$_SESSION['user_id'];
$error = '';

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(400);
    exit('Жазылу нөмірі дұрыс емес.');
}

// Тек осы пайдаланушыға тиесілі жазылуды алу
$findStmt = $pdo->prepare("
    SELECT a.*, v.make, v.model, v.plate_number
    FROM appointments a
    JOIN vehicles v ON a.vehicle_id = v.id
    WHERE a.id = ? AND a.user_id = ?
");
$findStmt->execute([$id, $userId]);
$appointment = $findStmt->fetch();

if (!$appointment) {
    http_response_code(404);
    exit('Жазылу табылмады немесе оған рұқсатыңыз жоқ.');
}

// Белсенді қызметтер тізімі
$servicesStmt = $pdo->query("
    SELECT id, service_name
    FROM services
    WHERE is_active = 1
    ORDER BY service_name
");
$services = $servicesStmt->fetchAll();

$validTimes = [
    '09:00', '10:00', '11:00', '12:00',
    '14:00', '15:00', '16:00', '17:00'
];

// Форманы өңдеу
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $make = trim($_POST['make'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $plate = trim($_POST['plate_number'] ?? '');
    $serviceId = filter_var($_POST['service_id'] ?? null, FILTER_VALIDATE_INT);
    $date = $_POST['appointment_date'] ?? '';
    $time = $_POST['appointment_time'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if ($make === '' || $model === '' || !$serviceId || $date === '' || $time === '') {
        $error = 'Барлық міндетті өрісті толтырыңыз.';
    } elseif (strlen($make) > 80 || strlen($model) > 80 || strlen($plate) > 20) {
        $error = 'Көлік туралы енгізілген мәлімет тым ұзын.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date < date('Y-m-d')) {
        $error = 'Күнді дұрыс таңдаңыз. Өткен күнге жазылуға болмайды.';
    } elseif (!in_array($time, $validTimes, true)) {
        $error = 'Уақытты тізімнен таңдаңыз.';
    } elseif (mb_strlen($notes) > 1000) {
        $error = 'Түсініктеме 1000 таңбадан аспауы керек.';
    } else {
        $serviceCheck = $pdo->prepare(
            'SELECT id FROM services WHERE id = ? AND is_active = 1'
        );
        $serviceCheck->execute([$serviceId]);

        if (!$serviceCheck->fetch()) {
            $error = 'Қызмет дұрыс таңдалмаған.';
        } else {
            try {
                $pdo->beginTransaction();

                // Көлік мәліметтерін жаңарту
                $vehicleStmt = $pdo->prepare("
                    UPDATE vehicles
                    SET make = ?, model = ?, plate_number = ?
                    WHERE id = ? AND user_id = ?
                ");

                $vehicleStmt->execute([
                    $make,
                    $model,
                    $plate !== '' ? $plate : null,
                    $appointment['vehicle_id'],
                    $userId
                ]);

                // Жазылуды жаңарту
                $updateStmt = $pdo->prepare("
                    UPDATE appointments
                    SET service_id = ?,
                        appointment_date = ?,
                        appointment_time = ?,
                        notes = ?
                    WHERE id = ? AND user_id = ?
                ");

                $updateStmt->execute([
                    $serviceId,
                    $date,
                    $time,
                    $notes !== '' ? $notes : null,
                    $id,
                    $userId
                ]);

                $pdo->commit();

                header('Location: appointments.php');
                exit;

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log($e->getMessage());
                $error = 'Жазылуды жаңарту кезінде қате пайда болды.';
            }
        }
    }

    // Қате болса, енгізілген мәліметтерді формада сақтау
    $appointment['make'] = $make;
    $appointment['model'] = $model;
    $appointment['plate_number'] = $plate;
    $appointment['service_id'] = $serviceId;
    $appointment['appointment_date'] = $date;
    $appointment['appointment_time'] = $time;
    $appointment['notes'] = $notes;
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="page-main">

    <section class="page-hero compact-hero">
        <div class="eyebrow">AUTOCARE / ЖАЗЫЛУДЫ ӨҢДЕУ</div>
        <h1>Мәліметтерді <span>өзгерту.</span></h1>
        <p>Жазылу мәліметтерін тексеріп, қажетті өзгерістерді енгізіңіз.</p>
    </section>

    <section class="booking-layout">

        <form class="form-panel" method="POST" action="edit_appointment.php">

            <input type="hidden" name="id" value="<?= (int)$id ?>">

            <?php if ($error): ?>
                <div class="notice"><?= h($error) ?></div>
            <?php endif; ?>

            <div class="form-heading">
                <div>
                    <span class="step-label">EDIT / МӘЛІМЕТТЕР</span>
                    <h2>Жазылуды өңдеу</h2>
                </div>
            </div>

            <div class="form-grid">

                <label>
                    Көлік маркасы
                    <input
                        type="text"
                        name="make"
                        maxlength="80"
                        required
                        value="<?= h($appointment['make']) ?>"
                    >
                </label>

                <label>
                    Көлік моделі
                    <input
                        type="text"
                        name="model"
                        maxlength="80"
                        required
                        value="<?= h($appointment['model']) ?>"
                    >
                </label>

                <label class="wide-field">
                    Мемлекеттік нөмірі
                    <input
                        type="text"
                        name="plate_number"
                        maxlength="20"
                        value="<?= h($appointment['plate_number'] ?? '') ?>"
                        placeholder="Мысалы: 123ABC02"
                    >
                </label>

                <label class="wide-field">
                    Қызмет түрі
                    <select name="service_id" required>
                        <?php foreach ($services as $service): ?>
                            <option
                                value="<?= (int)$service['id'] ?>"
                                <?= (int)$appointment['service_id'] === (int)$service['id'] ? 'selected' : '' ?>
                            >
                                <?= h($service['service_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Жазылу күні
                    <input
                        type="date"
                        name="appointment_date"
                        min="<?= date('Y-m-d') ?>"
                        required
                        value="<?= h($appointment['appointment_date']) ?>"
                    >
                </label>

                <label>
                    Уақыты
                    <select name="appointment_time" required>
                        <?php foreach ($validTimes as $time): ?>
                            <option
                                value="<?= h($time) ?>"
                                <?= substr($appointment['appointment_time'], 0, 5) === $time ? 'selected' : '' ?>
                            >
                                <?= h($time) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="wide-field">
                    Қосымша түсініктеме
                    <textarea name="notes" rows="4" maxlength="1000"><?= h($appointment['notes'] ?? '') ?></textarea>
                </label>

            </div>

            <button class="button button-primary full-button" type="submit">
                Өзгерістерді сақтау →
            </button>

            <a class="button button-outline full-button"
               href="appointments.php"
               style="margin-top:10px;">
                Артқа қайту
            </a>

        </form>

    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
