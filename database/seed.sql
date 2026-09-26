USE pharma_platform;

-- Roles
INSERT INTO roles (name, description) VALUES
('SUPER_ADMIN','Full platform control'),
('ADMIN','Platform administrator'),
('FINANCE','Finance & payments only'),
('HR','HR & employees'),
('COURSE_MANAGER','Courses & content'),
('MENTOR','Mentor'),
('STUDENT','Student');

-- Permissions
INSERT INTO permissions (code, module, label) VALUES
('student.view','student','View students'),
('student.create','student','Create student'),
('student.edit','student','Edit student'),
('student.delete','student','Delete student'),
('mentor.view','mentor','View mentors'),
('mentor.create','mentor','Create mentor'),
('mentor.edit','mentor','Edit mentor'),
('mentor.delete','mentor','Delete mentor'),
('course.view','course','View courses'),
('course.create','course','Create course'),
('course.edit','course','Edit course'),
('course.delete','course','Delete course'),
('finance.view','finance','View finance'),
('payment.view','payment','View payments'),
('payment.refund','payment','Refund payment'),
('invoice.view','invoice','View invoices'),
('finance.report','finance','Finance reports'),
('assignment.create','assignment','Create assignment'),
('assignment.review','assignment','Review assignment'),
('assessment.create','assessment','Create assessment'),
('assessment.review','assessment','Review assessment'),
('attendance.view','attendance','View attendance'),
('attendance.manage','attendance','Manage attendance'),
('employee.create','employee','Create employee'),
('employee.edit','employee','Edit employee'),
('settings.view','settings','View settings'),
('settings.edit','settings','Edit settings'),
('role.manage','role','Manage roles & permissions'),
('audit.view','audit','View audit logs');

-- SUPER_ADMIN gets all
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name='SUPER_ADMIN';

-- ADMIN gets all except settings.edit (customize as needed)
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name='ADMIN';

-- FINANCE only finance-related
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.name='FINANCE'
  AND p.code IN ('finance.view','payment.view','payment.refund','invoice.view','finance.report');

-- COURSE_MANAGER
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.name='COURSE_MANAGER'
  AND p.code IN ('course.view','course.create','course.edit','assignment.create','assessment.create');

-- MENTOR
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.name='MENTOR'
  AND p.code IN ('assignment.review','assessment.review','attendance.manage','student.view');

-- STUDENT
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p
WHERE r.name='STUDENT' AND p.code = 'course.view';

-- Users (password = Password@123)
INSERT INTO users (full_name, email, mobile, password_hash, user_type, status, email_verified) VALUES
('Super Admin','superadmin@pharma.local','9000000001','$2y$10$LosVlY7qfqEcsvY2NNl4ZelpU2agu0U1S2TElgd0Zp1.nc4wklv0y','SUPER_ADMIN','active',1),
('Admin User','admin@pharma.local','9000000002','$2y$10$LosVlY7qfqEcsvY2NNl4ZelpU2agu0U1S2TElgd0Zp1.nc4wklv0y','ADMIN','active',1),
('Finance User','finance@pharma.local','9000000003','$2y$10$LosVlY7qfqEcsvY2NNl4ZelpU2agu0U1S2TElgd0Zp1.nc4wklv0y','FINANCE','active',1),
('Dr. Anil Kumar','mentor1@pharma.local','9000000004','$2y$10$LosVlY7qfqEcsvY2NNl4ZelpU2agu0U1S2TElgd0Zp1.nc4wklv0y','MENTOR','active',1),
('Riya Sharma','student1@pharma.local','9000000005','$2y$10$LosVlY7qfqEcsvY2NNl4ZelpU2agu0U1S2TElgd0Zp1.nc4wklv0y','STUDENT','active',1);

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u JOIN roles r ON r.name = u.user_type;

INSERT INTO students (user_id, gender, qualification, college) VALUES
((SELECT id FROM users WHERE email='student1@pharma.local'), 'female','B.Pharm','NIPER');

INSERT INTO mentors (user_id, qualification, specialization, experience_years, bio, max_students) VALUES
((SELECT id FROM users WHERE email='mentor1@pharma.local'),'PhD Pharmacology','Clinical Pharmacology',10,'Senior pharma mentor',20);

INSERT INTO employees (user_id, department, designation) VALUES
((SELECT id FROM users WHERE email='finance@pharma.local'),'Finance','Finance Executive');

-- Sample course
INSERT INTO courses (course_code, name, short_desc, description, duration_weeks, duration_days, price, discount_price, level, status)
VALUES ('PHM101','Clinical Pharmacology Essentials','Beginner friendly course on clinical pharmacology.',
'Learn the fundamentals of clinical pharmacology with 1-to-1 mentorship.',5,30,9999.00,7999.00,'beginner','published');

INSERT INTO settings (skey, svalue, sgroup) VALUES
('site_name','Pharma Academy','general'),
('site_url','http://localhost/pharma-platform','general'),
('timezone','Asia/Kolkata','general'),
('weight_assessment','50','ranking'),
('weight_assignment','25','ranking'),
('weight_attendance','15','ranking'),
('weight_mentor_eval','10','ranking'),
('min_attendance_pct','75','completion'),
('min_performance_pct','60','completion');