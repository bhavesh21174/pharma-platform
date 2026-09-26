# Pharma Platform — Course + LMS + 1-to-1 Mentorship

A production-ready PHP 8 + MySQL platform for pharma courses, live mentor sessions,
assessments, payments, and administration. Uses vanilla HTML/CSS/JS + Bootstrap 5.

## Requirements

- PHP 8.1+
- MySQL 5.7+ / MariaDB 10.4+
- Apache (XAMPP or LAMP)
- OpenSSL PHP extension (Google JWT)
- cURL PHP extension (Razorpay + Google)
- fileinfo PHP extension (uploads)

## Local setup (XAMPP)

1. Clone into `C:\xampp\htdocs\pharma-platform\`.
2. Start Apache + MySQL in XAMPP.
3. Open phpMyAdmin → import `database/schema.sql` → then `database/seed.sql`.
4. Copy `.env.example` → `.env` and fill DB credentials.
5. Visit `http://localhost/pharma-platform/`.

For an existing database, import `database/course-enrollment-migration.sql` once before using course deposits, mentor allocation requests, and midpoint installments. Configure Razorpay in admin Settings → Payment. Configure Google Calendar in admin Settings → Google to generate Google Meet links.

### Course payment and mentoring cycle

- Admins set each course to 30, 60, or 90 days.
- Students select a preferred mentor and pay 50% of the course fee.
- A verified deposit creates a pending allocation request for an admin. The admin confirms a mentor; the midpoint balance date is calculated from the deposit date.
- At 15, 30, or 45 days respectively, the student gets an in-panel notification and private announcement for the remaining 50%. Schedule `php corn/course-installment-reminders.php` daily to deliver reminders.
- After mentor allocation, students choose available future slots. Google Meet creation requires Google Calendar credentials. Mentors can enter a Google Meet or Zoom link and attach the provider recording URL while marking attendance. Automatic cloud recording still needs the provider's Workspace/Zoom recording API setup.

### Demo logins (from seed.sql) — CHANGE IN PRODUCTION

| Role     | Email                  | Password     |
|----------|------------------------|--------------|
| Super Admin | superadmin@pharma.local | Password@123 |
| Admin    | admin@pharma.local     | Password@123 |
| Finance  | finance@pharma.local   | Password@123 |
| Mentor   | mentor1@pharma.local   | Password@123 |
| Student  | student1@pharma.local  | Password@123 |

If you already imported the seed file before this password fix, import
`database/fix-demo-logins.sql` in phpMyAdmin to restore these development logins.

## Configuration

- **DB:** `config/database.php` (reads `.env`).
- **Site settings:** admin panel → `Settings`.
- **Razorpay:** admin → Settings → Payment, OR `.env`.
- **Google Calendar/Meet:** admin → Settings → Google, OR `.env`.
  - Create a service account in Google Cloud Console.
  - Share the target calendar with the service account email.
  - Grant `https://www.googleapis.com/auth/calendar` scope.

### Demo payments

When `APP_ENV=development` and Razorpay keys are missing, checkout uses a clearly labeled simulated payment. It records no real charge but exercises the same enrollment, admin allocation, invoice, and installment workflow. Set the `DEMO_PAYMENTS_ENABLED` environment variable to `0` to disable it. Demo payments are always disabled when `APP_ENV=production`.

## Deploying to production

1. Set `APP_ENV=production` and `display_errors=0` in `config/config.php`.
2. Set strong DB credentials + JWT-like secrets in `.env`.
3. Point domain → `public/` if hosting with subfolder restriction; otherwise use root `.htaccess`.
4. Enable HTTPS (Let's Encrypt) and uncomment HSTS in `.htaccess`.
5. Set `secure` cookies: `session_set_cookie_params(['secure'=>true,'httponly'=>true,'samesite'=>'Lax'])`.
6. Schedule cron jobs (Linux):