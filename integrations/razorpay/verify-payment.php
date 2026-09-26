<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/config.php';
requireRole(USER_STUDENT);
header('Content-Type: application/json');

try {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true) ?: [];
    $isDemo = !empty($body['demo_mode']);
    $orderId   = $body['razorpay_order_id']   ?? '';
    $paymentId = $body['razorpay_payment_id'] ?? '';
    $signature = $body['razorpay_signature']  ?? '';
    $localId   = (int)($body['payment_id'] ?? 0);

    if (!$orderId || !$localId) {
        throw new RuntimeException('Missing parameters.');
    }

    if ($isDemo) {
        if (!DEMO_PAYMENTS_ENABLED || !str_starts_with($orderId, 'demo_order_')) {
            throw new RuntimeException('Demo payments are disabled for this environment.');
        }
        $csrf = (string)($body['csrf_token'] ?? '');
        if (!$csrf || !hash_equals($_SESSION['csrf'] ?? '', $csrf)) {
            throw new RuntimeException('Demo payment security check failed.');
        }
        $paymentId = 'DEMO-' . $localId . '-' . bin2hex(random_bytes(4));
        $signature = 'demo';
    } else {
        if (!$paymentId || !$signature) throw new RuntimeException('Missing payment verification data.');
        $cfg = razorpay_config();
        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, $cfg['key_secret']);
        if (!hash_equals($expected, $signature)) {
            throw new RuntimeException('Signature verification failed.');
        }
    }

    $payment = db_one('SELECT * FROM payments WHERE id=? AND order_id=?',[$localId, $orderId]);
    if (!$payment) throw new RuntimeException('Payment record not found.');

    $student = db_one('SELECT id FROM students WHERE user_id=?',[$_SESSION['user_id']]);
    if (!$student || (int)$student['id'] !== (int)$payment['student_id']) {
        throw new RuntimeException('Payment does not belong to you.');
    }

    if ($payment['status'] === 'successful') {
        echo json_encode(['ok'=>true, 'note'=>'already processed']); exit;
    }

    db()->beginTransaction();

    // Update payment
    $paymentUpdate = [
        'payment_id' => $paymentId,
        'signature'  => $signature,
        'status'     => 'successful',
        'paid_at'    => date('Y-m-d H:i:s'),
    ];
    if ($isDemo) $paymentUpdate['method'] = 'demo';
    db_update('payments', $paymentUpdate, 'id = :id', ['id'=>$payment['id']]);

    db_insert('payment_transactions', [
        'payment_id'   => $payment['id'],
        'event_type'   => 'payment.verified',
        'payload_json' => json_encode($body),
    ]);

    $orderTxn = db_one("SELECT payload_json FROM payment_transactions WHERE payment_id=? AND event_type='order.created' ORDER BY id LIMIT 1", [(int)$payment['id']]);
    $orderData = $orderTxn ? (json_decode($orderTxn['payload_json'], true) ?: []) : [];
    $orderNotes = $orderData['notes'] ?? [];
    $purpose = $orderNotes['purpose'] ?? '';

    if ($purpose === 'course_enrollment') {
        $requestedMentorId = (int)($orderNotes['mentor_id'] ?? 0);
        $totalFee = (float)($orderNotes['total_amount'] ?? 0);
        $expectedDeposit = round($totalFee / 2, 2);
        $course = db_one("SELECT id, name, duration_days FROM courses WHERE id=? AND status='published'", [(int)$payment['course_id']]);
        $mentor = db_one("SELECT id FROM mentors WHERE id=? AND status='active'", [$requestedMentorId]);
        if (!$course || !$mentor || (int)$payment['installment_no'] !== 1 || $totalFee <= 0
            || (int)($orderNotes['student_id'] ?? 0) !== (int)$payment['student_id']
            || (int)($orderNotes['course_id'] ?? 0) !== (int)$payment['course_id']
            || (int)$payment['mentor_id'] !== $requestedMentorId
            || abs((float)$payment['amount'] - $expectedDeposit) > 0.01) {
            throw new RuntimeException('Course deposit details do not match the order.');
        }

        $enrollment = db_one('SELECT id FROM enrollments WHERE student_id=? AND course_id=?', [(int)$payment['student_id'], (int)$payment['course_id']]);
        if ($enrollment) {
            db_update('enrollments', [
                'status' => 'pending_admin',
                'mentor_id' => null,
                'requested_mentor_id' => $requestedMentorId,
                'total_fee' => $totalFee,
                'paid_amount' => (float)$payment['amount'],
                'enrolled_at' => date('Y-m-d H:i:s'),
                'second_installment_due_at' => null,
                'second_reminder_sent_at' => null,
            ], 'id = :id', ['id' => $enrollment['id']]);
            $enrollmentId = (int)$enrollment['id'];
        } else {
            $enrollmentId = db_insert('enrollments', [
                'student_id' => (int)$payment['student_id'],
                'course_id' => (int)$payment['course_id'],
                'mentor_id' => null,
                'requested_mentor_id' => $requestedMentorId,
                'total_fee' => $totalFee,
                'paid_amount' => (float)$payment['amount'],
                'status' => 'pending_admin',
                'enrolled_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $invoiceNo = 'INV-' . date('Ymd') . '-' . str_pad((string)$payment['id'], 5, '0', STR_PAD_LEFT);
        db_insert('invoices', [
            'invoice_no' => $invoiceNo,
            'payment_id' => (int)$payment['id'],
            'student_id' => (int)$payment['student_id'],
            'amount' => (float)$payment['amount'],
            'tax' => 0,
            'total' => (float)$payment['amount'],
        ]);

        $studentUser = db_one('SELECT user_id FROM students WHERE id=?', [(int)$payment['student_id']]);
        $adminUsers = db_all("SELECT id FROM users WHERE user_type IN ('ADMIN','SUPER_ADMIN') AND status='active'");
        if ($studentUser) {
            createNotification((int)$studentUser['user_id'], 'Mentor request sent',
                'Your 50% deposit was received. An administrator will confirm your mentor allocation.',
                SITE_URL . '/student/my-courses.php');
        }
        foreach ($adminUsers as $adminUser) {
            createNotification((int)$adminUser['id'], 'Course enrollment needs allocation',
                'A deposit was received for ' . $course['name'] . '. Please review the requested mentor.',
                SITE_URL . '/admin/enrollments/requests.php');
        }
        $announcementOwner = $adminUsers[0]['id'] ?? null;
        if ($studentUser && $announcementOwner) {
            db_insert('announcements', [
                'title' => 'Course request received',
                'description' => 'Your 50% course deposit was received. Your requested mentor is awaiting administrator confirmation. We will notify you when the allocation is complete.',
                'audience' => 'student',
                'course_id' => (int)$payment['course_id'],
                'student_id' => (int)$payment['student_id'],
                'created_by' => (int)$announcementOwner,
            ]);
        }

        db_insert('payment_transactions', [
            'payment_id' => (int)$payment['id'],
            'event_type' => 'enrollment.deposit_confirmed',
            'payload_json' => json_encode(['enrollment_id' => $enrollmentId, 'invoice_no' => $invoiceNo]),
        ]);
        db()->commit();
        createAuditLog('course.deposit_verified', 'enrollment', $enrollmentId, null, ['status' => 'pending_admin']);
        echo json_encode(['ok' => true, 'enrollment_id' => $enrollmentId, 'status' => 'pending_admin']);
        exit;
    }

    if ($purpose === 'course_installment') {
        $enrollmentId = (int)($orderNotes['enrollment_id'] ?? 0);
        $enrollment = db_one("SELECT * FROM enrollments WHERE id=? AND student_id=? AND status='active'", [$enrollmentId, (int)$payment['student_id']]);
        if (!$enrollment || (int)$payment['installment_no'] !== 2
            || (int)($orderNotes['course_id'] ?? 0) !== (int)$payment['course_id']
            || (int)$enrollment['course_id'] !== (int)$payment['course_id']) {
            throw new RuntimeException('Course installment is not payable for this enrollment.');
        }
        $remaining = round((float)$enrollment['total_fee'] - (float)$enrollment['paid_amount'], 2);
        if ($remaining <= 0 || abs((float)$payment['amount'] - $remaining) > 0.01) {
            throw new RuntimeException('Installment amount does not match the remaining course balance.');
        }

        db_update('enrollments', ['paid_amount' => (float)$enrollment['total_fee']], 'id = :id', ['id' => $enrollmentId]);
        $invoiceNo = 'INV-' . date('Ymd') . '-' . str_pad((string)$payment['id'], 5, '0', STR_PAD_LEFT);
        db_insert('invoices', [
            'invoice_no' => $invoiceNo,
            'payment_id' => (int)$payment['id'],
            'student_id' => (int)$payment['student_id'],
            'amount' => (float)$payment['amount'],
            'tax' => 0,
            'total' => (float)$payment['amount'],
        ]);
        $studentUser = db_one('SELECT user_id FROM students WHERE id=?', [(int)$payment['student_id']]);
        $adminUsers = db_all("SELECT id FROM users WHERE user_type IN ('ADMIN','SUPER_ADMIN') AND status='active'");
        if ($studentUser) {
            createNotification((int)$studentUser['user_id'], 'Course balance paid',
                'Your remaining course balance has been paid. Thank you.', SITE_URL . '/student/payments.php');
            $announcementOwner = $adminUsers[0]['id'] ?? null;
            if ($announcementOwner) {
                db_insert('announcements', [
                    'title' => 'Course payment complete',
                    'description' => 'Your remaining installment was received. Your course balance is now paid in full.',
                    'audience' => 'student',
                    'course_id' => (int)$payment['course_id'],
                    'student_id' => (int)$payment['student_id'],
                    'created_by' => (int)$announcementOwner,
                ]);
            }
        }
        db_insert('payment_transactions', [
            'payment_id' => (int)$payment['id'],
            'event_type' => 'enrollment.balance_paid',
            'payload_json' => json_encode(['enrollment_id' => $enrollmentId, 'invoice_no' => $invoiceNo]),
        ]);
        db()->commit();
        createAuditLog('course.balance_verified', 'enrollment', $enrollmentId, null, ['paid_amount' => $enrollment['total_fee']]);
        echo json_encode(['ok' => true, 'enrollment_id' => $enrollmentId, 'status' => 'paid']);
        exit;
    }

    throw new RuntimeException('Unsupported payment order. Please create a new course payment request.');

    // Legacy payment handling retained below for reference; new orders cannot reach it.
    $enroll = db_one('SELECT id FROM enrollments WHERE student_id=? AND course_id=?',[$payment['student_id'],$payment['course_id']]);
    if ($enroll) {
        db_update('enrollments', [
            'status'=>'active','mentor_id'=>$payment['mentor_id'],'enrolled_at'=>date('Y-m-d H:i:s')
        ], 'id = :id', ['id'=>$enroll['id']]);
        $enrollId = (int)$enroll['id'];
    } else {
        $enrollId = db_insert('enrollments', [
            'student_id'=>$payment['student_id'],
            'course_id'=>$payment['course_id'],
            'mentor_id'=>$payment['mentor_id'],
            'status'=>'active',
            'enrolled_at'=>date('Y-m-d H:i:s'),
        ]);
    }

    // Mentor assignment (idempotent)
    $ma = db_one("SELECT id FROM mentor_assignments WHERE mentor_id=? AND student_id=? AND course_id=? AND status='active'",
                 [$payment['mentor_id'],$payment['student_id'],$payment['course_id']]);
    if (!$ma) {
        db_insert('mentor_assignments', [
            'mentor_id'=>$payment['mentor_id'],
            'student_id'=>$payment['student_id'],
            'course_id'=>$payment['course_id'],
            'assigned_by'=>$_SESSION['user_id'],
            'status'=>'active',
        ]);
    }

    // Create meeting (Google Meet link added by cron/hook below)
    require_once __DIR__ . '/../google/calendar.php';
    $avail = null;
    $notes = json_decode($body['notes'] ?? '[]', true);
    // Get the meeting details from the initial payment notes stored earlier
    // We stored avail_id on the original order via create-order. Retrieve from transaction log:
    $orderTxn = db_one("SELECT payload_json FROM payment_transactions WHERE payment_id=? AND event_type='order.created' ORDER BY id LIMIT 1",[$payment['id']]);
    $availId = null;
    if ($orderTxn) {
        $orderData = json_decode($orderTxn['payload_json'], true) ?: [];
        $availId = $orderData['notes']['avail_id'] ?? null;
    }
    $avail = $availId ? db_one('SELECT * FROM mentor_availability WHERE id=?',[(int)$availId]) : null;

    $meetUrl = null; $eventId = null;
    if ($avail) {
        try {
            [$eventId, $meetUrl] = google_create_meeting(
                $payment['mentor_id'],
                $payment['student_id'],
                $payment['course_id'],
                $avail['avail_date'],
                $avail['start_time'],
                $avail['end_time']
            );
        } catch (Throwable $e) {
            error_log('Google Meet create failed: '.$e->getMessage());
        }
        db_insert('meetings', [
            'student_id'    => $payment['student_id'],
            'mentor_id'     => $payment['mentor_id'],
            'course_id'     => $payment['course_id'],
            'meeting_date'  => $avail['avail_date'],
            'start_time'    => $avail['start_time'],
            'end_time'      => $avail['end_time'],
            'gcal_event_id' => $eventId,
            'meet_url'      => $meetUrl,
            'status'        => 'scheduled',
        ]);
    }

    // Invoice number
    $invoiceNo = 'INV-' . date('Ymd') . '-' . str_pad((string)$payment['id'], 5, '0', STR_PAD_LEFT);
    db_insert('invoices', [
        'invoice_no'=>$invoiceNo,'payment_id'=>$payment['id'],'student_id'=>$payment['student_id'],
        'amount'=>$payment['amount'],'tax'=>0,'total'=>$payment['amount'],
    ]);

    // Notify
    $u = db_one('SELECT user_id FROM students WHERE id=?',[$payment['student_id']]);
    if ($u) createNotification((int)$u['user_id'],'Payment successful',
        'You are enrolled. Mentor session scheduled. Invoice: '.$invoiceNo, SITE_URL.'/student/dashboard.php');

    db()->commit();
    createAuditLog('payment.verified','payment',$payment['id'],null,['status'=>'successful']);

    echo json_encode(['ok'=>true, 'enrollment_id'=>$enrollId]);
} catch (Throwable $e) {
    if (db()->inTransaction()) db()->rollBack();
    error_log('verify-payment: '.$e->getMessage());
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}