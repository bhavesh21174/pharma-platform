<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('student.view');
$rows = db_all("SELECT a.*, u.full_name AS author FROM announcements a JOIN users u ON u.id=a.created_by ORDER BY a.id DESC");
$pageTitle='Announcements';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <div class="d-flex justify-content-between mb-3">
    <h4 class="fw-bold mb-0">Announcements</h4>
    <a class="btn btn-primary" href="create.php"><i class="bi bi-plus-lg"></i> New</a>
  </div>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table small align-middle mb-0">
      <thead class="table-light"><tr><th>#</th><th>Title</th><th>Audience</th><th>Publish</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= e($r['title']) ?></td>
          <td><?= e($r['audience']) ?></td>
          <td><?= formatDate($r['publish_at']) ?></td>
          <td><span class="badge bg-secondary"><?= e($r['status']) ?></span></td>
          <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="edit.php?id=<?= (int)$r['id'] ?>">Edit</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>