<?php
$items = [
  ['dashboard','Dashboard',           '/admin/dashboard.php','bi-speedometer2',null],
  ['course.view','Courses',           '/admin/courses/index.php','bi-book',null],
  ['student.view','Students',         '/admin/students/index.php','bi-people',null],
  ['student.edit','Enrollment Requests','/admin/enrollments/requests.php','bi-person-check',null],
  ['mentor.view','Mentors',           '/admin/mentors/index.php','bi-person-badge',null],
  ['attendance.view','Meetings',      '/admin/meetings/index.php','bi-camera-video',null],
  ['assignment.review','Assignments', '/admin/assignments/index.php','bi-journal-text',null],
  ['assessment.review','Assessments', '/admin/assessments/index.php','bi-clipboard-check',null],
  ['attendance.view','Attendance',    '/admin/attendance/index.php','bi-calendar-check',null],
  ['performance.view','Performance',  '/admin/performance/index.php','bi-graph-up',null],
  ['leaderboard.view','Leaderboard',  '/admin/leaderboard/index.php','bi-trophy',null],
  ['feedback.view','Feedback',        '/admin/feedback/index.php','bi-star',null],
  ['announcement.view','Announcements','/admin/announcements/index.php','bi-megaphone',null],
  ['finance.view','Finance',          '/admin/finance/index.php','bi-cash-coin',null],
  ['employee.create','Employees',     '/admin/employees/index.php','bi-person-rolodex',null],
  ['role.manage','Roles & Permissions','/admin/roles/index.php','bi-shield-lock',null],
  ['certificate.view','Certificates', '/admin/certificates/index.php','bi-award',null],
  ['report.view','Reports',           '/admin/reports/students.php','bi-bar-chart',null],
  ['audit.view','Audit Logs',         '/admin/audit/index.php','bi-clock-history',null],
  ['settings.view','Settings',        '/admin/settings/general.php','bi-gear',null],
];
?>
<button class="sidebar-toggle" type="button" data-sidebar-toggle aria-expanded="false" aria-controls="admin-sidebar" aria-label="Open navigation"><i class="bi bi-list" aria-hidden="true"></i></button>
<aside class="app-sidebar" id="admin-sidebar">
  <div class="brand"><?= SITE_NAME ?><span>Admin workspace</span></div>
  <nav class="nav flex-column">
    <?php foreach ($items as [$perm,$label,$url,$icon,$_]):
      if ($perm && !hasPermission($perm)) continue; ?>
      <a class="nav-link" href="<?= SITE_URL . $url ?>">
        <i class="bi <?= $icon ?>"></i> <?= e($label) ?>
      </a>
    <?php endforeach; ?>
    <a class="nav-link nav-link-logout" href="<?= SITE_URL ?>/logout.php"><i class="bi bi-box-arrow-left" aria-hidden="true"></i><span>Sign out</span></a>
  </nav>
</aside>