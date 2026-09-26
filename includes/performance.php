<?php
require_once __DIR__ . '/functions.php';

/**
 * Compute performance metrics for one student in one course.
 * Weights come from settings (weight_assessment, weight_assignment, weight_attendance, weight_mentor_eval).
 */
function compute_performance(int $studentId, int $courseId): array {
    // Assignment average (out of 100)
    $asg = db_one("SELECT COALESCE(AVG( (s.marks / NULLIF(a.max_marks,0)) * 100 ),0) AS pct
                   FROM assignment_submissions s
                   JOIN assignments a ON a.id=s.assignment_id
                   WHERE s.student_id=? AND a.course_id=? AND s.marks IS NOT NULL",
                  [$studentId, $courseId])['pct'] ?? 0;

    // Assessment average
    $asm = db_one("SELECT COALESCE(AVG(r.percentage),0) AS pct
                   FROM assessment_results r
                   JOIN assessments a ON a.id=r.assessment_id
                   WHERE r.student_id=? AND a.course_id=?",
                  [$studentId, $courseId])['pct'] ?? 0;

    // Attendance %
    $att = db_one("SELECT
                     ROUND(100 * SUM(status IN ('present','late','excused')) / NULLIF(COUNT(*),0), 2) AS pct
                   FROM attendance WHERE student_id=? AND course_id=?",
                  [$studentId, $courseId])['pct'] ?? 0;

    // Mentor evaluation (0-100, stored as mentor_eval)
    $me = (float) (db_one("SELECT mentor_eval FROM student_performance WHERE student_id=? AND course_id=?",
                  [$studentId, $courseId])['mentor_eval'] ?? 0);

    $w_a  = (float) setting('weight_assessment', 50);
    $w_s  = (float) setting('weight_assignment', 25);
    $w_at = (float) setting('weight_attendance', 15);
    $w_me = (float) setting('weight_mentor_eval', 10);
    $totalW = max(1, $w_a + $w_s + $w_at + $w_me);

    $overall = (($asm * $w_a) + ($asg * $w_s) + ($att * $w_at) + ($me * $w_me)) / $totalW;

    return [
        'assignment_score' => round((float)$asg, 2),
        'assessment_score' => round((float)$asm, 2),
        'attendance_pct'   => round((float)$att, 2),
        'mentor_eval'      => round((float)$me, 2),
        'overall_score'    => round($overall, 2),
    ];
}

/** Upsert into student_performance table. */
function sync_student_performance(int $studentId, int $courseId): void {
    $m = compute_performance($studentId, $courseId);
    $existing = db_one('SELECT id FROM student_performance WHERE student_id=? AND course_id=?',
                       [$studentId, $courseId]);
    if ($existing) {
        db_update('student_performance', $m, 'id = :id', ['id'=>$existing['id']]);
    } else {
        $m['student_id'] = $studentId;
        $m['course_id']  = $courseId;
        db_insert('student_performance', $m);
    }
}

/** Full leaderboard rebuild for one course. */
function rebuild_leaderboard(int $courseId): void {
    $students = db_all("SELECT student_id FROM enrollments WHERE course_id=? AND status='active'", [$courseId]);
    $rows = [];
    foreach ($students as $s) {
        $sid = (int)$s['student_id'];
        sync_student_performance($sid, $courseId);
        $perf = db_one('SELECT overall_score FROM student_performance WHERE student_id=? AND course_id=?',
                       [$sid, $courseId]);
        $rows[] = ['student_id'=>$sid, 'score'=>(float)($perf['overall_score'] ?? 0)];
    }
    usort($rows, fn($a,$b) => $b['score'] <=> $a['score']);

    // Clear and reinsert
    db_query('DELETE FROM leaderboard WHERE course_id=?', [$courseId]);
    $rank = 1;
    foreach ($rows as $r) {
        db_insert('leaderboard', [
            'course_id'      => $courseId,
            'student_id'     => $r['student_id'],
            'score'          => $r['score'],
            'rank_position'  => $rank++,
        ]);
    }
}

/** Check if a student satisfies course completion rules. Returns bool. */
function student_meets_completion(int $studentId, int $courseId): bool {
    sync_student_performance($studentId, $courseId);
    $p = db_one('SELECT * FROM student_performance WHERE student_id=? AND course_id=?',
                [$studentId, $courseId]);
    if (!$p) return false;

    $minAtt  = (float) setting('min_attendance_pct', 75);
    $minPerf = (float) setting('min_performance_pct', 60);

    return ((float)$p['attendance_pct'] >= $minAtt) && ((float)$p['overall_score'] >= $minPerf);
}