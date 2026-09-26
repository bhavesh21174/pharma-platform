<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/config.php';
requireRole(USER_STUDENT);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
verifyCsrf();

$installmentNo = (int)($_POST['installment_no'] ?? 1);
$student = db_one('SELECT * FROM students WHERE user_id=?', [$_SESSION['user_id']]);
if (!$student) exit('Student profile missing.');

$enrollment = null;
$mentor = null;
$purpose = 'course_enrollment';

if ($installmentNo === 2) {
    $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
    $enrollment = db_one("SELECT * FROM enrollments WHERE id=? AND student_id=? AND status='active'", [$enrollmentId, $student['id']]);
    if (!$enrollment) {
        setFlash('danger', 'Active enrollment was not found.');
        redirect(SITE_URL . '/student/payments.php');
    }
    if (!$enrollment['second_installment_due_at'] || strtotime($enrollment['second_installment_due_at']) > time()) {
        setFlash('warning', 'The remaining installment is not due yet.');
        redirect(SITE_URL . '/student/payments.php');
    }
    $courseId = (int)$enrollment['course_id'];
    $mentorId = (int)$enrollment['mentor_id'];
    $purpose = 'course_installment';
    $totalAmount = (float)$enrollment['total_fee'];
    $payAmount = round($totalAmount - (float)$enrollment['paid_amount'], 2);
    $course = db_one('SELECT * FROM courses WHERE id=?', [$courseId]);
    $mentor = db_one('SELECT m.*, u.full_name FROM mentors m JOIN users u ON u.id=m.user_id WHERE m.id=?', [$mentorId]);
} elseif ($installmentNo === 1) {
    $courseId = (int)($_POST['course_id'] ?? 0);
    $mentorId = (int)($_POST['mentor_id'] ?? 0);
    $course = db_one("SELECT * FROM courses WHERE id=? AND status='published'", [$courseId]);
    $mentor = db_one("SELECT m.*, u.full_name FROM mentors m JOIN users u ON u.id=m.user_id WHERE m.id=? AND m.status='active' AND u.status='active'", [$mentorId]);
    if (!$course || !$mentor) {
        http_response_code(400);
        exit('Course or mentor is unavailable.');
    }
    $existing = db_one('SELECT status FROM enrollments WHERE student_id=? AND course_id=?', [$student['id'], $courseId]);
    if ($existing) {
        setFlash('info', 'An enrollment already exists for this course.');
        redirect(SITE_URL . '/student/my-courses.php');
    }
    $totalAmount = (float)($course['discount_price'] ?? $course['price']);
    $payAmount = round($totalAmount / 2, 2);
} else {
    http_response_code(400);
    exit('Invalid installment.');
}

if (!$course || $totalAmount <= 0 || $payAmount <= 0) {
    setFlash('danger', 'This course does not have a valid price. Please contact support.');
    redirect($installmentNo === 2 ? SITE_URL . '/student/payments.php' : SITE_URL . '/public/courses.php');
}

