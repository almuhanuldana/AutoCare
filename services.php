<?php
$pageTitle = 'Қызметтер';
$activePage = 'services';
require_once __DIR__ . '/config/database.php';

$search = trim($_GET['search'] ?? '');
$category = $_GET['category'] ?? 'all';
$allowedCategories = ['all', 'diagnostic', 'maintenance', 'repair'];
if (!in_array($category, $allowedCategories, true)) {
    $category = 'all';
}
$sql = "SELECT * FROM services WHERE is_active = 1";
$params = [];
if ($search !== '') {
    $sql .= " AND service_name LIKE ?";
    $params[] = '%' . $search . '%';
}
if ($category !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $category;
}
$sql .= " ORDER BY id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>
<main class="page-main">
  <section class="page-hero">
    <div class="eyebrow">AUTOCARE / ҚЫЗМЕТТЕР</div>
    <h1>Көлігіңізге қажет<br><span>барлық қызмет бір жерде.</span></h1>
    <p>Қызмет түрін таңдап, бағасымен танысыңыз. Нақты баға көліктің моделіне және жұмыс көлеміне байланысты анықталады.</p>
  </section>
  <section class="catalog-section">
    <form class="catalog-toolbar" method="GET" action="services.php">
      <div class="search-box"><span>⌕</span><input name="search" type="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Қызмет атауын іздеу..." aria-label="Қызмет іздеу"></div>
      <select name="category" aria-label="Қызмет санатын таңдау">
        <option value="all" <?= $category === 'all' ? 'selected' : '' ?>>Барлық санат</option>
        <option value="diagnostic" <?= $category === 'diagnostic' ? 'selected' : '' ?>>Диагностика</option>
        <option value="maintenance" <?= $category === 'maintenance' ? 'selected' : '' ?>>Техникалық қызмет</option>
        <option value="repair" <?= $category === 'repair' ? 'selected' : '' ?>>Жөндеу</option>
      </select>
      <button class="button button-outline" type="submit">Іздеу</button>
      <span class="result-count"><?= count($services) ?> қызмет</span>
    </form>
    <div class="service-grid catalog-grid" id="serviceGrid">
      <?php foreach ($services as $service): ?>
      <article class="service-card catalog-card">
        <div class="service-card-top"><div class="service-icon">⚙</div><span class="tag"><?= htmlspecialchars(mb_strtoupper($service['category']), ENT_QUOTES, 'UTF-8') ?></span></div>
        <h3><?= htmlspecialchars($service['service_name'], ENT_QUOTES, 'UTF-8') ?></h3>
        <p><?= htmlspecialchars($service['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        <div class="service-meta"><span>⏱ <?= (int)$service['estimated_minutes'] ?> минут</span><strong><?= number_format((float)$service['base_price'], 0, ',', ' ') ?> ₸ бастап</strong></div>
        <a class="button button-outline full-button" href="booking.php?service=<?= urlencode($service['service_name']) ?>">Қызметті таңдау <span>→</span></a>
      </article>
      <?php endforeach; ?>
    </div>
    <?php if (!$services): ?><div class="empty-state">Іздеу бойынша қызмет табылмады. Басқа сөз енгізіп көріңіз.</div><?php endif; ?>
  </section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
