<?php
require_once __DIR__ . '/config.php';

/**
 * Create a Google Calendar event with a Meet link.
 * @return array{0:string,1:string} [event_id, meet_url]
 */
function google_create_meeting(int $mentorId, int $studentId, int $courseId, string $date, string $startTime, string $endTime): array {
    $cfg = google_config();
    if (!$cfg['client_email'] || !$cfg['private_key']) return [null, null];

    $mentor  = db_one('SELECT m.*, u.full_name, u.email FROM mentors m JOIN users u ON u.id=m.user_id WHERE m.id=?',[$mentorId]);
    $student = db_one('SELECT s.*, u.full_name, u.email FROM students s JOIN users u ON u.id=s.user_id WHERE s.id=?',[$studentId]);
    $course  = db_one('SELECT name FROM courses WHERE id=?',[$courseId]);
    if (!$mentor || !$student || !$course) throw new RuntimeException('Data missing.');

    $tz = $cfg['timezone'];
    $start = (new DateTime("$date $startTime", new DateTimeZone($tz)))->format('c');
    $end   = (new DateTime("$date $endTime",   new DateTimeZone($tz)))->format('c');

    $event = [
        'summary' => 'Mentor Session: '.$course['name'].' — '.$student['full_name'],
        'description' => 'Pharma Academy 1-to-1 mentorship session.',
        'start' => ['dateTime'=>$start,'timeZone'=>$tz],
        'end'   => ['dateTime'=>$end,  'timeZone'=>$tz],
        'attendees' => [
            ['email'=>$mentor['email']],
            ['email'=>$student['email']],
        ],
        'conferenceData' => [
            'createRequest' => [
                'requestId' => 'PA-'.bin2hex(random_bytes(6)),
                'conferenceSolutionKey' => ['type'=>'hangoutsMeet'],
            ],
        ],
        'reminders' => [
            'useDefault' => false,
            'overrides'  => [
                ['method'=>'email','minutes'=>24*60],
                ['method'=>'popup','minutes'=>15],
            ],
        ],
    ];

    $token = google_access_token();
    $url = 'https://www.googleapis.com/calendar/v3/calendars/'
         . rawurlencode($cfg['calendar_id'])
         . '/events?conferenceDataVersion=1&sendUpdates=all';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$token, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($event),
        CURLOPT_TIMEOUT => 30,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($resp === false || $code >= 400) throw new RuntimeException('Google Calendar error: '.$resp);

    $data = json_decode($resp, true) ?: [];
    $eventId = $data['id'] ?? null;
    $meetUrl = $data['hangoutLink']
             ?? $data['conferenceData']['entryPoints'][0]['uri']
             ?? null;
    return [$eventId, $meetUrl];
}