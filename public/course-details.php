<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
$id = (int)($_GET['id'] ?? 0);
$c = db_one("SELECT * FROM courses WHERE id=? AND status='published'",[$id]);
if (!$c) { http_response_code(404); exit('Not found'); }
$viewer = currentUser();
$isStudent = $viewer && $viewer['user_type'] === USER_STUDENT;
$studentEnrollment = null;
if ($isStudent) {
  $student = db_one('SELECT id FROM students WHERE user_id=?', [(int)$viewer['id']]);
  if ($student) $studentEnrollment = db_one('SELECT status FROM enrollments WHERE student_id=? AND course_id=?', [(int)$student['id'], $id]);
}
$modules = db_all('SELECT * FROM course_modules WHERE course_id=? ORDER BY position',[$id]);
$pageTitle = $c['name'];
$bodyClass = 'public-site public-course-detail';
$activeNav = 'courses';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/public-nav.php';
?>
<?php $courseImage = $c['thumbnail'] ? UPLOAD_URL . '/' . $c['thumbnail'] : 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1800&q=85'; ?>
<main class="course-detail-page">
  <section class="course-detail-hero">
    <img src="<?= e($courseImage) ?>" alt="Healthcare learning" class="course-detail-image">
    <div class="course-detail-shade" aria-hidden="true"></div>
    <div class="container course-detail-heading">
      <a class="course-back-link" href="<?= SITE_URL ?>/public/courses.php"><i class="bi bi-arrow-left" aria-hidden="true"></i> All courses</a>
      <p class="public-eyebrow mt-4"><?= e(ucfirst($c['level'])) ?> <span>·</span> <?= (int)$c['duration_days'] ?> days</p>
      <h1><?= e($c['name']) ?></h1>
      <p><?= e($c['short_desc']) ?></p>
    </div>
  </section>
  <section class="container course-detail-content">
    <div class="row g-5 align-items-start">
      <div class="col-lg-7">
        <section class="course-copy-section">
          <p class="public-section-kicker">Course overview</p>
          <h2>Build knowledge you can use.</h2>
          <div class="course-long-copy"><?= nl2br(e($c['description'])) ?></div>
        </section>
        <?php if ($c['outcomes']): ?>
          <section class="course-copy-section">
            <p class="public-section-kicker">Your progress</p>
            <h2>What you will work toward</h2>
            <div class="course-long-copy"><?= nl2br(e($c['outcomes'])) ?></div>
          </section>
        <?php endif; ?>
        <section class="course-copy-section">
          <p class="public-section-kicker">Inside the course</p>
          <h2>Learning outline</h2>
          <?php if ($modules): ?>
            <div class="course-syllabus">
              <?php foreach ($modules as $index => $module): ?>
                <details class="syllabus-item" <?= $index === 0 ? 'open' : '' ?>>
                  <summary><span><?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></span><?= e($module['title']) ?><i class="bi bi-plus-lg" aria-hidden="true"></i></summary>
                  <?php if ($module['description']): ?><p><?= e($module['description']) ?></p><?php endif; ?>
                </details>
              <?php endforeach; ?>
            </div>
          <?php elseif ($c['syllabus']): ?>
            <div class="course-long-copy"><?= nl2br(e($c['syllabus'])) ?></div>
          <?php else: ?>
            <p class="text-muted">Detailed lessons will appear in your student workspace after enrollment.</p>
          <?php endif; ?>
        </section>
      </div>
      <aside class="col-lg-5">
        <div class="course-enroll-panel">
          <p class="public-section-kicker">Start learning</p>
          <p class="course-price-label">Course fee</p>
          <h2><?= formatCurrency((float)($c['discount_price'] ?? $c['price'])) ?></h2>
          <?php if ($c['discount_price'] && $c['discount_price'] < $c['price']): ?>
            <p class="course-old-price">Regular fee <?= formatCurrency((float)$c['price']) ?></p>
          <?php endif; ?>
          <div class="course-quick-facts">
            <div><i class="bi bi-calendar3" aria-hidden="true"></i><span><small>Duration</small><?= (int)$c['duration_days'] ?> days</span></div>
            <div><i class="bi bi-person-video3" aria-hidden="true"></i><span><small>Guidance</small>One-to-one mentor</span></div>
            <div><i class="bi bi-wallet2" aria-hidden="true"></i><span><small>First payment</small>50% to request enrollment</span></div>
          </div>
          <?php if ($isStudent && $studentEnrollment && $studentEnrollment['status'] === 'active'): ?>
            <a href="<?= SITE_URL ?>/student/my-mentor.php?course_id=<?= (int)$c['id'] ?>" class="btn btn-public-primary w-100">View assigned mentor</a>
            <a href="<?= SITE_URL ?>/student/my-courses.php" class="btn btn-public-outline w-100 mt-2">My courses</a>
          <?php elseif ($isStudent && $studentEnrollment): ?>
            <a href="<?= SITE_URL ?>/student/my-courses.php" class="btn btn-public-primary w-100">View request status</a>
            <a href="<?= SITE_URL ?>/student/payments.php" class="btn btn-public-outline w-100 mt-2">Payment details</a>
          <?php elseif ($isStudent): ?>
            <a href="<?= SITE_URL ?>/admin/students/mentor-selection.php?course_id=<?= (int)$c['id'] ?>" class="btn btn-public-primary w-100">Choose a mentor</a>
          <?php elseif ($viewer): ?>
            <a href="<?= SITE_URL ?>/dashboard-router.php" class="btn btn-public-primary w-100">Go to workspace</a>
          <?php else: ?>
            <a href="<?= SITE_URL ?>/register.php?course=<?= (int)$c['id'] ?>" class="btn btn-public-primary w-100">Enroll · pay 50% now</a>
            <a href="<?= SITE_URL ?>/login.php?course=<?= (int)$c['id'] ?>" class="btn btn-public-outline w-100 mt-2">Already registered? Log in</a>
          <?php endif; ?>
          <p class="course-safe-note"><i class="bi bi-shield-check" aria-hidden="true"></i> Remaining 50% is due halfway through the course.</p>
        </div>
      </aside>
    </div>
  </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>