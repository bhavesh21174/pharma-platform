USE pharma_platform;

-- Restores the documented Password@123 password for seeded demo accounts.
-- Run only in a development database; these are public demo credentials.
INSERT INTO users (full_name, email, mobile, password_hash, user_type, status, email_verified)
VALUES (
  'Super Admin',
  'superadmin@pharma.local',
  '9000000001',
  '$2y$10$LosVlY7qfqEcsvY2NNl4ZelpU2agu0U1S2TElgd0Zp1.nc4wklv0y',
  'SUPER_ADMIN',
  'active',
  1
)
ON DUPLICATE KEY UPDATE
  full_name = VALUES(full_name),
  password_hash = VALUES(password_hash),
  user_type = VALUES(user_type),
  status = VALUES(status),
  email_verified = VALUES(email_verified);

INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.name = 'SUPER_ADMIN'
WHERE u.email = 'superadmin@pharma.local';

UPDATE users
SET password_hash = '$2y$10$LosVlY7qfqEcsvY2NNl4ZelpU2agu0U1S2TElgd0Zp1.nc4wklv0y'
WHERE email IN (
  'superadmin@pharma.local',
  'admin@pharma.local',
  'finance@pharma.local',
  'mentor1@pharma.local',
  'student1@pharma.local'
);