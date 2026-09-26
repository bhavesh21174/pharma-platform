<?php
$items = [
  ['/mentor/dashboard.php','Dashboard','bi-speedometer2'],
  ['/mentor/my-students.php','My Students','bi-people'],
  ['/mentor/availability.php','Availability','bi-calendar-plus'],
  ['/mentor/leave.php','Leave','bi-calendar-x'],
  ['/mentor/sessions.php','Sessions','bi-camera-video'],
  ['/admin/mentors/assignments.php','Assignments','bi-journal-text'],
  ['/admin/mentors/assessments.php','Assessments','bi-clipboard-check'],
  ['/admin/mentors/attendance.php','Attendance','bi-calendar-check'],
  ['/admin/mentors/announcements.php','Announcements','bi-megaphone'],
  ['/mentor/profile.php','Profile','bi-person'],
];
$activePage = basename($_SERVER['PHP_SELF'] ?? '');
?>
<button class="sidebar-toggle" type="button" data-sidebar-toggle aria-expanded="false" aria-controls="mentor-sidebar" aria-label="Open navigation"><i class="bi bi-list" aria-hidden="true"></i></button>
<aside class="app-sidebar" id="mentor-sidebar">
  <div class="brand"><?= SITE_NAME ?><span>Mentor workspace</span></div>
  <nav class="nav flex-column">
    <?php foreach ($items as [$url,$label,$icon]): ?>
      <a class="nav-link<?= $activePage === basename($url) ? ' active' : '' ?>" href="<?= SITE_URL . $url ?>"<?= $activePage === basename($url) ? ' aria-current="page"' : '' ?>><i class="bi <?= $icon ?>" aria-hidden="true"></i><span><?= $label ?></span></a>
    <?php endforeach; ?>
    <a class="nav-link nav-link-logout" href="<?= SITE_URL ?>/logout.php"><i class="bi bi-box-arrow-left" aria-hidden="true"></i><span>Sign out</span></a>
  </nav>
</aside>