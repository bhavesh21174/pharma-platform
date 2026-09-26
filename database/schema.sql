-- =====================================================
-- PHARMA PLATFORM — Full Schema
-- =====================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS pharma_platform
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pharma_platform;

-- ---------- USERS / RBAC ----------
CREATE TABLE users (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  full_name     VARCHAR(150) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  mobile        VARCHAR(20)  DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  user_type     ENUM('SUPER_ADMIN','ADMIN','FINANCE','HR','COURSE_MANAGER','MENTOR','STUDENT') NOT NULL,
  status        ENUM('active','inactive','suspended') DEFAULT 'active',
  profile_photo VARCHAR(255) DEFAULT NULL,
  email_verified TINYINT(1) DEFAULT 0,
  reset_token   VARCHAR(100) DEFAULT NULL,
  reset_expires DATETIME DEFAULT NULL,
  remember_token VARCHAR(100) DEFAULT NULL,
  last_login    DATETIME DEFAULT NULL,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_type (user_type),
  INDEX idx_users_status (status)
) ENGINE=InnoDB;

CREATE TABLE roles (
  id          INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name        VARCHAR(80) NOT NULL UNIQUE,
  description VARCHAR(255) DEFAULT NULL,
  is_system   TINYINT(1) DEFAULT 1,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE permissions (
  id       INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  code     VARCHAR(100) NOT NULL UNIQUE,   -- e.g. student.create
  module   VARCHAR(50)  NOT NULL,
  label    VARCHAR(120) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
  role_id       INT UNSIGNED NOT NULL,
  permission_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE user_roles (
  user_id BIGINT UNSIGNED NOT NULL,
  role_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, role_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE user_permissions (
  user_id       BIGINT UNSIGNED NOT NULL,
  permission_id INT UNSIGNED NOT NULL,
  allowed       TINYINT(1) DEFAULT 1,   -- 1 = grant, 0 = deny override
  PRIMARY KEY (user_id, permission_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- PROFILES ----------
CREATE TABLE students (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id         BIGINT UNSIGNED NOT NULL UNIQUE,
  dob             DATE DEFAULT NULL,
  gender          ENUM('male','female','other') DEFAULT NULL,
  address         TEXT DEFAULT NULL,
  qualification   VARCHAR(150) DEFAULT NULL,
  college         VARCHAR(200) DEFAULT NULL,
  emergency_contact VARCHAR(50) DEFAULT NULL,
  created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE mentors (
  id                BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id           BIGINT UNSIGNED NOT NULL UNIQUE,
  qualification     VARCHAR(200) DEFAULT NULL,
  specialization    VARCHAR(200) DEFAULT NULL,
  experience_years  INT DEFAULT 0,
  bio               TEXT DEFAULT NULL,
  skills            TEXT DEFAULT NULL,
  max_students      INT DEFAULT 20,
  status            ENUM('active','inactive') DEFAULT 'active',
  created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE employees (
  id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id      BIGINT UNSIGNED NOT NULL UNIQUE,
  department   VARCHAR(100) DEFAULT NULL,
  designation  VARCHAR(100) DEFAULT NULL,
  status       ENUM('active','inactive') DEFAULT 'active',
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- COURSES ----------
CREATE TABLE courses (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  course_code     VARCHAR(50) UNIQUE,
  name            VARCHAR(200) NOT NULL,
  short_desc      VARCHAR(500) DEFAULT NULL,
  description     LONGTEXT DEFAULT NULL,
  thumbnail       VARCHAR(255) DEFAULT NULL,
  banner          VARCHAR(255) DEFAULT NULL,
  duration_weeks  INT DEFAULT 4,
  duration_days   SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  price           DECIMAL(10,2) DEFAULT 0.00,
  discount_price  DECIMAL(10,2) DEFAULT NULL,
  level           ENUM('beginner','intermediate','advanced') DEFAULT 'beginner',
  eligibility     TEXT DEFAULT NULL,
  outcomes        TEXT DEFAULT NULL,
  benefits        TEXT DEFAULT NULL,
  syllabus        LONGTEXT DEFAULT NULL,
  status          ENUM('draft','published','archived') DEFAULT 'draft',
  start_date      DATE DEFAULT NULL,
  end_date        DATE DEFAULT NULL,
  created_by      BIGINT UNSIGNED DEFAULT NULL,
  created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_courses_status (status),
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE course_modules (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  course_id   BIGINT UNSIGNED NOT NULL,
  title       VARCHAR(200) NOT NULL,
  description TEXT DEFAULT NULL,
  position    INT DEFAULT 1,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  INDEX idx_modules_course (course_id)
) ENGINE=InnoDB;

CREATE TABLE lessons (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  module_id   BIGINT UNSIGNED NOT NULL,
  title       VARCHAR(200) NOT NULL,
  content     LONGTEXT DEFAULT NULL,
  video_url   VARCHAR(500) DEFAULT NULL,
  duration_minutes INT DEFAULT 0,
  position    INT DEFAULT 1,
  status      ENUM('draft','published') DEFAULT 'published',
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (module_id) REFERENCES course_modules(id) ON DELETE CASCADE,
  INDEX idx_lessons_module (module_id)
) ENGINE=InnoDB;

CREATE TABLE study_materials (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  course_id   BIGINT UNSIGNED NOT NULL,
  module_id   BIGINT UNSIGNED DEFAULT NULL,
  title       VARCHAR(200) NOT NULL,
  file_path   VARCHAR(500) NOT NULL,
  file_type   VARCHAR(50) DEFAULT NULL,
  file_size   INT DEFAULT 0,
  uploaded_by BIGINT UNSIGNED DEFAULT NULL,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (module_id) REFERENCES course_modules(id) ON DELETE SET NULL,
  FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- ENROLLMENT & MENTOR ASSIGNMENT ----------
CREATE TABLE enrollments (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  student_id  BIGINT UNSIGNED NOT NULL,
  course_id   BIGINT UNSIGNED NOT NULL,
  mentor_id   BIGINT UNSIGNED DEFAULT NULL,
  status      ENUM('pending_payment','pending_admin','active','completed','cancelled','refunded') DEFAULT 'pending_payment',
  requested_mentor_id BIGINT UNSIGNED DEFAULT NULL,
  total_fee   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  second_installment_due_at DATETIME DEFAULT NULL,
  second_reminder_sent_at DATETIME DEFAULT NULL,
  progress    DECIMAL(5,2) DEFAULT 0.00,
  enrolled_at DATETIME DEFAULT NULL,
  completed_at DATETIME DEFAULT NULL,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_student_course (student_id, course_id),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id)  REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (mentor_id)  REFERENCES mentors(id) ON DELETE SET NULL,
  FOREIGN KEY (requested_mentor_id) REFERENCES mentors(id) ON DELETE SET NULL,
  INDEX idx_enroll_status (status)
) ENGINE=InnoDB;

CREATE TABLE mentor_assignments (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  mentor_id   BIGINT UNSIGNED NOT NULL,
  student_id  BIGINT UNSIGNED NOT NULL,
  course_id   BIGINT UNSIGNED NOT NULL,
  assigned_by BIGINT UNSIGNED DEFAULT NULL,
  status      ENUM('active','completed','reassigned') DEFAULT 'active',
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (mentor_id)  REFERENCES mentors(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id)  REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_ma_mentor (mentor_id, status),
  INDEX idx_ma_student (student_id, status)
) ENGINE=InnoDB;

-- ---------- MENTOR AVAILABILITY / LEAVE / SLOTS ----------
CREATE TABLE mentor_availability (
  id         BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  mentor_id  BIGINT UNSIGNED NOT NULL,
  avail_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time   TIME NOT NULL,
  is_blocked TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE,
  INDEX idx_avail_mentor_date (mentor_id, avail_date)
) ENGINE=InnoDB;

CREATE TABLE mentor_leaves (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  mentor_id   BIGINT UNSIGNED NOT NULL,
  leave_date  DATE NOT NULL,
  start_time  TIME DEFAULT NULL,
  end_time    TIME DEFAULT NULL,
  reason      TEXT DEFAULT NULL,
  status      ENUM('pending','approved','rejected') DEFAULT 'pending',
  approved_by BIGINT UNSIGNED DEFAULT NULL,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE,
  FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE meeting_slots (
  id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  mentor_id    BIGINT UNSIGNED NOT NULL,
  slot_date    DATE NOT NULL,
  start_time   TIME NOT NULL,
  end_time     TIME NOT NULL,
  status       ENUM('available','booked','blocked') DEFAULT 'available',
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_slot (mentor_id, slot_date, start_time),
  FOREIGN KEY (mentor_id) REFERENCES mentors(id) ON DELETE CASCADE,
  INDEX idx_slot_status (mentor_id, slot_date, status)
) ENGINE=InnoDB;

CREATE TABLE meetings (
  id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  student_id     BIGINT UNSIGNED NOT NULL,
  mentor_id      BIGINT UNSIGNED NOT NULL,
  course_id      BIGINT UNSIGNED NOT NULL,
  slot_id        BIGINT UNSIGNED DEFAULT NULL,
  meeting_date   DATE NOT NULL,
  start_time     TIME NOT NULL,
  end_time       TIME NOT NULL,
  gcal_event_id  VARCHAR(255) DEFAULT NULL,
  meet_url       VARCHAR(500) DEFAULT NULL,
  status         ENUM('scheduled','completed','cancelled','rescheduled','no_show') DEFAULT 'scheduled',
  notes          TEXT DEFAULT NULL,
  recording_url  VARCHAR(500) DEFAULT NULL,
  created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (mentor_id)  REFERENCES mentors(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id)  REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (slot_id)    REFERENCES meeting_slots(id) ON DELETE SET NULL,
  INDEX idx_meet_student (student_id, meeting_date),
  INDEX idx_meet_mentor  (mentor_id, meeting_date)
) ENGINE=InnoDB;

-- ---------- ATTENDANCE ----------
CREATE TABLE attendance (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  student_id  BIGINT UNSIGNED NOT NULL,
  mentor_id   BIGINT UNSIGNED NOT NULL,
  course_id   BIGINT UNSIGNED NOT NULL,
  meeting_id  BIGINT UNSIGNED DEFAULT NULL,
  att_date    DATE NOT NULL,
  start_time  TIME DEFAULT NULL,
  end_time    TIME DEFAULT NULL,
  status      ENUM('present','absent','late','excused') DEFAULT 'present',
  notes       TEXT DEFAULT NULL,
  marked_by   BIGINT UNSIGNED DEFAULT NULL,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_att (student_id, course_id, att_date, start_time),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (mentor_id)  REFERENCES mentors(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id)  REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE SET NULL,
  FOREIGN KEY (marked_by)  REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- ASSIGNMENTS ----------
CREATE TABLE assignments (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  course_id     BIGINT UNSIGNED NOT NULL,
  module_id     BIGINT UNSIGNED DEFAULT NULL,
  title         VARCHAR(200) NOT NULL,
  description   TEXT DEFAULT NULL,
  instructions  TEXT DEFAULT NULL,
  attachment    VARCHAR(500) DEFAULT NULL,
  start_date    DATETIME DEFAULT NULL,
  due_date      DATETIME NOT NULL,
  max_marks     INT DEFAULT 100,
  created_by    BIGINT UNSIGNED NOT NULL,
  status        ENUM('draft','published','closed') DEFAULT 'published',
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (module_id) REFERENCES course_modules(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_ass_course (course_id, status)
) ENGINE=InnoDB;

CREATE TABLE assignment_submissions (
  id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  assignment_id  BIGINT UNSIGNED NOT NULL,
  student_id     BIGINT UNSIGNED NOT NULL,
  file_path      VARCHAR(500) DEFAULT NULL,
  text_response  LONGTEXT DEFAULT NULL,
  submitted_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  marks          DECIMAL(6,2) DEFAULT NULL,
  feedback       TEXT DEFAULT NULL,
  status         ENUM('pending','submitted','under_review','checked','resubmit','late') DEFAULT 'submitted',
  reviewed_by    BIGINT UNSIGNED DEFAULT NULL,
  reviewed_at    DATETIME DEFAULT NULL,
  FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id)    REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by)   REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY uq_sub (assignment_id, student_id, submitted_at)
) ENGINE=InnoDB;

-- ---------- ASSESSMENTS ----------
CREATE TABLE assessments (
  id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  course_id      BIGINT UNSIGNED NOT NULL,
  module_id      BIGINT UNSIGNED DEFAULT NULL,
  title          VARCHAR(200) NOT NULL,
  description    TEXT DEFAULT NULL,
  time_limit_min INT DEFAULT 30,
  total_marks    INT DEFAULT 100,
  negative_mark  DECIMAL(5,2) DEFAULT 0.00,
  attempt_limit  INT DEFAULT 1,
  start_at       DATETIME DEFAULT NULL,
  end_at         DATETIME DEFAULT NULL,
  created_by     BIGINT UNSIGNED NOT NULL,
  status         ENUM('draft','published','closed') DEFAULT 'published',
  created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (module_id) REFERENCES course_modules(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE assessment_questions (
  id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  assessment_id  BIGINT UNSIGNED NOT NULL,
  question_type  ENUM('mcq','multi','truefalse','short','descriptive') DEFAULT 'mcq',
  question_text  TEXT NOT NULL,
  options_json   TEXT DEFAULT NULL,   -- JSON array for MCQs
  correct_answer TEXT DEFAULT NULL,   -- for auto-scored
  marks          INT DEFAULT 1,
  position       INT DEFAULT 1,
  FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE assessment_attempts (
  id             BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  assessment_id  BIGINT UNSIGNED NOT NULL,
  student_id     BIGINT UNSIGNED NOT NULL,
  started_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
  submitted_at   DATETIME DEFAULT NULL,
  status         ENUM('in_progress','submitted','evaluated') DEFAULT 'in_progress',
  score          DECIMAL(6,2) DEFAULT NULL,
  percentage     DECIMAL(5,2) DEFAULT NULL,
  FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id)    REFERENCES students(id) ON DELETE CASCADE,
  INDEX idx_att_student (student_id, assessment_id)
) ENGINE=InnoDB;

CREATE TABLE assessment_answers (
  id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  attempt_id   BIGINT UNSIGNED NOT NULL,
  question_id  BIGINT UNSIGNED NOT NULL,
  answer       TEXT DEFAULT NULL,
  is_correct   TINYINT(1) DEFAULT NULL,
  awarded      DECIMAL(6,2) DEFAULT NULL,
  FOREIGN KEY (attempt_id)  REFERENCES assessment_attempts(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES assessment_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE assessment_results (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  attempt_id    BIGINT UNSIGNED NOT NULL UNIQUE,
  assessment_id BIGINT UNSIGNED NOT NULL,
  student_id    BIGINT UNSIGNED NOT NULL,
  score         DECIMAL(6,2) DEFAULT 0,
  total         INT DEFAULT 0,
  percentage    DECIMAL(5,2) DEFAULT 0,
  result        ENUM('pass','fail') DEFAULT 'pass',
  published_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (attempt_id)    REFERENCES assessment_attempts(id) ON DELETE CASCADE,
  FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id)    REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- PERFORMANCE & LEADERBOARD ----------
CREATE TABLE student_performance (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  student_id      BIGINT UNSIGNED NOT NULL,
  course_id       BIGINT UNSIGNED NOT NULL,
  assignment_score DECIMAL(6,2) DEFAULT 0,
  assessment_score DECIMAL(6,2) DEFAULT 0,
  attendance_pct   DECIMAL(5,2) DEFAULT 0,
  mentor_eval      DECIMAL(5,2) DEFAULT 0,
  overall_score    DECIMAL(5,2) DEFAULT 0,
  notes            TEXT DEFAULT NULL,
  updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_perf (student_id, course_id),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id)  REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE leaderboard (
  id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  course_id    BIGINT UNSIGNED NOT NULL,
  student_id   BIGINT UNSIGNED NOT NULL,
  score        DECIMAL(6,2) DEFAULT 0,
  rank_position INT DEFAULT 0,
  updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_lb (course_id, student_id),
  FOREIGN KEY (course_id)  REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- FEEDBACK ----------
CREATE TABLE feedback (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  student_id  BIGINT UNSIGNED NOT NULL,
  mentor_id   BIGINT UNSIGNED DEFAULT NULL,
  course_id   BIGINT UNSIGNED DEFAULT NULL,
  meeting_id  BIGINT UNSIGNED DEFAULT NULL,
  type        ENUM('mentor','session','course','platform') DEFAULT 'mentor',
  rating      TINYINT DEFAULT 5,
  comments    TEXT DEFAULT NULL,
  suggestions TEXT DEFAULT NULL,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (mentor_id)  REFERENCES mentors(id) ON DELETE SET NULL,
  FOREIGN KEY (course_id)  REFERENCES courses(id) ON DELETE SET NULL,
  FOREIGN KEY (meeting_id) REFERENCES meetings(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- PAYMENTS ----------
CREATE TABLE payments (
  id               BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  student_id       BIGINT UNSIGNED NOT NULL,
  course_id        BIGINT UNSIGNED NOT NULL,
  mentor_id        BIGINT UNSIGNED DEFAULT NULL,
  order_id         VARCHAR(100) DEFAULT NULL,   -- Razorpay order
  payment_id       VARCHAR(100) DEFAULT NULL,   -- Razorpay payment
  signature        VARCHAR(255) DEFAULT NULL,
  amount           DECIMAL(10,2) NOT NULL,
  currency         VARCHAR(10) DEFAULT 'INR',
  status           ENUM('pending','processing','successful','failed','refunded','partially_refunded') DEFAULT 'pending',
  installment_no   TINYINT UNSIGNED NOT NULL DEFAULT 1,
  method           VARCHAR(50) DEFAULT NULL,
  paid_at          DATETIME DEFAULT NULL,
  refund_status    VARCHAR(50) DEFAULT NULL,
  transaction_ref  VARCHAR(150) DEFAULT NULL,
  created_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_order (order_id),
  INDEX idx_pay_student (student_id, status),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id)  REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (mentor_id)  REFERENCES mentors(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE payment_transactions (
  id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  payment_id   BIGINT UNSIGNED NOT NULL,
  event_type   VARCHAR(80) NOT NULL,
  payload_json LONGTEXT DEFAULT NULL,
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE invoices (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  invoice_no    VARCHAR(50) NOT NULL UNIQUE,
  payment_id    BIGINT UNSIGNED NOT NULL,
  student_id    BIGINT UNSIGNED NOT NULL,
  amount        DECIMAL(10,2) NOT NULL,
  tax           DECIMAL(10,2) DEFAULT 0,
  total         DECIMAL(10,2) NOT NULL,
  issued_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
  pdf_path      VARCHAR(500) DEFAULT NULL,
  FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE refunds (
  id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  payment_id    BIGINT UNSIGNED NOT NULL,
  amount        DECIMAL(10,2) NOT NULL,
  reason        TEXT DEFAULT NULL,
  status        ENUM('pending','processed','failed') DEFAULT 'pending',
  processed_by  BIGINT UNSIGNED DEFAULT NULL,
  processed_at  DATETIME DEFAULT NULL,
  created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (payment_id)   REFERENCES payments(id) ON DELETE CASCADE,
  FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- NOTIFICATIONS & ANNOUNCEMENTS ----------
CREATE TABLE notifications (
  id         BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id    BIGINT UNSIGNED NOT NULL,
  title      VARCHAR(200) NOT NULL,
  message    TEXT NOT NULL,
  type       VARCHAR(50) DEFAULT 'info',
  link       VARCHAR(500) DEFAULT NULL,
  is_read    TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notif_user (user_id, is_read)
) ENGINE=InnoDB;

CREATE TABLE announcements (
  id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  title        VARCHAR(200) NOT NULL,
  description  LONGTEXT NOT NULL,
  attachment   VARCHAR(500) DEFAULT NULL,
  audience     ENUM('all','students','mentors','employees','course','mentor','student') DEFAULT 'all',
  course_id    BIGINT UNSIGNED DEFAULT NULL,
  mentor_id    BIGINT UNSIGNED DEFAULT NULL,
  student_id   BIGINT UNSIGNED DEFAULT NULL,
  publish_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  expire_at    DATETIME DEFAULT NULL,
  status       ENUM('draft','published','expired') DEFAULT 'published',
  created_by   BIGINT UNSIGNED NOT NULL,
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id)  REFERENCES courses(id) ON DELETE SET NULL,
  FOREIGN KEY (mentor_id)  REFERENCES mentors(id) ON DELETE SET NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- CERTIFICATES ----------
CREATE TABLE certificates (
  id               BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  certificate_id   VARCHAR(50) NOT NULL UNIQUE,   -- Public verification code
  student_id       BIGINT UNSIGNED NOT NULL,
  course_id        BIGINT UNSIGNED NOT NULL,
  student_name     VARCHAR(200) NOT NULL,
  course_name      VARCHAR(200) NOT NULL,
  completion_date  DATE NOT NULL,
  pdf_path         VARCHAR(500) DEFAULT NULL,
  verification_url VARCHAR(500) DEFAULT NULL,
  issued_by        BIGINT UNSIGNED DEFAULT NULL,
  issued_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id)  REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (issued_by)  REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- AUDIT LOG & SETTINGS ----------
CREATE TABLE audit_logs (
  id         BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id    BIGINT UNSIGNED DEFAULT NULL,
  action     VARCHAR(120) NOT NULL,
  module     VARCHAR(80) DEFAULT NULL,
  record_id  VARCHAR(50) DEFAULT NULL,
  old_data   LONGTEXT DEFAULT NULL,
  new_data   LONGTEXT DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_user (user_id),
  INDEX idx_audit_module (module),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE settings (
  id        INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  skey      VARCHAR(100) NOT NULL UNIQUE,
  svalue    LONGTEXT DEFAULT NULL,
  sgroup    VARCHAR(50) DEFAULT 'general',
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;