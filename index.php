<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
$featured = db_all("SELECT id, name, short_desc, price, discount_price, thumbnail
                    FROM courses WHERE status='published' ORDER BY id DESC LIMIT 3");
$pageTitle = 'Home';
$bodyClass = 'public-site public-home';
$activeNav = 'home';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/public-nav.php';
?>
<main>
<section class="home-hero">
  <img class="home-hero-image" src="https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&amp;fit=crop&amp;w=2000&amp;q=85" alt="Clinician reviewing medical notes in a modern healthcare setting">
  <div class="home-hero-shade" aria-hidden="true"></div>
  <div class="container home-hero-content">
    <p class="home-eyebrow">Pharma Academy <span>·</span> Learn with purpose</p>
    <h1>Pharma learning.<br>Real mentorship.</h1>
    <p class="home-hero-copy">Build practical confidence through structured courses, expert guidance, and live one-to-one sessions.</p>
    <div class="d-flex flex-wrap gap-2 mt-4">
      <a href="<?= SITE_URL ?>/public/courses.php" class="btn btn-light btn-lg fw-semibold">Explore courses</a>
      <a href="<?= SITE_URL ?>/register.php" class="btn btn-outline-light btn-lg">Create account</a>
    </div>
  </div>
</section>

<section class="home-courses py-5">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end gap-3 mb-4">
      <div><p class="home-section-kicker mb-1">Start learning</p><h2 class="fw-bold mb-0">Featured courses</h2></div>
      <a class="home-more-link" href="<?= SITE_URL ?>/public/courses.php">All courses <span aria-hidden="true">→</span></a>
    </div>
    <div class="row g-4">
      <?php foreach ($featured as $c): $price = $c['discount_price'] ?? $c['price']; ?>
        <div class="col-md-4">
          <div class="card home-course-card h-100">
            <?php $courseImage = $c['thumbnail'] ? UPLOAD_URL . '/' . $c['thumbnail'] : 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=900&q=80'; ?>
            <img class="home-course-image" src="<?= e($courseImage) ?>" alt="Healthcare education" loading="lazy">
            <div class="card-body">
              <h5 class="fw-semibold"><?= e($c['name']) ?></h5>
              <p class="text-muted small"><?= e($c['short_desc']) ?></p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="fw-bold home-price"><?= formatCurrency((float)$price) ?></span>
                <a class="btn btn-sm btn-primary" href="<?= SITE_URL ?>/public/course-details.php?id=<?= (int)$c['id'] ?>">View course</a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$featured): ?><div class="col-12"><p class="text-muted mb-0">New courses are being prepared. Check back soon.</p></div><?php endif; ?>
    </div>
  </div>
</section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>