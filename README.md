# CatchIt

CatchIt is a PHP and MySQL campus lost-and-found application. Students can report lost or found belongings, search approved listings, and submit claims. Administrators can manage listings, accounts, and claims.

## Features

- Student registration, sign-in, and profile management
- Lost and found item reports, including optional photos
- Search and browse listings by item, category, and location
- Claim workflow for found items
- Admin tools for managing items, users, and claims
- Public Help and Feedback pages with saved ratings and comments
- Responsive interface with light and dark themes

## Requirements

- XAMPP, or another local stack with Apache, PHP 7.4+ and MySQL/MariaDB
- PHP `mysqli` support (and `mysqlnd` for `mysqli_stmt_get_result`)
- A modern browser

Composer is used for PHPMailer SMTP delivery. The Inter font is loaded from Google Fonts when an internet connection is available; the site falls back to sans-serif fonts otherwise.

## Run locally with XAMPP

1. Place the project folder inside XAMPP's `htdocs` directory. For example:

   ```text
   C:\xampp\htdocs\Campus-Lost-And-Found
   ```

   If your checkout has an additional nested `Campus-Lost-And-Found` folder, use that inner folder as the project root.

2. Start **Apache** and **MySQL** from the XAMPP Control Panel.

3. Open phpMyAdmin at `http://localhost/phpmyadmin`, select **Import**, choose `database/campus_lost_found.sql`, and run the import. The SQL file creates the `campus_lost_found` database, its tables (including feedback and password-reset storage), and the initial item categories. For existing installations, select the database and import `database/add_password_resets.sql` and `database/add_feedback.sql` to add the corresponding tables.

4. Install the PHP dependency from the project directory:

   ```text
   composer install
   ```

5. Check `config/database.php`. The default local settings are:

   ```php
   $host = "127.0.0.1";
   $username = "root";
   $password = "";
   $database = "campus_lost_found";
   $port = 3306;
   ```

   Update these values if your MySQL credentials or port are different. The Help page contains fictional sample contact details; replace them with verified campus contacts before publishing.

6. Configure SMTP and app settings in the environment of the Apache/PHP process. Do not put SMTP secrets in frontend files or commit them to the project:

   | Variable | Purpose |
   | --- | --- |
   | `SMTP_HOST` | SMTP server hostname |
   | `SMTP_PORT` | SMTP port (commonly `587` for TLS or `465` for SSL) |
   | `SMTP_USERNAME` | SMTP account; optional only if the server does not require authentication |
   | `SMTP_PASSWORD` | SMTP password; optional only if the server does not require authentication |
   | `SMTP_ENCRYPTION` | `tls` (default) or `ssl`; `none` is limited to non-production environments |
   | `SMTP_FROM_EMAIL` | Verified sender email address |
   | `SMTP_FROM_NAME` | Optional sender display name (defaults to `CatchIt`) |
   | `CATCHIT_ADMIN_EMAIL` | Admin address that receives new report notifications |
   | `CATCHIT_APP_KEY` | Long random secret used to hash reset-request rate-limit identifiers |
   | `APP_BASE_URL` | Public base URL of this app, without a trailing slash |
   | `CATCHIT_ENV` | Set to `production` to require an explicitly configured HTTPS `APP_BASE_URL` |

   In production, set `APP_BASE_URL` to the canonical `https://` site URL. Keep secrets in server environment settings or a protected configuration outside the public web root. Restart Apache after changing its environment. SMTP delivery failures are logged to the PHP error log; report pages explicitly indicate when a notification could not be delivered. A reset-request page never confirms that a message was sent or whether an account exists.

7. Open the site in your browser. For the current nested XAMPP checkout, the URL is:

   ```text
   http://localhost/Campus-Lost-And-Found/Campus-Lost-And-Found/
   ```

   If you placed the project directly in `htdocs/Campus-Lost-And-Found`, use `http://localhost/Campus-Lost-And-Found/` instead.

8. To verify the database connection, open:

   ```text
   http://localhost/Campus-Lost-And-Found/Campus-Lost-And-Found/test_database.php
   ```

   A successful check reports the connected database and item count.

## Create an administrator account

Registration creates student accounts. For a local development database, register an account, then use phpMyAdmin's SQL tab to grant it the admin role:

```sql
UPDATE users
SET role = 'admin'
WHERE email = 'admin@example.com';
```

Replace the email with the address used during registration. Do not expose a self-service admin role change in a public deployment.

## Project layout

```text
admin/       Admin dashboard and management pages
config/      Database connection settings
css/         Shared styles and themes
database/    Database schema and starter categories
includes/    Shared navigation, layout, helpers, and auth checks
js/          Theme and interface interactions
student/     Student dashboard, reports, search, claims, and profile
uploads/     Uploaded item images
index.php    Public listing page
help.php     Public Help and sample contact information
feedback.php Public feedback form
login.php    Sign-in
forgot_password.php Password reset requests
reset_password.php Token-based password reset
register.php Account registration
composer.json PHPMailer dependency
```

## Notes

- Uploaded item images are stored under `uploads/items/`. Ensure this directory is writable by the web server when using item photo uploads.
- `test_connection.php` and `test_database.php` are diagnostic pages intended for local development. Remove or restrict them before deploying publicly.
- The database credentials shown above are suitable only for a default local XAMPP setup. Configure secure credentials and server settings before any production deployment.
