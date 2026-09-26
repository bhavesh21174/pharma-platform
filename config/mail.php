<?php
function send_mail(string $to, string $subject, string $htmlBody): bool {
    // TODO: replace with PHPMailer/SMTP in production.
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SITE_NAME . " <no-reply@pharma.local>\r\n";
    return @mail($to, $subject, $htmlBody, $headers);
}
