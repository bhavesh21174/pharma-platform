<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('student.view');

$q = trim($_GET['q'] ?? '');
$params=[]; $where='1=1';
if ($q!=='') { $where.=' AND (u.full_name LIKE ? OR u.email LIKE ?)'; $params=["%$q%","%$q%"]; }

$rows = db_all("SELECT s.*, u.full_name,u.email,u.mobile,u.status AS user_status,
                  (SELECT COUNT(*) FROM enrollments e WHERE e.student_id=s.id AND e.status='active') AS active_courses
                FROM students s JOIN users u ON u.id=s.user_id
                WHERE $where ORDER BY s.id DESC", $params);

$pageTitle='Students';
include __DIR__.'/../../includes/header.php';
include __DIR__.'/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <div class="d-flex justify-content-between mb-3">
    <h4 class="fw-bold mb-0">Students</h4>
    <?php if (hasPermission('student.create')): ?><a class="btn btn-primary" href="create.php"><i class="bi bi-plus-lg"></i> Add Student</a><?php endif; ?>
  </div>
  <form class="row g-2 mb-3">
    <div class="col-md-4"><input name="q" value="<?= e($q) ?>" class="form-control" placeholder="Search name/email"></div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Search</button></div>
  </form>
  <div class="card border-0 shadow-sm rounded-4"><div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light"><tr><th>#</th><th>Name</th><th>Email</th><th>College</th><th>Courses</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $s): ?>
        <tr>
          <td><?= (int)$s['id'] ?></td>
          <td><?= e($s['full_name']) ?></td>
          <td><?= e($s['email']) ?></td>
          <td><?= e($s['college']) ?></td>
          <td><span class="badge bg-info text-dark"><?= (int)$s['active_courses'] ?></span></td>
          <td><span class="badge bg-<?= $s['user_status']==='active'?'success':'secondary' ?>"><?= e($s['user_status']) ?></span></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int)$s['id'] ?>">View</a>
            <?php if (hasPermission('student.edit')): ?><a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= (int)$s['id'] ?>">Edit</a><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>