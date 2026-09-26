<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
$mentors = db_all("SELECT m.*, u.full_name,u.profile_photo,
                    (SELECT COUNT(*) FROM mentor_assignments ma WHERE ma.mentor_id=m.id AND ma.status='active') AS students
                   FROM mentors m JOIN users u ON u.id=m.user_id
                   WHERE m.status='active' AND u.status='active'
                   ORDER BY m.experience_years DESC");
$pageTitle = 'Mentors';
$bodyClass = 'public-site public-mentors-page';
$activeNav = 'mentors';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/public-nav.php';
?>
<main>
  <header class="public-page-hero mentors-hero">
    <div class="container public-page-hero-content">
      <p class="public-eyebrow">Learn with a human in your corner</p>
      <h1>Guidance from<br>people in the field.</h1>
      <p>Meet the mentors who help turn pharma knowledge into confident practice.</p>
    </div>
  </header>
  <section class="public-page-content container">
    <div class="catalog-toolbar mentor-toolbar">
      <div><p class="public-section-kicker mb-1">The people behind the learning</p><h2>Meet your mentors</h2><p class="text-muted mb-0"><?= count($mentors) ?> active mentor<?= count($mentors) === 1 ? '' : 's' ?></p></div>
      <a href="<?= SITE_URL ?>/public/courses.php" class="btn btn-public-outline">Explore courses <span aria-hidden="true">↗</span></a>
    </div>
    <div class="row g-4">
    <?php foreach ($mentors as $m): ?>
      <div class="col-md-6 col-lg-4">
        <article class="mentor-tile h-100">
          <div class="mentor-tile-top">
            <?php if ($m['profile_photo']): ?>
              <img src="<?= UPLOAD_URL.'/'.e($m['profile_photo']) ?>" width="76" height="76" class="mentor-avatar-image" alt="Portrait of <?= e($m['full_name']) ?>" loading="lazy">
            <?php else: ?>
              <div class="mentor-avatar mentor-avatar-large" aria-hidden="true"><?= e(mb_strtoupper(mb_substr(trim($m['full_name']), 0, 1))) ?></div>
            <?php endif; ?>
            <span class="mentor-mark" aria-hidden="true"><i class="bi bi-patch-check-fill"></i></span>
          </div>
          <div class="mentor-tile-body">
            <p class="course-tile-kicker mb-2"><?= e($m['specialization'] ?: 'Pharma mentor') ?></p>
            <h3><?= e($m['full_name']) ?></h3>
            <p class="mentor-credentials"><?= e($m['qualification'] ?: 'Industry mentor') ?> <span>·</span> <?= (int)$m['experience_years'] ?> years experience</p>
            <p class="mentor-bio"><?= e(mb_substr($m['bio'] ?: 'Supporting the next generation of pharma professionals through focused, one-to-one learning.', 0, 190)) ?><?= mb_strlen($m['bio'] ?? '') > 190 ? '…' : '' ?></p>
            <div class="mentor-tile-footer"><span><i class="bi bi-people" aria-hidden="true"></i> <?= (int)$m['students'] ?> students</span><a href="<?= SITE_URL ?>/public/courses.php">Find a course <span aria-hidden="true">→</span></a></div>
          </div>
        </article>
      </div>
    <?php endforeach; ?>
      <?php if (!$mentors): ?><div class="col-12"><div class="catalog-empty"><i class="bi bi-person-lines-fill" aria-hidden="true"></i><h3>Mentor profiles are on the way</h3><p>Please check back soon.</p></div></div><?php endif; ?>
    </div>
  </section>
  </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>