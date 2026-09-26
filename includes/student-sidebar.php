<?php
$items = [
  ['/student/dashboard.php','Dashboard','bi-speedometer2'],
  ['/student/my-courses.php','My Courses','bi-book'],
  ['/student/my-mentor.php','My Mentor','bi-person-badge'],
  ['/student/meetings.php','Meetings','bi-camera-video'],
  ['/admin/students/assignments.php','Assignments','bi-journal-text'],
  ['/admin/students/assessments.php','Assessments','bi-clipboard-check'],
  ['/admin/students/attendance.php','Attendance','bi-calendar-check'],
  ['/admin/students/performance.php','Performance','bi-graph-up'],
  ['/admin/students/leaderboard.php','Leaderboard','bi-trophy'],
  ['/admin/students/feedback.php','Feedback','bi-star'],
  ['/admin/students/announcements.php','Announcements','bi-megaphone'],
  ['/admin/students/results.php','Results','bi-clipboard-data'],
  ['/admin/students/certificates.php','Certificates','bi-award'],
  ['/student/payments.php','Payments','bi-credit-card'],
  ['/student/invoices.php','Invoices','bi-receipt'],
  ['/student/notifications.php','Notifications','bi-bell'],
  ['/student/profile.php','Profile','bi-person'],
];
$activePage = basename($_SERVER['PHP_SELF'] ?? '');
?>
<button class="sidebar-toggle" type="button" data-sidebar-toggle aria-expanded="false" aria-controls="student-sidebar" aria-label="Open navigation"><i class="bi bi-list" aria-hidden="true"></i></button>
<aside class="app-sidebar" id="student-sidebar">
  <div class="brand"><?= SITE_NAME ?><span>Student workspace</span></div>
  <nav class="nav flex-column">
    <?php foreach ($items as [$url,$label,$icon]): ?>
      <a class="nav-link<?= $activePage === basename($url) ? ' active' : '' ?>" href="<?= SITE_URL . $url ?>"<?= $activePage === basename($url) ? ' aria-current="page"' : '' ?>><i class="bi <?= $icon ?>" aria-hidden="true"></i><span><?= $label ?></span></a>
    <?php endforeach; ?>
    <a class="nav-link" href="<?= SITE_URL ?>/public/courses.php"><i class="bi bi-search" aria-hidden="true"></i><span>Browse courses</span></a>
    <a class="nav-link nav-link-logout" href="<?= SITE_URL ?>/logout.php"><i class="bi bi-box-arrow-left" aria-hidden="true"></i><span>Sign out</span></a>
  </nav>
</aside>