<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
$courseCount = (int)db_one("SELECT COUNT(*) AS c FROM courses WHERE status='published'")['c'];
$mentorCount = (int)db_one("SELECT COUNT(*) AS c FROM mentors WHERE status='active'")['c'];
$pageTitle = 'About';
$bodyClass = 'public-site public-about-page';
$activeNav = 'about';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/public-nav.php';
?>
<main>
  <section class="about-opening">
    <div class="about-opening-image" role="img" aria-label="A healthcare professional working in a clinical environment"></div>
    <div class="container about-opening-content">
      <p class="public-eyebrow">A clearer path into pharma</p>
      <h1>Learn the science.<br>Grow the practice.</h1>
      <p><?= e(SITE_NAME) ?> connects structured pharma courses with personal guidance from experienced mentors.</p>
    </div>
  </section>
  <section class="about-statement container">
    <div class="about-statement-label"><span>Our approach</span><i class="bi bi-arrow-down-right" aria-hidden="true"></i></div>
    <div><h2>Good learning should feel connected to the work ahead.</h2><p>We bring lessons, assessments, and one-to-one conversations into one learning journey, so students can build knowledge steadily and ask better questions along the way.</p></div>
  </section>
  <section class="about-steps">
    <div class="container">
      <div class="about-steps-heading"><p class="public-section-kicker">From curious to capable</p><h2>A practical learning cycle.</h2></div>
      <div class="row g-0 about-step-list">
        <div class="col-md-4"><article class="about-step"><span>01</span><i class="bi bi-journal-medical" aria-hidden="true"></i><h3>Build foundations</h3><p>Follow a focused course at a pace designed around its 30, 60, or 90-day duration.</p></article></div>
        <div class="col-md-4"><article class="about-step"><span>02</span><i class="bi bi-person-video3" aria-hidden="true"></i><h3>Learn one-to-one</h3><p>Choose a mentor, request an allocation, and book sessions from their available times.</p></article></div>
        <div class="col-md-4"><article class="about-step"><span>03</span><i class="bi bi-graph-up-arrow" aria-hidden="true"></i><h3>Keep progressing</h3><p>Use assessments, assignments, and feedback to understand what to work on next.</p></article></div>
      </div>
    </div>
  </section>
  <section class="about-stats container" aria-label="Pharma Academy at a glance">
    <div><strong><?= $courseCount ?></strong><span>Published courses</span></div>
    <div><strong><?= $mentorCount ?></strong><span>Active mentors</span></div>
    <div><strong>1:1</strong><span>Mentor sessions</span></div>
    <a href="<?= SITE_URL ?>/public/courses.php">Find your starting point <span aria-hidden="true">↗</span></a>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>