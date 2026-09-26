<?php
$activeNav = $activeNav ?? '';
$navUser = currentUser();
?>
<nav class="navbar navbar-expand-lg public-nav sticky-top" aria-label="Main navigation">
  <div class="container">
    <a class="navbar-brand" href="<?= SITE_URL ?>/">
      <span class="brand-mark" aria-hidden="true">P</span>
      <span><?= e(SITE_NAME) ?><small>PHARMA LEARNING</small></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#public-nav-links" aria-controls="public-nav-links" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="public-nav-links">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
        <?php foreach ([['courses','Courses','/public/courses.php'], ['mentors','Mentors','/mentors.php'], ['about','About','/about.php']] as [$key,$label,$path]): ?>
          <li class="nav-item"><a class="nav-link<?= $activeNav === $key ? ' active' : '' ?>" href="<?= SITE_URL . $path ?>"<?= $activeNav === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
        <?php endforeach; ?>
        <?php if ($navUser): ?>
          <li class="nav-item ms-lg-2"><a class="btn btn-public-primary" href="<?= SITE_URL ?>/dashboard-router.php">My workspace</a></li>
        <?php else: ?>
          <li class="nav-item ms-lg-2"><a class="nav-link nav-login" href="<?= SITE_URL ?>/login.php">Login</a></li>
          <li class="nav-item ms-lg-2"><a class="btn btn-public-primary" href="<?= SITE_URL ?>/register.php">Get started <span aria-hidden="true">↗</span></a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
