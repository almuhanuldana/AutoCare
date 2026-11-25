<?php
$pageTitle = 'Менің жазылуларым';
$activePage = 'appointments';

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

$userId = (int) $_SESSION['user_id'];
$error = '';
$success = '';

// CSRF токенін жасау
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Күннің нақты дұрыстығын тексеру
function isValidDate($date) {
    if (!is_string($date) || $date === '') {
        return false;
    }

    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);

    return $dateObject !== false
        && $dateObject->format('Y-m-d') === $date;
}

// Жазылуды жою
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $postedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        $error = 'Қауіпсіздік тексерісінен өту мүмкін болмады. Бетті жаңартып көріңіз.';
    } else {
        $deleteId = filter_var($_POST['delete_id'], FILTER_VALIDATE_INT);

        if ($deleteId && $deleteId > 0) {
            $deleteStmt = $pdo->prepare(
                'DELETE FROM appointments WHERE id = ? AND user_id = ?'
            );
            $deleteStmt->execute([$deleteId, $userId]);

            if ($deleteStmt->rowCount() > 0) {
                $success = 'Жазылу сәтті жойылды.';
            } else {
                $error = 'Жазылу табылмады немесе оны жоюға рұқсатыңыз жоқ.';
            }
        } else {
            $error = 'Жазылу нөмірі дұрыс емес.';
        }
    }
}

// Іздеу және сүзгілер
$search = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$allowedCategories = ['all', 'diagnostic', 'maintenance', 'repair'];

if (!in_array($category, $allowedCategories, true)) {
    $category = 'all';
}

// Күндерді тексеру
if ($dateFrom !== '' && !isValidDate($dateFrom)) {
    $dateFrom = '';
    $error = 'Басталу күні дұрыс көрсетілмеген.';
}

if ($dateTo !== '' && !isValidDate($dateTo)) {
    $dateTo = '';
    $error = 'Аяқталу күні дұрыс көрсетілмеген.';
}

if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
    $dateFrom = '';
    $dateTo = '';
    $error = 'Басталу күні аяқталу күнінен кейін болмауы керек.';
}

// Сұрыптау
$sort = $_GET['sort'] ?? 'date_desc';

$allowedSorts = [
    'date_desc' => 'a.appointment_date DESC, a.appointment_time DESC',
    'date_asc' => 'a.appointment_date ASC, a.appointment_time ASC',
    'service_asc' => 's.service_name ASC',
    'status_asc' => 'a.status ASC',
    'newest' => 'a.created_at DESC'
];

if (!array_key_exists($sort, $allowedSorts)) {
    $sort = 'date_desc';
}

// SQL шарттары
$where = ' WHERE a.user_id = :user_id';
$params = [':user_id' => $userId];

if ($search !== '') {
    $where .= " AND (
        s.service_name LIKE :search_service
        OR v.make LIKE :search_make
        OR v.model LIKE :search_model
        OR v.plate_number LIKE :search_plate
    )";

    $searchValue = '%' . $search . '%';

    $params[':search_service'] = $searchValue;
    $params[':search_make'] = $searchValue;
    $params[':search_model'] = $searchValue;
    $params[':search_plate'] = $searchValue;
}

if ($category !== 'all') {
    $where .= ' AND s.category = :category';
    $params[':category'] = $category;
}

if ($dateFrom !== '') {
    $where .= ' AND a.appointment_date >= :date_from';
    $params[':date_from'] = $dateFrom;
}

if ($dateTo !== '') {
    $where .= ' AND a.appointment_date <= :date_to';
    $params[':date_to'] = $dateTo;
}

// Сәйкес жазылулар санын есептеу
$countSql = "
    SELECT COUNT(*)
    FROM appointments a
    JOIN vehicles v ON a.vehicle_id = v.id
    JOIN services s ON a.service_id = s.id
    $where
";

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalAppointments = (int) $countStmt->fetchColumn();

// Пагинация
$perPage = 5;
$totalPages = max(1, (int) ceil($totalAppointments / $perPage));

$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
$page = $page && $page > 0 ? $page : 1;
$page = min($page, $totalPages);

$offset = ($page - 1) * $perPage;

// Жазылуларды шығару
$sql = "
    SELECT
        a.id,
        a.appointment_date,
        a.appointment_time,
        a.notes,
        a.status,
        a.created_at,
        v.make,
        v.model,
        v.plate_number,
        s.service_name,
        s.category,
        s.base_price
    FROM appointments a
    JOIN vehicles v ON a.vehicle_id = v.id
    JOIN services s ON a.service_id = s.id
    $where
    ORDER BY {$allowedSorts[$sort]}
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $value) {
    $type = $key === ':user_id' ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key, $value, $type);
}

$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$appointments = $stmt->fetchAll();

