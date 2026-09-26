<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('mentor.view');

$q = trim($_GET['q'] ?? '');
$params = []; $where = '1=1';
if ($q !== '') { $where .= ' AND (u.full_name LIKE ? OR u.email LIKE ? OR m.specialization LIKE ?)'; $params=["%$q%","%$q%","%$q%"]; }

$rows = db_all("SELECT m.*, u.full_name, u.email, u.mobile, u.status AS user_status,
                  (SELECT COUNT(*) FROM mentor_assignments ma WHERE ma.mentor_id=m.id AND ma.status='active') AS students
                FROM mentors m JOIN users u ON u.id=m.user_id
                WHERE $where ORDER BY m.id DESC", $params);

$pageTitle = 'Mentors';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Mentors</h4>
    <?php if (hasPermission('mentor.create')): ?><a class="btn btn-primary" href="create.php"><i class="bi bi-plus-lg"></i> Add Mentor</a><?php endif; ?>
  </div>

  <form class="row g-2 mb-3">
    <div class="col-md-4"><input name="q" value="<?= e($q) ?>" class="form-control" placeholder="Search"></div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Search</button></div>
  </form>

  <div class="card border-0 shadow-sm rounded-4">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>#</th><th>Name</th><th>Specialization</th><th>Experience</th><th>Active Students</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">No mentors.</td></tr>
        <?php else: foreach ($rows as $m): ?>
          <tr>
            <td><?= (int)$m['id'] ?></td>
            <td>
              <div class="fw-semibold"><?= e($m['full_name']) ?></div>
              <div class="small text-muted"><?= e($m['email']) ?></div>
            </td>
            <td><?= e($m['specialization']) ?></td>
            <td><?= (int)$m['experience_years'] ?> yrs</td>
            <td><span class="badge bg-info text-dark"><?= (int)$m['students'] ?>/<?= (int)$m['max_students'] ?></span></td>
            <td><span class="badge bg-<?= $m['status']==='active'?'success':'secondary' ?>"><?= e($m['status']) ?></span></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int)$m['id'] ?>">View</a>
              <?php if (hasPermission('mentor.edit')): ?><a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= (int)$m['id'] ?>">Edit</a><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>