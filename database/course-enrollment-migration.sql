USE pharma_platform;

-- One-time upgrade for existing databases. Back up the database before running.
ALTER TABLE courses
  ADD COLUMN duration_days SMALLINT UNSIGNED NOT NULL DEFAULT 30 AFTER duration_weeks;

UPDATE courses
SET duration_days = CASE
  WHEN duration_weeks <= 4 THEN 30
  WHEN duration_weeks <= 8 THEN 60
  ELSE 90
END;

ALTER TABLE enrollments
  MODIFY status ENUM('pending_payment','pending_admin','active','completed','cancelled','refunded') DEFAULT 'pending_payment',
  ADD COLUMN requested_mentor_id BIGINT UNSIGNED DEFAULT NULL AFTER mentor_id,
  ADD COLUMN total_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER requested_mentor_id,
  ADD COLUMN paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_fee,
  ADD COLUMN second_installment_due_at DATETIME DEFAULT NULL AFTER paid_amount,
  ADD COLUMN second_reminder_sent_at DATETIME DEFAULT NULL AFTER second_installment_due_at,
  ADD CONSTRAINT fk_enrollment_requested_mentor FOREIGN KEY (requested_mentor_id) REFERENCES mentors(id) ON DELETE SET NULL;

ALTER TABLE payments
  ADD COLUMN installment_no TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER status;

ALTER TABLE meetings
  ADD COLUMN recording_url VARCHAR(500) DEFAULT NULL AFTER notes;