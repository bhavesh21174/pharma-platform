<?php
require_once __DIR__ . '/calendar.php';

function google_delete_event(string $eventId): bool {
    $cfg = google_config();
    if (!$cfg['client_email']) return false;
    $token = google_access_token();
    $url = 'https://www.googleapis.com/calendar/v3/calendars/'
         . rawurlencode($cfg['calendar_id']) . '/events/' . rawurlencode($eventId);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'DELETE',
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer '.$token],
        CURLOPT_TIMEOUT        => 30,
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code >= 200 && $code < 300;
}