<?php
require_once __DIR__ . '/../includes/auth-check.php';
requireRole(USER_MENTOR);
$mentor = db_one('SELECT id FROM mentors WHERE user_id=?', [$_SESSION['user_id']]);
if (!$mentor) exit('Mentor profile missing.');

$students = db_all("SELECT s.id AS student_id, u.full_name, u.email, u.mobile,
                      c.name AS course_name, ma.created_at AS assigned_at
                    FROM mentor_assignments ma
                    JOIN students s ON s.id=ma.student_id
                    JOIN users u ON u.id=s.user_id
                    JOIN courses c ON c.id=ma.course_id
                    WHERE ma.mentor_id=? AND ma.status='active'
                    ORDER BY c.name, u.full_name", [$mentor['id']]);

$pageTitle = 'My Students';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/mentor-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">My Students</h4>
  <div class="card"><div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light"><tr><th>Student</th><th>Course</th><th>Email</th><th>Mobile</th><th>Assigned</th></tr></thead>
      <tbody>
      <?php foreach ($students as $student): ?>
        <tr>
          <td class="fw-semibold"><?= e($student['full_name']) ?></td>
          <td><?= e($student['course_name']) ?></td>
          <td><?= e($student['email']) ?></td>
          <td><?= e($student['mobile'] ?? '—') ?></td>
          <td><?= formatDate($student['assigned_at']) ?></td>
        </tr>
      <?php endforeach; if (!$students): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">No students are assigned yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>