<?php
$pageTitle = 'Жазылу';
$activePage = 'booking';

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$userId = (int)$_SESSION['user_id'];

$error = '';
$success = '';

// CSRF токенін дайындау
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// HTML-ге қауіпсіз шығару
function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// POST мәндерінің жол екенін тексеру
function postString(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

// Белсенді қызметтерді алу
$servicesStmt = $pdo->query("
    SELECT id, service_name
    FROM services
    WHERE is_active = 1
    ORDER BY service_name ASC
");
$serviceRows = $servicesStmt->fetchAll();

// GET арқылы келген қызмет атауы
$selectedService = isset($_GET['service']) && is_string($_GET['service'])
    ? trim($_GET['service'])
    : '';

// Қолжетімді уақыттар
$validTimes = [
    '09:00',
    '10:00',
    '11:00',
    '12:00',
    '14:00',
    '15:00',
    '16:00',
    '17:00'
];

// Форманы өңдеу
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF токенін тексеру
    $postedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($postedToken) ||
        !hash_equals($csrfToken, $postedToken)
    ) {
        http_response_code(403);
        exit('Қауіпсіздік тексерісінен өту мүмкін болмады. Бетті жаңартып көріңіз.');
    }

    $make = postString('car_make');
    $model = postString('car_model');
    $date = postString('date');
    $time = postString('time');
    $notes = postString('notes');

    $serviceId = filter_var(
        $_POST['service_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    // Күннің нақты күнтізбелік дата екенін тексеру
    $dateObject = null;

    if ($date !== '') {
        $dateObject = DateTime::createFromFormat('!Y-m-d', $date);

        $dateErrors = DateTime::getLastErrors();

        $validDate = $dateObject !== false
            && ($dateErrors === false
                || (
                    $dateErrors['warning_count'] === 0
                    && $dateErrors['error_count'] === 0
                ))
            && $dateObject->format('Y-m-d') === $date;
    } else {
        $validDate = false;
    }

    // Міндетті өрістер
    if (
        $make === '' ||
        $model === '' ||
        !$serviceId ||
        $date === '' ||
        $time === ''
    ) {
        $error = 'Барлық міндетті өрісті толтырыңыз.';

    } elseif (
        mb_strlen($make) > 80 ||
        mb_strlen($model) > 80
    ) {
        $error = 'Көлік маркасы мен моделі 80 таңбадан аспауы керек.';

    } elseif (!$validDate || $date < date('Y-m-d')) {
        $error = 'Күнді дұрыс таңдаңыз. Өткен немесе жарамсыз күнге жазылуға болмайды.';

    } elseif (!in_array($time, $validTimes, true)) {
        $error = 'Уақытты ұсынылған тізімнен таңдаңыз.';

    } elseif (mb_strlen($notes) > 1000) {
        $error = 'Қосымша түсініктеме 1000 таңбадан аспауы керек.';

    } else {
        // Қызметтің бар және белсенді екенін тексеру
        $serviceCheck = $pdo->prepare("
            SELECT id
            FROM services
            WHERE id = :service_id
              AND is_active = 1
        ");

        $serviceCheck->execute([
            ':service_id' => $serviceId
        ]);

        if (!$serviceCheck->fetch()) {
            $error = 'Таңдалған қызмет табылмады немесе қазір қолжетімсіз.';

        } else {
            try {
                $pdo->beginTransaction();

                // Көлікті дерекқорға қосу
                $vehicleStmt = $pdo->prepare("
                    INSERT INTO vehicles (user_id, make, model)
                    VALUES (:user_id, :make, :model)
                ");

                $vehicleStmt->execute([
                    ':user_id' => $userId,
                    ':make' => $make,
                    ':model' => $model
                ]);

                $vehicleId = (int)$pdo->lastInsertId();

                // Жазылуды дерекқорға қосу
                $appointmentStmt = $pdo->prepare("
                    INSERT INTO appointments (
                        user_id,
                        vehicle_id,
                        service_id,
                        appointment_date,
                        appointment_time,
                        notes
                    )
                    VALUES (
                        :user_id,
                        :vehicle_id,
                        :service_id,
                        :appointment_date,
                        :appointment_time,
                        :notes
                    )
                ");

                $appointmentStmt->execute([
                    ':user_id' => $userId,
                    ':vehicle_id' => $vehicleId,
                    ':service_id' => $serviceId,
                    ':appointment_date' => $date,
                    ':appointment_time' => $time,
                    ':notes' => $notes !== '' ? $notes : null
                ]);

                $appointmentId = (int)$pdo->lastInsertId();

                $pdo->commit();

                $success = 'Өтінім сәтті сақталды! Жазылу нөмірі: ' . $appointmentId;

                // Сәтті жіберілген форманы тазалау
                $_POST = [];

                // Қайта жіберілген формаға ескі токен қолданылмасын
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $csrfToken = $_SESSION['csrf_token'];

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log($e->getMessage());
                $error = 'Өтінімді сақтау кезінде қате пайда болды. Қайталап көріңіз.';
            }
        }
    }
}

// Формадағы қызметті таңдау
$postedServiceId = filter_var(
    $_POST['service_id'] ?? null,
    FILTER_VALIDATE_INT
);

require_once __DIR__ . '/includes/header.php';
?>

<main class="page-main">

<section class="page-hero compact-hero">
    <div class="eyebrow">AUTOCARE / ОНЛАЙН ЖАЗЫЛУ</div>
    <h1>Қызметке <span>жазылыңыз.</span></h1>
    <p>Көлігіңіз туралы мәліметті енгізіп, қызмет пен ыңғайлы уақытты таңдаңыз.</p>
</section>

<section class="booking-layout">

    <form class="form-panel" method="POST" action="booking.php">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= h($csrfToken) ?>"
        >

        <div class="form-heading">
            <div>
                <span class="step-label">01 / МӘЛІМЕТТЕР</span>
                <h2>Жазылу формасы</h2>
            </div>
            <span class="form-badge">● Онлайн өтінім</span>
        </div>

        <?php if ($error): ?>
            <div class="notice"><?= h($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="notice"><?= h($success) ?></div>
        <?php endif; ?>

        <div class="form-grid">

            <label>
                Көлік маркасы
                <input
                    name="car_make"
                    type="text"
                    placeholder="Toyota, Hyundai..."
                    required
                    maxlength="80"
                    value="<?= h($_POST['car_make'] ?? '') ?>"
                >
            </label>

            <label>
                Көлік моделі
                <input
                    name="car_model"
                    type="text"
                    placeholder="Camry, Elantra..."
                    required
                    maxlength="80"
                    value="<?= h($_POST['car_model'] ?? '') ?>"
                >
            </label>

            <label class="wide-field">
                Қажетті қызмет

                <select name="service_id" required>
                    <option value="">Қызметті таңдаңыз</option>

                    <?php foreach ($serviceRows as $row): ?>
                        <option
                            value="<?= (int)$row['id'] ?>"
                            <?= (
                                $postedServiceId
                                    ? (int)$postedServiceId === (int)$row['id']
                                    : $selectedService === $row['service_name']
                            ) ? 'selected' : '' ?>
                        >
                            <?= h($row['service_name']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </label>

            <label>
                Күні
                <input
                    name="date"
                    type="date"
                    min="<?= date('Y-m-d') ?>"
                    required
                    value="<?= h($_POST['date'] ?? '') ?>"
                >
            </label>

            <label>
                Уақыты

                <select name="time" required>
                    <option value="">Уақытты таңдаңыз</option>

                    <?php foreach ($validTimes as $t): ?>
                        <option
                            value="<?= h($t) ?>"
                            <?= (($_POST['time'] ?? '') === $t) ? 'selected' : '' ?>
                        >
                            <?= h($t) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </label>

            <label class="wide-field">
                Қосымша түсініктеме
                <span class="optional">(міндетті емес)</span>

                <textarea
                    name="notes"
                    rows="3"
                    maxlength="1000"
                    placeholder="Көліктегі мәселені қысқаша сипаттаңыз..."
                ><?= h($_POST['notes'] ?? '') ?></textarea>
            </label>

        </div>

        <label class="check-row">
            <input type="checkbox" required>
            <span>Енгізілген мәліметтердің дұрыстығын растаймын.</span>
        </label>

        <button class="button button-primary full-button" type="submit">
            Өтінімді жіберу <span>→</span>
        </button>

    </form>

    <aside class="booking-aside">

        <div class="aside-dark">
            <span class="aside-icon">◷</span>
            <span class="step-label">БІЗДІҢ ЖҰМЫС УАҚЫТЫ</span>

            <h3>Көлігіңізге<br>уақыт табамыз.</h3>

            <p>
                Қызмет көрсету уақытын алдын ала таңдап,
                кезекте күтуді азайтыңыз.
            </p>

            <div class="hours-row">
                <span>Дүйсенбі – Жұма</span>
                <strong>09:00 – 18:00</strong>
            </div>

            <div class="hours-row">
                <span>Сенбі</span>
                <strong>10:00 – 16:00</strong>
            </div>

            <div class="hours-row">
                <span>Жексенбі</span>
                <strong>Демалыс</strong>
            </div>
        </div>

        <div class="aside-contact">
            <span class="contact-symbol">✆</span>

            <div>
                <small>СҰРАҚТАРЫҢЫЗ БАР МА?</small>
                <strong>+7 (700) 123-45-67</strong>
                <p>Оқу жобасының демо нөмірі</p>
            </div>
        </div>

    </aside>

</section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
