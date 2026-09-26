<?php
require_once __DIR__ . '/../../includes/auth-check.php';
requireRole(USER_STUDENT);
$course_id = (int)($_GET['course_id'] ?? 0);
$mentor_id = (int)($_GET['mentor_id'] ?? 0);
$course = db_one("SELECT * FROM courses WHERE id=?",[$course_id]);
$mentor = db_one("SELECT m.*, u.full_name FROM mentors m JOIN users u ON u.id=m.user_id WHERE m.id=?",[$mentor_id]);
$student = db_one('SELECT id FROM students WHERE user_id=?', [$_SESSION['user_id']]);
$assigned = $student ? db_one("SELECT e.id FROM enrollments e
                                JOIN mentor_assignments ma ON ma.student_id=e.student_id AND ma.course_id=e.course_id AND ma.mentor_id=e.mentor_id AND ma.status='active'
                                WHERE e.student_id=? AND e.course_id=? AND e.mentor_id=? AND e.status='active'", [$student['id'], $course_id, $mentor_id]) : null;
if (!$course || !$mentor || !$assigned) { http_response_code(403); exit('This mentor is not assigned to your active course.'); }

// Open slots: mentor availability for next 30 days that don't have a booked slot
$slots = db_all("SELECT * FROM mentor_availability
                 WHERE mentor_id=? AND avail_date BETWEEN CURDATE() AND CURDATE() + INTERVAL 30 DAY
                   AND is_blocked=0
                 ORDER BY avail_date, start_time", [$mentor_id]);

// Find already-booked ranges for these dates
$booked = db_all("SELECT meeting_date, start_time, end_time FROM meetings
                  WHERE mentor_id=? AND meeting_date >= CURDATE() AND status IN ('scheduled','rescheduled')", [$mentor_id]);

$pageTitle='Mentor Availability';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-1">Availability — <?= e($mentor['full_name']) ?></h4>
  <p class="text-muted small mb-4">Course: <?= e($course['name']) ?></p>

  <?php if (!$slots): ?>
    <div class="alert alert-info">No availability posted yet. Check back soon or contact support.</div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($slots as $s):
        $slotStart = strtotime($s['avail_date'].' '.$s['start_time']);
        if ($slotStart <= time()) continue;
        $taken = false;
        foreach ($booked as $booking) {
            if ($booking['meeting_date'] === $s['avail_date'] && $booking['start_time'] < $s['end_time'] && $booking['end_time'] > $s['start_time']) {
                $taken = true;
                break;
            }
        }
      ?>
        <div class="col-md-6 col-lg-4">
          <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body">
              <div class="fw-semibold"><?= formatDate($s['avail_date'],'D, d M Y') ?></div>
              <div class="small text-muted mb-3"><?= e(substr($s['start_time'],0,5)) ?> – <?= e(substr($s['end_time'],0,5)) ?></div>
              <?php if ($taken): ?>
                <span class="badge bg-secondary">Booked</span>
              <?php else: ?>
                <form method="post" action="<?= SITE_URL ?>/admin/students/book-session.php">
                  <?= csrfField() ?>
                  <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
                  <input type="hidden" name="mentor_id" value="<?= (int)$mentor_id ?>">
                  <input type="hidden" name="avail_id" value="<?= (int)$s['id'] ?>">
                  <button class="btn btn-sm btn-primary">Book this session</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>