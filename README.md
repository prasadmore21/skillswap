# SkillSwap — Student Skill Exchange Platform

A peer-to-peer platform where students exchange skills instead of paying for
courses. Built with PHP 8, MySQL, and Bootstrap 5, per the accompanying
project proposal.

## Setup (local, via XAMPP)

**Important:** this project expects to live at the root of whatever domain
serves it (links like `/profile.php` resolve from the domain root) — both
locally and once deployed. Locally, that means it should NOT sit inside a
`htdocs/skillswap` subfolder; the files go straight into `htdocs/` itself.

1. Copy everything **inside** this `skillswap` folder (not the folder itself)
   directly into your XAMPP `htdocs/` folder. If `htdocs/index.php` already
   exists (the default XAMPP splash page), back it up or delete it first —
   it will be overwritten by this project's `index.php`.
2. Start **Apache** and **MySQL** from the XAMPP control panel.
3. Open phpMyAdmin (`http://localhost/phpmyadmin`) and import `sql/schema.sql`.
   This creates the `skillswap` database and all tables, plus a small starter
   list of common skills.
4. Visit `http://localhost/` (no `/skillswap/` in the URL).
5. Register a real account through the site.
6. In phpMyAdmin, run the following to make that account an admin:
   ```sql
   UPDATE users SET role='admin', is_verified=1 WHERE email='your-email@example.com';
   ```
7. Log back in — you'll now see the "Admin Panel" link in the nav.

## Configuration

Database credentials live in `config/database.php`. The defaults match a
fresh XAMPP install (`root` user, no password). Update this file if your
MySQL setup differs, or when moving to a hosting provider.

## Folder structure

```
skillswap/
├── admin/              Admin-only pages (users, certificates, reports)
├── assets/              CSS/JS
├── auth/                Register / login / logout
├── config/              Database connection settings
├── includes/            Shared header/footer/functions
├── sql/                 Database schema
├── uploads/              Profile pictures & certificates (user-generated, gitignore-worthy)
└── *.php                 Feature pages (profile, matches, requests, sessions, ratings, notifications)
```

## Verifying the build

See `REQUIREMENTS_CHECKLIST.md` for a full mapping of every proposal
objective/module to its implementing files, plus a 14-step end-to-end test
script using two real accounts.

## Deploying beyond localhost

Any standard PHP 8 + MySQL host works (shared hosting or a VPS). Upload
files to the host's **web root** (e.g. `htdocs/`, `public_html/`, or
whatever your host calls it) — not a subfolder — since paths are root-relative:

1. Upload all files via FTP/SFTP (or your host's file manager) into the web root.
2. Import `sql/schema.sql` through the host's phpMyAdmin (or `mysql` CLI).
3. Update `config/database.php` with the host's DB credentials (these will
   differ from the local XAMPP defaults).
4. Make sure the `uploads/` folder is writable by the web server
   (`chmod 755` is usually enough; avoid `777` unless your host requires it).
5. Repeat the "promote to admin" SQL step from Setup above.
