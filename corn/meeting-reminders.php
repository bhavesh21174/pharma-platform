<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$windows = [
    ['h'=>24,'label'=>'24 hours'],
    ['h'=>1, 'label'=>'1 hour'],
];

foreach ($windows as $w) {
    $lower = date('Y-m-d H:i:s', time() + ($w['h']-1) * 3600);
    $upper = date('Y-m-d H:i:s', time() + $w['h'] * 3600);

    $meetings = db_all("SELECT m.*,
                          (SELECT user_id FROM mentors WHERE id=m.mentor_id) AS mentor_user,
                          (SELECT user_id FROM students WHERE id=m.student_id) AS student_user
                        FROM meetings m
                        WHERE m.status='scheduled'
                          AND CONCAT(m.meeting_date,' ',m.start_time) BETWEEN ? AND ?", [$lower,$upper]);

    foreach ($meetings as $m) {
        foreach (['mentor_user'=>'Mentor','student_user'=>'Student'] as $col=>$label) {
            $uid = (int)$m[$col];
            if (!$uid) continue;
            // Prevent duplicate: check last notification
            $dup = db_one("SELECT id FROM notifications WHERE user_id=? AND title LIKE ? AND DATE(created_at)=CURDATE()",
                          [$uid, "%Meeting%{$w['h']}%reminder%"]);
            if ($dup) continue;
            createNotification($uid,
                "Meeting reminder ({$w['label']})",
                "Your session is scheduled in {$w['label']}. ".($m['meet_url'] ? "Join: {$m['meet_url']}" : ''),
                null);
        }
    }
}
echo "meeting-reminders done\n";