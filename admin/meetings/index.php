<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requirePermission('attendance.view');

$allowedStatuses = ['scheduled', 'completed', 'cancelled', 'rescheduled', 'no_show'];
$status = $_GET['status'] ?? '';
if (!in_array($status, $allowedStatuses, true)) $status = '';
$where = $status ? 'WHERE m.status = ?' : '';
$params = $status ? [$status] : [];
$rows = db_all("SELECT m.*, su.full_name AS student_name, mu.full_name AS mentor_name, c.name AS course_name
                FROM meetings m
                JOIN students s ON s.id=m.student_id
                JOIN users su ON su.id=s.user_id
                JOIN mentors mt ON mt.id=m.mentor_id
                JOIN users mu ON mu.id=mt.user_id
                JOIN courses c ON c.id=m.course_id
                $where
                ORDER BY m.meeting_date DESC, m.start_time DESC LIMIT 200", $params);

$pageTitle = 'Meetings';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/admin-sidebar.php';
?>
<main class="app-main">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
    <div><p class="home-section-kicker mb-1">Scheduling</p><h4 class="fw-bold mb-0">Meetings</h4></div>
    <form method="get" class="d-flex gap-2">
      <label class="visually-hidden" for="meeting-status">Filter by status</label>
      <select id="meeting-status" name="status" class="form-select">
        <option value="">All statuses</option>
        <?php foreach ($allowedStatuses as $option): ?>
          <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $option))) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-outline-primary">Filter</button>
    </form>
  </div>
  <div class="card"><div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light"><tr><th>Date</th><th>Time</th><th>Student</th><th>Mentor</th><th>Course</th><th>Status</th><th>Meeting</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $meeting): ?>
        <tr>
          <td><?= formatDate($meeting['meeting_date']) ?></td>
          <td><?= e(substr($meeting['start_time'], 0, 5)) ?>–<?= e(substr($meeting['end_time'], 0, 5)) ?></td>
          <td><?= e($meeting['student_name']) ?></td>
          <td><?= e($meeting['mentor_name']) ?></td>
          <td><?= e($meeting['course_name']) ?></td>
          <td><span class="badge bg-<?= ['scheduled'=>'success','completed'=>'secondary','cancelled'=>'danger','rescheduled'=>'warning','no_show'=>'dark'][$meeting['status']] ?? 'secondary' ?>"><?= e(ucwords(str_replace('_', ' ', $meeting['status']))) ?></span></td>
          <td><?php if ($meeting['meet_url']): ?><a href="<?= e($meeting['meet_url']) ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer">Join</a><?php else: ?>—<?php endif; ?></td>
        </tr>
      <?php endforeach; if (!$rows): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No meetings found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div></div>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>