$config = razorpay_config();
$currency = $config['currency'];
$demoMode = DEMO_PAYMENTS_ENABLED && (!$config['key_id'] || !$config['key_secret']);
$pendingPayment = db_one("SELECT * FROM payments
             WHERE student_id=? AND course_id=? AND installment_no=? AND status='pending'
             ORDER BY id DESC LIMIT 1", [(int)$student['id'], $courseId, $installmentNo]);
$order = null;
$paymentId = 0;
if ($pendingPayment) {
  $orderTxn = db_one("SELECT payload_json FROM payment_transactions WHERE payment_id=? AND event_type='order.created' ORDER BY id LIMIT 1", [(int)$pendingPayment['id']]);
  $savedOrder = $orderTxn ? (json_decode($orderTxn['payload_json'], true) ?: []) : [];
  if (!empty($savedOrder['id'])) {
    $order = $savedOrder;
    $paymentId = (int)$pendingPayment['id'];
    $payAmount = (float)$pendingPayment['amount'];
    $mentorId = (int)$pendingPayment['mentor_id'];
    $mentor = $mentorId ? db_one('SELECT m.*, u.full_name FROM mentors m JOIN users u ON u.id=m.user_id WHERE m.id=?', [$mentorId]) : null;
    $savedNotes = $savedOrder['notes'] ?? [];
    $totalAmount = (float)($savedNotes['total_amount'] ?? $totalAmount);
    if (str_starts_with($order['id'], 'demo_order_')) {
      if (DEMO_PAYMENTS_ENABLED) $demoMode = true;
      else {
        db_update('payments', ['status' => 'failed'], 'id = :id', ['id' => $paymentId]);
        $order = null;
      }
    }
  }
}

if (!$order) {
  $notes = [
    'purpose' => $purpose,
    'installment_no' => $installmentNo,
    'student_id' => (int)$student['id'],
    'course_id' => $courseId,
    'mentor_id' => $mentorId,
    'total_amount' => number_format($totalAmount, 2, '.', ''),
    'enrollment_id' => $enrollment ? (int)$enrollment['id'] : null,
  ];
  if ($demoMode) {
    $order = ['id' => 'demo_order_' . bin2hex(random_bytes(8)), 'amount' => (int)round($payAmount * 100), 'currency' => $currency, 'notes' => $notes];
  } else {
    $receipt = 'course_' . bin2hex(random_bytes(6));
    try {
      $order = razorpay_request('POST', 'orders', [
        'amount' => (int)round($payAmount * 100),
        'currency' => $currency,
        'receipt' => $receipt,
        'notes' => $notes,
      ]);
    } catch (Throwable $exception) {
      error_log('Course installment order: ' . $exception->getMessage());
      setFlash('danger', 'Payment could not be started. Check payment settings and try again.');
      redirect($installmentNo === 2
        ? SITE_URL . '/student/payments.php'
        : SITE_URL . '/admin/students/mentor-selection.php?course_id=' . $courseId);
    }
  }

  $paymentId = db_insert('payments', [
    'student_id' => (int)$student['id'],
    'course_id' => $courseId,
    'mentor_id' => $mentorId,
    'order_id' => $order['id'],
    'amount' => $payAmount,
    'currency' => $currency,
    'status' => 'pending',
    'installment_no' => $installmentNo,
  ]);
  db_insert('payment_transactions', [
    'payment_id' => $paymentId,
    'event_type' => 'order.created',
    'payload_json' => json_encode($order),
  ]);
  createAuditLog($installmentNo === 1 ? 'course.deposit_order_created' : 'course.balance_order_created', 'enrollment', $paymentId, null, [
    'course_id' => $courseId,
    'mentor_id' => $mentorId,
    'amount' => $payAmount,
  ]);
}

$user = currentUser();
$pageTitle = $installmentNo === 1 ? 'Course Deposit' : 'Course Balance';
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/student-sidebar.php';
?>
<main class="app-main">
  <h4 class="fw-bold mb-3"><?= $installmentNo === 1 ? 'Course deposit' : 'Course balance installment' ?></h4>
  <div class="card" style="max-width: 620px"><div class="card-body p-4">
    <?php if ($demoMode): ?><div class="alert alert-warning small"><strong>Demo payment mode.</strong> This test will not charge real money.</div><?php endif; ?>
    <p class="text-muted mb-1"><?= e($course['name']) ?> · <?= (int)$course['duration_days'] ?> days</p>
    <?php if ($mentor): ?><h5 class="fw-bold"><?= e($mentor['full_name']) ?> <span class="text-muted fw-normal"><?= $installmentNo === 1 ? 'requested mentor' : 'assigned mentor' ?></span></h5><?php endif; ?>
    <hr>
    <dl class="row mb-4">
      <dt class="col-7">Course fee</dt><dd class="col-5 text-end"><?= formatCurrency($totalAmount) ?></dd>
      <?php if ($installmentNo === 1): ?>
        <dt class="col-7">Due now (50%)</dt><dd class="col-5 text-end fw-bold"><?= formatCurrency($payAmount) ?></dd>
        <dt class="col-7">Remaining (due after <?= (int)floor((int)$course['duration_days'] / 2) ?> days)</dt><dd class="col-5 text-end"><?= formatCurrency($totalAmount - $payAmount) ?></dd>
      <?php else: ?>
        <dt class="col-7">Remaining balance</dt><dd class="col-5 text-end fw-bold"><?= formatCurrency($payAmount) ?></dd>
      <?php endif; ?>
    </dl>
    <button id="payBtn" class="btn btn-primary w-100"><?= $demoMode ? 'Confirm demo payment' : 'Pay ' . ($installmentNo === 1 ? 'deposit' : 'balance') . ' securely' ?></button>
    <div id="payMsg" class="small mt-3" role="status"></div>
  </div></div>
</main>
<?php if ($demoMode): ?>
<script>
document.getElementById('payBtn').addEventListener('click', async () => {
  const message = document.getElementById('payMsg');
  const button = document.getElementById('payBtn');
  button.disabled = true;
  try {
    const result = await fetch(<?= json_encode(SITE_URL . '/integrations/razorpay/verify-payment.php') ?>, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        demo_mode: true,
        csrf_token: <?= json_encode(csrfToken()) ?>,
        razorpay_order_id: <?= json_encode($order['id']) ?>,
        payment_id: <?= $paymentId ?>
      })
    });
    const data = await result.json();
    if (!result.ok || !data.ok) throw new Error(data.error || 'Demo payment failed.');
    window.location.href = <?= json_encode(SITE_URL . ($installmentNo === 1 ? '/student/my-courses.php?request=sent' : '/student/payments.php?paid=1')) ?>;
  } catch (error) {
    message.textContent = error.message || 'Demo payment failed. Please try again.';
    message.classList.add('text-danger');
    button.disabled = false;
  }
});
</script>
<?php else: ?>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
const options = {
  key: <?= json_encode($config['key_id']) ?>,
  amount: <?= (int)round($payAmount * 100) ?>,
  currency: <?= json_encode($config['currency']) ?>,
  name: <?= json_encode(SITE_NAME) ?>,
  description: <?= json_encode($course['name'] . ($installmentNo === 1 ? ' - 50% deposit' : ' - remaining balance')) ?>,
  order_id: <?= json_encode($order['id']) ?>,
  prefill: {
    name: <?= json_encode($user['full_name']) ?>,
    email: <?= json_encode($user['email']) ?>,
    contact: <?= json_encode($user['mobile'] ?? '') ?>
  },
  handler: async response => {
    const message = document.getElementById('payMsg');
    try {
      const result = await fetch(<?= json_encode(SITE_URL . '/integrations/razorpay/verify-payment.php') ?>, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          razorpay_order_id: response.razorpay_order_id,
          razorpay_payment_id: response.razorpay_payment_id,
          razorpay_signature: response.razorpay_signature,
          payment_id: <?= $paymentId ?>
        })
      });
      const data = await result.json();
      if (!result.ok || !data.ok) throw new Error(data.error || 'Payment verification failed.');
      window.location.href = <?= json_encode(SITE_URL . ($installmentNo === 1 ? '/student/my-courses.php?request=sent' : '/student/payments.php?paid=1')) ?>;
    } catch (error) {
      message.textContent = error.message || 'Payment verification failed. Please contact support.';
      message.classList.add('text-danger');
    }
  },
  theme: {color: '#176b60'}
};
document.getElementById('payBtn').addEventListener('click', () => new Razorpay(options).open());
</script>
<?php endif; ?>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
