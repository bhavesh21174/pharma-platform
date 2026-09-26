<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('assignment.review');

$rows = db_all("SELECT a.*, c.name AS course_name,
                  (SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id=a.id) AS submissions
                FROM assignments a JOIN courses c ON c.id=a.course_id
                ORDER BY a.id DESC");

$pageTitle = 'Assignments';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <div class="d-flex justify-content-between mb-3">
    <h4 class="fw-bold mb-0">Assignments</h4>
    <?php if (hasPermission('assignment.create')): ?>
      <a class="btn btn-primary" href="create.php"><i class="bi bi-plus-lg"></i> New Assignment</a>
    <?php endif; ?>
  </div>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table align-middle small mb-0">
      <thead class="table-light"><tr><th>#</th><th>Title</th><th>Course</th><th>Due</th><th>Max</th><th>Submissions</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= e($r['title']) ?></td>
          <td><?= e($r['course_name']) ?></td>
          <td><?= formatDate($r['due_date'],'d M Y H:i') ?></td>
          <td><?= (int)$r['max_marks'] ?></td>
          <td><span class="badge bg-info text-dark"><?= (int)$r['submissions'] ?></span></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="submissions.php?id=<?= (int)$r['id'] ?>">Submissions</a>
            <?php if (hasPermission('assignment.create')): ?>
              <a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>