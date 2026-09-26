<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requireRole(USER_MENTOR);
$mentor = db_one('SELECT * FROM mentors WHERE user_id=?',[$_SESSION['user_id']]);

$rows = db_all("SELECT a.*, c.name AS course_name,
                  (SELECT COUNT(*) FROM assignment_submissions s
                    JOIN students st ON st.id=s.student_id
                    JOIN mentor_assignments ma ON ma.student_id=st.id AND ma.course_id=a.course_id AND ma.mentor_id=?
                    WHERE s.assignment_id=a.id AND s.status IN ('submitted','under_review')) AS pending
                FROM assignments a
                JOIN courses c ON c.id=a.course_id
                WHERE a.course_id IN (SELECT course_id FROM mentor_assignments WHERE mentor_id=? AND status='active')
                ORDER BY a.due_date DESC", [$mentor['id'], $mentor['id']]);

$pageTitle='Assignments';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/mentor-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">My Students' Assignments</h4>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table small align-middle mb-0">
      <thead class="table-light"><tr><th>Title</th><th>Course</th><th>Due</th><th>Pending</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e($r['title']) ?></td>
          <td><?= e($r['course_name']) ?></td>
          <td><?= formatDate($r['due_date'],'d M Y') ?></td>
          <td><span class="badge bg-info text-dark"><?= (int)$r['pending'] ?></span></td>
          <td class="text-end"><a class="btn btn-sm btn-primary" href="assignment-review.php?id=<?= (int)$r['id'] ?>">Review</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>