// Бет ауысқанда сүзгілерді сақтау
function appointmentPageUrl($pageNumber) {
    $query = $_GET;
    $query['page'] = $pageNumber;

    return 'appointments.php?' . http_build_query($query);
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="page-main">

    <section class="page-hero compact-hero">
        <div class="eyebrow">AUTOCARE / ЖЕКЕ КАБИНЕТ</div>
        <h1>Менің <span>жазылуларым.</span></h1>
        <p>Жазылуларыңызды көріңіз, іздеңіз, өңдеңіз немесе қажет болмаса жойыңыз.</p>

        <a class="button button-primary" href="booking.php">
            Жаңа жазылу қосу →
        </a>
    </section>

    <section class="catalog-section">

        <?php if ($success): ?>
            <div class="notice"><?= h($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="notice"><?= h($error) ?></div>
        <?php endif; ?>

        <form class="catalog-toolbar" method="GET" action="appointments.php">

            <input
                type="search"
                name="search"
                placeholder="Қызмет немесе көлік атауы..."
                value="<?= h($search) ?>"
            >

            <select name="category">
                <option value="all">Барлық санат</option>
                <option value="diagnostic" <?= $category === 'diagnostic' ? 'selected' : '' ?>>Диагностика</option>
                <option value="maintenance" <?= $category === 'maintenance' ? 'selected' : '' ?>>Техникалық қызмет</option>
                <option value="repair" <?= $category === 'repair' ? 'selected' : '' ?>>Жөндеу</option>
            </select>

            <label>
                Басталу күні
                <input type="date" name="date_from" value="<?= h($dateFrom) ?>">
            </label>

            <label>
                Аяқталу күні
                <input type="date" name="date_to" value="<?= h($dateTo) ?>">
            </label>

            <select name="sort">
                <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Күні: жаңадан ескіге</option>
                <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Күні: ескіден жаңасына</option>
                <option value="service_asc" <?= $sort === 'service_asc' ? 'selected' : '' ?>>Қызмет атауы бойынша</option>
                <option value="status_asc" <?= $sort === 'status_asc' ? 'selected' : '' ?>>Мәртебесі бойынша</option>
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Жаңадан қосылған</option>
            </select>

            <button class="button button-primary" type="submit">
                Іздеу және сүзу
            </button>

            <a class="button button-outline" href="appointments.php">
                Тазалау
            </a>

        </form>

        <p class="result-count">
            Табылған жазылулар: <?= $totalAppointments ?>
        </p>

        <?php if ($appointments): ?>

            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:850px;">

                    <thead>
                        <tr style="background:#f1f6ff; text-align:left;">
                            <th style="padding:13px;">№</th>
                            <th style="padding:13px;">Көлік</th>
                            <th style="padding:13px;">Қызмет</th>
                            <th style="padding:13px;">Күні</th>
                            <th style="padding:13px;">Уақыты</th>
                            <th style="padding:13px;">Мәртебесі</th>
                            <th style="padding:13px;">Әрекет</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($appointments as $item): ?>
                        <tr style="border-bottom:1px solid #e2e8ee;">

                            <td style="padding:13px;">
                                <?= (int)$item['id'] ?>
                            </td>

                            <td style="padding:13px;">
                                <?= h($item['make'] . ' ' . $item['model']) ?>

                                <?php if ($item['plate_number']): ?>
                                    <br>
                                    <small><?= h($item['plate_number']) ?></small>
                                <?php endif; ?>
                            </td>

                            <td style="padding:13px;">
                                <?= h($item['service_name']) ?>
                            </td>

                            <td style="padding:13px;">
                                <?= h($item['appointment_date']) ?>
                            </td>

                            <td style="padding:13px;">
                                <?= h(substr($item['appointment_time'], 0, 5)) ?>
                            </td>

                            <td style="padding:13px;">
                                <?= h($item['status']) ?>
                            </td>

                            <td style="padding:13px;">

                                <a
                                    class="button button-outline"
                                    href="edit_appointment.php?id=<?= (int)$item['id'] ?>"
                                >
                                    Өңдеу
                                </a>

                                <form
                                    method="POST"
                                    action="appointments.php"
                                    onsubmit="return confirm('Осы жазылуды жойғыңыз келе ме?');"
                                    style="display:inline-block; margin-top:5px;"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= h($csrfToken) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="delete_id"
                                        value="<?= (int)$item['id'] ?>"
                                    >

                                    <button class="button button-outline" type="submit">
                                        Жою
                                    </button>
                                </form>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

        <?php else: ?>

            <div class="empty-state">
                Жазылулар табылмады. Сүзгілерді өзгертіп көріңіз немесе жаңа жазылу қосыңыз.
            </div>

        <?php endif; ?>

        <?php if ($totalPages > 1): ?>

            <nav class="pagination" aria-label="Жазылулар беттері">

                <?php if ($page > 1): ?>
                    <a href="<?= h(appointmentPageUrl($page - 1)) ?>">
                        ← Алдыңғы
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a
                        href="<?= h(appointmentPageUrl($i)) ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"
                    >
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= h(appointmentPageUrl($page + 1)) ?>">
                        Келесі →
                    </a>
                <?php endif; ?>

            </nav>

        <?php endif; ?>

        <div class="pagination-info">
            <?= $totalAppointments > 0
                ? (($offset + 1) . '–' . ($offset + count($appointments)) . ' / ' . $totalAppointments)
                : 'Көрсетілетін жазылу жоқ'
            ?>
        </div>

    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
