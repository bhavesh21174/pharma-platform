<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('course.view');

$q      = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20; $off = ($page-1)*$limit;

$where = ['1=1']; $params = [];
if ($q !== '')      { $where[] = '(name LIKE ? OR course_code LIKE ?)'; $params[]="%$q%"; $params[]="%$q%"; }
if ($status !== '') { $where[] = 'status = ?'; $params[]=$status; }
$w = implode(' AND ', $where);

$total = (int) db_one("SELECT COUNT(*) c FROM courses WHERE $w", $params)['c'];
$rows  = db_all("SELECT * FROM courses WHERE $w ORDER BY id DESC LIMIT $limit OFFSET $off", $params);

$pageTitle = 'Courses';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Courses</h4>
    <a class="btn btn-primary" href="create.php"><i class="bi bi-plus-lg"></i> New Course</a>
  </div>

  <form class="row g-2 mb-3">
    <div class="col-md-4"><input name="q" value="<?= e($q) ?>" class="form-control" placeholder="Search name or code"></div>
    <div class="col-md-3">
      <select name="status" class="form-select">
        <option value="">All status</option>
        <?php foreach (['draft','published','archived'] as $s): ?>
          <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
  </form>

  <div class="card border-0 shadow-sm rounded-4">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr><th>#</th><th>Code</th><th>Name</th><th>Price</th><th>Duration</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">No courses found.</td></tr>
        <?php else: foreach ($rows as $c): ?>
          <tr>
            <td><?= (int)$c['id'] ?></td>
            <td><span class="badge bg-light text-dark"><?= e($c['course_code']) ?></span></td>
            <td><?= e($c['name']) ?></td>
            <td><?= formatCurrency((float)($c['discount_price'] ?? $c['price'])) ?></td>
            <td><?= (int)$c['duration_days'] ?> days</td>
            <td>
              <?php $cls = ['draft'=>'secondary','published'=>'success','archived'=>'dark'][$c['status']] ?? 'secondary'; ?>
              <span class="badge bg-<?= $cls ?>"><?= e($c['status']) ?></span>
            </td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="view.php?id=<?= (int)$c['id'] ?>">View</a>
              <?php if (hasPermission('course.edit')): ?>
                <a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= (int)$c['id'] ?>">Edit</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php $pages = (int)ceil($total/$limit); if ($pages > 1): ?>
  <nav class="mt-3"><ul class="pagination">
    <?php for ($i=1;$i<=$pages;$i++): ?>
      <li class="page-item <?= $i==$page?'active':'' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>"><?= $i ?></a></li>
    <?php endfor; ?>
  </ul></nav>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>