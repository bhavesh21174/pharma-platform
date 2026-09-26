<?php
// User types
const USER_SUPER_ADMIN = 'SUPER_ADMIN';
const USER_ADMIN       = 'ADMIN';
const USER_FINANCE     = 'FINANCE';
const USER_HR          = 'HR';
const USER_COURSE_MGR  = 'COURSE_MANAGER';
const USER_MENTOR      = 'MENTOR';
const USER_STUDENT     = 'STUDENT';

// Payment
const PAY_PENDING   = 'pending';
const PAY_SUCCESS   = 'successful';
const PAY_FAILED    = 'failed';
const PAY_REFUNDED  = 'refunded';

// Meeting statuses
const MEET_SCHEDULED   = 'scheduled';
const MEET_COMPLETED   = 'completed';
const MEET_CANCELLED   = 'cancelled';
const MEET_RESCHEDULED = 'rescheduled';
const MEET_NOSHOW      = 'no_show';

// 24-hour rule for availability changes (business rule #5)
const AVAILABILITY_MIN_HOURS = 24;