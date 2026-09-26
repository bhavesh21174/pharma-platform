<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
$query = trim($_GET['q'] ?? '');
$rows = $query === ''
    ? db_all("SELECT * FROM courses WHERE status='published' ORDER BY id DESC")
    : db_all("SELECT * FROM courses WHERE status='published' AND (name LIKE ? OR short_desc LIKE ?) ORDER BY id DESC", ['%' . $query . '%', '%' . $query . '%']);
$pageTitle = 'Courses';
$bodyClass = 'public-site public-courses-page';
$activeNav = 'courses';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/public-nav.php';
?>
<main>
  <header class="public-page-hero courses-hero">
    <div class="container public-page-hero-content">
      <p class="public-eyebrow">Build your next skill</p>
      <h1>Courses for a<br>changing field.</h1>
      <p>Structured pharma learning designed to connect knowledge with practice.</p>
    </div>
  </header>
  <section class="public-page-content container">
    <div class="catalog-toolbar">
      <div><p class="public-section-kicker mb-1">The catalogue</p><h2>Explore courses</h2><p class="text-muted mb-0"><?= count($rows) ?> course<?= count($rows) === 1 ? '' : 's' ?><?= $query !== '' ? ' matching “' . e($query) . '”' : ' available' ?></p></div>
      <form class="catalog-search" method="get" role="search">
        <label class="visually-hidden" for="course-search">Search courses</label>
        <i class="bi bi-search" aria-hidden="true"></i>
        <input id="course-search" type="search" name="q" value="<?= e($query) ?>" placeholder="Search by topic or course">
        <button class="btn btn-public-primary">Search</button>
      </form>
    </div>
    <div class="row g-4">
    <?php foreach ($rows as $c): ?>
      <div class="col-md-6 col-lg-4">
        <?php $courseImage = $c['thumbnail'] ? UPLOAD_URL . '/' . $c['thumbnail'] : 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=900&q=80'; ?>
        <article class="course-tile h-100">
          <a class="course-tile-image" href="<?= SITE_URL ?>/public/course-details.php?id=<?= (int)$c['id'] ?>" aria-label="View <?= e($c['name']) ?>">
            <img src="<?= e($courseImage) ?>" alt="Healthcare course visual" loading="lazy">
            <span><?= (int)$c['duration_days'] ?> days</span>
          </a>
          <div class="course-tile-body">
            <p class="course-tile-kicker"><?= e(ucfirst($c['level'])) ?> <span>·</span> <?= e($c['course_code'] ?: 'Pharma Academy') ?></p>
            <h3><?= e($c['name']) ?></h3>
            <p class="course-tile-desc"><?= e($c['short_desc']) ?></p>
            <div class="course-tile-footer">
              <div><small>Course fee</small><strong><?= formatCurrency((float)($c['discount_price'] ?? $c['price'])) ?></strong></div>
              <a class="btn btn-public-primary" href="<?= SITE_URL ?>/public/course-details.php?id=<?= (int)$c['id'] ?>">Explore <span aria-hidden="true">↗</span></a>
            </div>
          </div>
        </article>
      </div>
    <?php endforeach; ?>
      <?php if (!$rows): ?><div class="col-12"><div class="catalog-empty"><i class="bi bi-journal-x" aria-hidden="true"></i><h3>No matching courses</h3><p>Try a different course name or browse the full catalogue.</p><a class="btn btn-public-outline" href="<?= SITE_URL ?>/public/courses.php">Clear search</a></div></div><?php endif; ?>
    </div>
  </section>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>