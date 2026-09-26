<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('mentor.view');
$id = (int)($_GET['id'] ?? 0);
$m  = db_one('SELECT m.*, u.full_name,u.email,u.mobile,u.profile_photo,u.status AS user_status FROM mentors m JOIN users u ON u.id=m.user_id WHERE m.id=?',[$id]);
if (!$m) exit('Not found');

$assigned = db_all("SELECT ma.*, u.full_name, u.email, s.id AS sid, c.name AS course_name
                    FROM mentor_assignments ma
                    JOIN students s ON s.id=ma.student_id
                    JOIN users u ON u.id=s.user_id
                    JOIN courses c ON c.id=ma.course_id
                    WHERE ma.mentor_id=? AND ma.status='active'", [$id]);

$stats = [
    'assigned'=>(int) db_one("SELECT COUNT(*) c FROM mentor_assignments WHERE mentor_id=? AND status='active'",[$id])['c'],
    'sessions'=>(int) db_one("SELECT COUNT(*) c FROM meetings WHERE mentor_id=? AND status='scheduled'",[$id])['c'],
    'completed'=>(int) db_one("SELECT COUNT(*) c FROM meetings WHERE mentor_id=? AND status='completed'",[$id])['c'],
];

$pageTitle = $m['full_name'];
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <div class="d-flex justify-content-between mb-3">
    <div class="d-flex gap-3 align-items-center">
      <?php if ($m['profile_photo']): ?>
        <img src="<?= UPLOAD_URL.'/'.e($m['profile_photo']) ?>" class="mentor-avatar-image" width="72" height="72" alt="">
      <?php else: ?>
        <div class="mentor-avatar mentor-avatar-large" aria-hidden="true"><?= e(mb_strtoupper(mb_substr(trim($m['full_name']), 0, 1))) ?></div>
      <?php endif; ?>
      <div>
        <h4 class="fw-bold mb-0"><?= e($m['full_name']) ?></h4>
        <div class="text-muted small"><?= e($m['specialization']) ?> · <?= e($m['qualification']) ?></div>
      </div>
    </div>
    <div>
      <?php if (hasPermission('mentor.edit')): ?><a class="btn btn-outline-secondary" href="edit.php?id=<?= (int)$id ?>">Edit</a><?php endif; ?>
      <a class="btn btn-primary" href="assign-student.php?mentor_id=<?= (int)$id ?>">Assign Student</a>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-6 col-md-4"><div class="stat-card"><div class="label">Active Students</div><div class="value"><?= $stats['assigned'] ?></div></div></div>
    <div class="col-6 col-md-4"><div class="stat-card"><div class="label">Upcoming Sessions</div><div class="value"><?= $stats['sessions'] ?></div></div></div>
    <div class="col-6 col-md-4"><div class="stat-card"><div class="label">Completed Sessions</div><div class="value"><?= $stats['completed'] ?></div></div></div>
  </div>

  <div class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-body">
      <h6 class="fw-bold mb-3">Bio</h6>
      <p class="small text-muted mb-0"><?= nl2br(e($m['bio'])) ?></p>
    </div>
  </div>

  <div class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-body">
      <h6 class="fw-bold mb-3">Assigned Students</h6>
      <?php if (!$assigned): ?><div class="text-muted small">None.</div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table align-middle small">
          <thead><tr><th>Student</th><th>Email</th><th>Course</th><th>Since</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($assigned as $a): ?>
            <tr>
              <td><?= e($a['full_name']) ?></td>
              <td><?= e($a['email']) ?></td>
              <td><?= e($a['course_name']) ?></td>
              <td><?= formatDate($a['created_at']) ?></td>
              <td><a class="btn btn-sm btn-outline-primary" href="../students/view.php?id=<?= (int)$a['sid'] ?>">View</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>