# SkillSwap — Requirements Checklist

Use this to verify the build against your original proposal. For each item: go do
the action described, and confirm the result matches. Check the box only after
you've actually clicked through it yourself.

## Objectives (from Section 3 of your proposal)

- [ ] **1. Secure skill exchange platform** — passwords hashed with bcrypt
  (`password_hash`/`password_verify`), all queries use PDO prepared statements
  (no SQL injection), file uploads validated by real content type not
  extension, session regenerated on login.
- [ ] **2. Registration & authentication** — `index.php` (landing page forms),
  `auth/register.php`, `auth/login.php`, `auth/logout.php`.
- [ ] **3. Student profile management** — `profile.php` (name, bio, picture,
  teach/learn skill lists), `profile_update.php`, `skill_add.php`, `skill_remove.php`.
- [ ] **4. Certificate verification** — `certificates.php` (student upload),
  `certificate_upload.php`, `admin/certificates.php` (review queue),
  `admin/certificate_action.php` (approve/reject, flips `is_verified` badge).
- [ ] **5. Admin management system** — `admin/index.php` (dashboard),
  `admin/users.php` + `admin/user_action.php` (suspend/reactivate/promote/demote),
  `admin/certificates.php` (verification).
- [ ] **6. Skill-based matchmaking** — `matches.php`: mutual "Perfect Swaps,"
  one-directional "Teachers For You" and "Students Interested In Your Skills."
- [ ] **7. Skill exchange requests** — `request_form.php`, `request_create.php`,
  `exchange_requests.php` (Received/Sent tabs), `request_action.php`
  (accept/reject/cancel).
- [ ] **8. Rating and review system** — `rate_session.php`,
  `rate_session_submit.php`, shown on `profile.php` and `view_profile.php`.
- [ ] **9. Collaborative learning without payment** — no payment/pricing code
  anywhere in the system; every exchange is skill-for-skill by design (schema
  has no currency/amount field).
- [ ] **10. Modular / extensible codebase** — each module is its own set of
  files sharing one `config/database.php` connection and one `includes/`
  layer; new modules (e.g. real-time chat) can be added without touching
  existing files.

## Modules (from Section 7 of your proposal)

| # | Module | Files |
|---|---|---|
| 1 | User Registration & Authentication | `index.php`, `auth/*.php` |
| 2 | User Profile Management | `profile.php`, `profile_update.php` |
| 3 | Skill Management | `skill_add.php`, `skill_remove.php` |
| 4 | Certificate Upload & Verification | `certificates.php`, `certificate_upload.php`, `admin/certificates.php`, `admin/certificate_action.php` |
| 5 | Admin Verification & User Management | `admin/index.php`, `admin/users.php`, `admin/user_action.php` |
| 6 | Skill-Based Matchmaking | `matches.php` |
| 7 | Skill Exchange Request | `request_form.php`, `request_create.php`, `exchange_requests.php`, `request_action.php` |
| 8 | Session Scheduling | `session_form.php`, `session_create.php`, `sessions.php`, `session_action.php` |
| 9 | Ratings & Reviews | `rate_session.php`, `rate_session_submit.php`, `view_profile.php` |
| 10 | Notification | `notifications.php`, `notification_click.php`, `notifications_mark_all_read.php`, nav badge in `includes/header.php` |
| 11 | Reports & Analytics | `admin/reports.php` |

## End-to-end test script

Run this exact sequence with two real accounts (not seed data) to confirm every
module actually works together, not just in isolation:

1. Register **Student A** and **Student B** through the real form on `index.php`.
2. **A**: add "Guitar" to Skills I Can Teach, add "Python Programming" to
   Skills I Want to Learn (on `profile.php`).
3. **B**: add "Python Programming" to Skills I Can Teach, add "Guitar" to
   Skills I Want to Learn.
4. **A**: go to `certificates.php`, upload a certificate for Guitar.
5. Log in as the promoted **admin** account → `admin/certificates.php` →
   approve A's certificate → confirm A's profile now shows "✔ Verified Teacher."
6. **B**: go to `matches.php` → confirm **A** appears under "🤝 Perfect Swaps."
7. **B**: click "Request Exchange" on A → send a request.
8. **A**: `exchange_requests.php` → "Received" tab → Accept.
9. Either party: `session_form.php` → schedule a session for a future date.
10. `sessions.php` → mark the session "Completed."
11. Either party: click "Leave a rating" → submit a rating + review.
12. Confirm the review appears on the rated user's `view_profile.php`.
13. Confirm both A and B received notifications at each step (`notifications.php`,
    nav badge count).
14. Admin: `admin/reports.php` → confirm the counts (requests, sessions,
    certificates, ratings) reflect everything you just did.

If all 14 steps work end to end with real accounts, the system is functioning
as designed.

## Known limitations (be upfront about these if asked)

- No automated test suite — verification is manual, via the script above.
- No email notifications — everything is in-app only (matches your proposal;
  proposal Section 4 lists email notifications as a *future* enhancement).
- No real-time chat or video conferencing — also listed as future work in
  your proposal, not part of the core scope.
- I do not have a PHP runtime available while building this, so syntax was
  reviewed carefully but not machine-executed on my end — run
  `find . -name "*.php" -exec php -l {} \;` from the project root before
  your first real test, to catch any typo immediately.
