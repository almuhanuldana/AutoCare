
<?php
$pageTitle = 'Қызметтер';
$activePage = 'services';

require_once __DIR__ . '/config/database.php';

// 1. Іздеу және сүзгі параметрлері
$search = trim($_GET['search'] ?? '');

$category = $_GET['category'] ?? 'all';

$allowedCategories = [
    'all',
    'diagnostic',
    'maintenance',
    'repair'
];

if (!in_array($category, $allowedCategories, true)) {
    $category = 'all';
}

// 2. Сұрыптау параметрлері
$sort = $_GET['sort'] ?? 'newest';

$allowedSorts = [
    'newest' => 'id DESC',
    'oldest' => 'id ASC',
    'price_asc' => 'base_price ASC',
    'price_desc' => 'base_price DESC',
    'name_asc' => 'service_name ASC',
    'duration_asc' => 'estimated_minutes ASC'
];

if (!array_key_exists($sort, $allowedSorts)) {
    $sort = 'newest';
}

// 3. Іздеу және сүзгі үшін SQL шарттары
$where = " WHERE is_active = 1";
$params = [];

if ($search !== '') {
    $where .= " AND service_name LIKE ?";
    $params[] = '%' . $search . '%';
}

if ($category !== 'all') {
    $where .= " AND category = ?";
    $params[] = $category;
}

// 4. Барлық сәйкес қызметтер санын анықтау
$countSql = "SELECT COUNT(*) FROM services" . $where;

$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);

$totalServices = (int)$countStmt->fetchColumn();

// 5. Пагинация
$perPage = 6;

$totalPages = max(1, (int)ceil($totalServices / $perPage));

$page = filter_input(
    INPUT_GET,
    'page',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$page = $page ?: 1;

$page = min($page, $totalPages);

$offset = ($page - 1) * $perPage;

// 6. Қызметтерді сұрыптап, бет бойынша шығару
$sql = "SELECT * FROM services"
    . $where
    . " ORDER BY " . $allowedSorts[$sort]
    . " LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);

$index = 1;

foreach ($params as $param) {
    $stmt->bindValue($index++, $param, PDO::PARAM_STR);
}

$stmt->bindValue($index++, $perPage, PDO::PARAM_INT);
$stmt->bindValue($index, $offset, PDO::PARAM_INT);

$stmt->execute();

$services = $stmt->fetchAll();

// 7. Бет ауысқанда сүзгілерді сақтау
function paginationUrl($pageNumber)
{
    $query = $_GET;
    $query['page'] = $pageNumber;

    return 'services.php?' . http_build_query($query);
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="page-main">

    <section class="page-hero">
        <div class="eyebrow">AUTOCARE / ҚЫЗМЕТТЕР</div>

        <h1>Көлігіңізге қажет<br>
            <span>барлық қызмет бір жерде.</span>
        </h1>

        <p>
            Қызмет түрін таңдап, бағасымен танысыңыз.
            Нақты баға көліктің моделіне және жұмыс көлеміне байланысты анықталады.
        </p>
    </section>

    <section class="catalog-section">

        <!-- Іздеу, сүзгі және сұрыптау -->
        <form class="catalog-toolbar" method="GET" action="services.php">

            <div class="search-box">
                <span>⌕</span>

                <input
                    name="search"
                    type="search"
                    value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Қызмет атауын іздеу..."
                    aria-label="Қызмет іздеу"
                >
            </div>

            <select name="category" aria-label="Қызмет санаты">

                <option value="all" <?= $category === 'all' ? 'selected' : '' ?>>
                    Барлық санат
                </option>

                <option value="diagnostic" <?= $category === 'diagnostic' ? 'selected' : '' ?>>
                    Диагностика
                </option>

                <option value="maintenance" <?= $category === 'maintenance' ? 'selected' : '' ?>>
                    Техникалық қызмет
                </option>

                <option value="repair" <?= $category === 'repair' ? 'selected' : '' ?>>
                    Жөндеу
                </option>

            </select>

            <select name="sort" aria-label="Сұрыптау">

                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>
                    Жаңадан қосылған
                </option>

                <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>
                    Ескі қызметтер
                </option>

                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>
                    Бағасы: арзаннан қымбатқа
                </option>

                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>
                    Бағасы: қымбаттан арзанға
                </option>

                <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>
                    Атауы бойынша
                </option>

                <option value="duration_asc" <?= $sort === 'duration_asc' ? 'selected' : '' ?>>
                    Ұзақтығы бойынша
                </option>

            </select>

            <button class="button button-outline" type="submit">
                Қолдану
            </button>

            <a href="services.php" class="button button-outline">
                Тазалау
            </a>

        </form>

        <div class="result-count">
            Табылған қызметтер: <?= $totalServices ?>
        </div>

        <!-- Қызметтер тізімі -->
        <div class="service-grid catalog-grid" id="serviceGrid">

            <?php foreach ($services as $service): ?>

                <article class="service-card catalog-card">

                    <div class="service-card-top">

                        <div class="service-icon">⚙</div>

                        <span class="tag">
                            <?= htmlspecialchars(
                                mb_strtoupper($service['category']),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </span>

                    </div>

                    <h3>
                        <?= htmlspecialchars(
                            $service['service_name'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars(
                            $service['description'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                    <div class="service-meta">

                        <span>
                            ⏱ <?= (int)$service['estimated_minutes'] ?> минут
                        </span>

                        <strong>
                            <?= number_format(
                                (float)$service['base_price'],
                                0,
                                ',',
                                ' '
                            ) ?> ₸ бастап
                        </strong>

                    </div>

                    <a
                        class="button button-outline full-button"
                        href="booking.php?service=<?= urlencode($service['service_name']) ?>"
                    >
                        Қызметті таңдау <span>→</span>
                    </a>

                </article>

            <?php endforeach; ?>

        </div>

        <?php if (!$services): ?>

            <div class="empty-state">
                Іздеу бойынша қызмет табылмады.
                Басқа сөз енгізіп көріңіз.
            </div>

        <?php endif; ?>

        <!-- Пагинация -->
        <?php if ($totalPages > 1): ?>

            <nav class="pagination" aria-label="Қызметтер беттері">

                <?php if ($page > 1): ?>

                    <a href="<?= htmlspecialchars(paginationUrl($page - 1), ENT_QUOTES, 'UTF-8') ?>">
                        ← Алдыңғы
                    </a>

                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                    <a
                        href="<?= htmlspecialchars(paginationUrl($i), ENT_QUOTES, 'UTF-8') ?>"
                        class="<?= $i === $page ? 'active' : '' ?>"
                        <?= $i === $page ? 'aria-current="page"' : '' ?>
                    >
                        <?= $i ?>
                    </a>

                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>

                    <a href="<?= htmlspecialchars(paginationUrl($page + 1), ENT_QUOTES, 'UTF-8') ?>">
                        Келесі →
                    </a>

                <?php endif; ?>

            </nav>

        <?php endif; ?>

        <div class="pagination-info">
            <?= $totalServices > 0
                ? (($offset + 1) . '–' . ($offset + count($services)) . ' / ' . $totalServices . ' қызмет көрсетілді')
                : 'Көрсетілетін қызмет жоқ'
            ?>
        </div>

    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
