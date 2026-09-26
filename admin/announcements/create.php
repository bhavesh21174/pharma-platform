<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('student.view');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    $data = [
        'title'=>trim($_POST['title'] ?? ''),
        'description'=>$_POST['description'] ?? '',
        'audience'=>$_POST['audience'] ?? 'all',
        'course_id'=>$_POST['course_id'] ? (int)$_POST['course_id'] : null,
        'mentor_id'=>$_POST['mentor_id'] ? (int)$_POST['mentor_id'] : null,
        'student_id'=>$_POST['student_id'] ? (int)$_POST['student_id'] : null,
        'publish_at'=>$_POST['publish_at'] ?: date('Y-m-d H:i:s'),
        'expire_at'=>$_POST['expire_at'] ?: null,
        'status'=>$_POST['status'] ?? 'published',
        'created_by'=>$_SESSION['user_id'],
    ];
    if (!$data['title']) { setFlash('danger','Title required.'); redirect('create.php'); }
    if (!empty($_FILES['attachment']['name'])) {
        try { $data['attachment'] = upload_file($_FILES['attachment'],'documents'); }
        catch (Throwable $e) { setFlash('danger',$e->getMessage()); }
    }
    $id = db_insert('announcements',$data);
    createAuditLog('announcement.create','announcement',$id);

    // Fan-out notifications
    if ($data['status']==='published') {
        $targets = [];
        if ($data['audience'] === 'all' || $data['audience'] === 'students') {
            $targets = array_column(db_all("SELECT id FROM users WHERE user_type='STUDENT' AND status='active'"), 'id');
        } elseif ($data['audience'] === 'mentors') {
            $targets = array_column(db_all("SELECT id FROM users WHERE user_type='MENTOR' AND status='active'"), 'id');
        } elseif ($data['audience'] === 'employees') {
            $targets = array_column(db_all("SELECT id FROM users WHERE user_type IN ('ADMIN','SUPER_ADMIN','FINANCE','HR','COURSE_MANAGER')"), 'id');
        } elseif ($data['audience'] === 'course' && $data['course_id']) {
            $targets = array_column(db_all("SELECT u.id FROM users u JOIN students s ON s.user_id=u.id
                                            JOIN enrollments e ON e.student_id=s.id
                                            WHERE e.course_id=? AND e.status='active'",[$data['course_id']]), 'id');
        } elseif ($data['audience'] === 'student' && $data['student_id']) {
            $st = db_one('SELECT user_id FROM students WHERE id=?',[$data['student_id']]);
            if ($st) $targets = [(int)$st['user_id']];
        } elseif ($data['audience'] === 'mentor' && $data['mentor_id']) {
            $mt = db_one('SELECT user_id FROM mentors WHERE id=?',[$data['mentor_id']]);
            if ($mt) $targets = [(int)$mt['user_id']];
        }
        foreach ($targets as $uid) {
            createNotification((int)$uid, $data['title'], mb_substr($data['description'],0,180), SITE_URL.'/admin/students/announcements.php');
        }
    }

    setFlash('success','Announcement created.');
    redirect('index.php');
}

$courses = db_all("SELECT id,name FROM courses");
$mentors = db_all("SELECT m.id,u.full_name FROM mentors m JOIN users u ON u.id=m.user_id");
$students = db_all("SELECT s.id,u.full_name FROM students s JOIN users u ON u.id=s.user_id");

$pageTitle='New Announcement';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3">New Announcement</h4>
  <form method="post" enctype="multipart/form-data" class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><?= csrfField() ?>
    <div class="row g-3">
      <div class="col-md-8"><label class="form-label small fw-semibold">Title *</label><input name="title" required class="form-control"></div>
      <div class="col-md-4"><label class="form-label small fw-semibold">Audience</label>
        <select name="audience" class="form-select">
          <option value="all">All users</option><option value="students">All students</option><option value="mentors">All mentors</option>
          <option value="employees">Employees</option><option value="course">Specific course</option>
          <option value="mentor">Specific mentor</option><option value="student">Specific student</option>
        </select>
      </div>
      <div class="col-12"><label class="form-label small fw-semibold">Description *</label><textarea name="description" rows="5" class="form-control" required></textarea></div>
      <div class="col-md-4"><label class="form-label small fw-semibold">Course (if audience = course)</label>
        <select name="course_id" class="form-select"><option value="">—</option>
          <?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label small fw-semibold">Mentor (if audience = mentor)</label>
        <select name="mentor_id" class="form-select"><option value="">—</option>
          <?php foreach ($mentors as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['full_name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label small fw-semibold">Student (if audience = student)</label>
        <select name="student_id" class="form-select"><option value="">—</option>
          <?php foreach ($students as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['full_name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label small fw-semibold">Publish</label><input type="datetime-local" name="publish_at" class="form-control"></div>
      <div class="col-md-4"><label class="form-label small fw-semibold">Expire</label><input type="datetime-local" name="expire_at" class="form-control"></div>
      <div class="col-md-4"><label class="form-label small fw-semibold">Status</label>
        <select name="status" class="form-select"><option>published</option><option>draft</option></select>
      </div>
      <div class="col-md-6"><label class="form-label small fw-semibold">Attachment</label><input type="file" name="attachment" class="form-control"></div>
    </div>
    <div class="mt-4"><button class="btn btn-primary px-4">Publish</button></div>
  </div></form>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>