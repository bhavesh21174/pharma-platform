<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/permission-check.php';
require_once __DIR__ . '/role-check.php';      // <-- ADDED (fixes requireRole)
require_once __DIR__ . '/audit-log.php';
require_once __DIR__ . '/upload.php';          // <-- if you haven't created this yet, see below
requireLogin();