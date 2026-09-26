<?php
function upload_file(array $file, string $subdir, array $allowedMime = ALLOWED_MIME, int $maxBytes = UPLOAD_MAX_BYTES): string {
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed.');
    if ($file['size'] > $maxBytes) throw new RuntimeException('File too large.');

    // MIME sniffing — do NOT trust extension
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowedMime, true)) throw new RuntimeException('File type not allowed.');

    $ext = match($mime) {
        'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp',
        'application/pdf'=>'pdf','application/zip','application/x-zip-compressed'=>'zip',
        'application/msword'=>'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'=>'docx',
        default => 'bin',
    };
    $dir = UPLOAD_PATH . '/' . $subdir;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Could not save file.');
    return $subdir . '/' . $name;
}