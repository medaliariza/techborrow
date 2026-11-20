# TechBorrow submission handoff

## Project

- **Title:** TechBorrow - Technical Equipment Borrowing and Inventory System
- **Repository:** https://github.com/medaliariza/techborrow
- **Website:** https://techborrow.freedev.app/ (the host responds, but the live student request page currently reports a database connection error; verify the DB setup and redeploy before treating the site as ready).
- **Android installer:** `TechBorrow.apk` in the repository root, also downloadable from `/download-apk.php` after deployment.
- **API documentation and Postman collection:** `docs/API-AND-POSTMAN.md` and `docs/TechBorrow.postman_collection.json`
- **Testing and QA:** `docs/TechBorrow-QA-Test-Plan.pdf` (30 sample test cases; cases are proposed and marked Not Run until executed).
- **Presentation:** Open `docs/TechBorrow-Presentation.html` in a browser. Replace the presenter and member placeholders before presenting.

## Roles and test accounts

The application roles are `admin`, `instructor` (teacher), and `student`. Fresh database imports do not contain demo users. To create local/sample accounts, first import `database/techborrow_db.sql`, then import `docs/demo-accounts.sql` into the same database.

| Role | Email | Password |
|---|---|---|
| Admin | `demo.admin@example.test` | `BorrowDemo#2026` |
| Instructor / teacher | `demo.instructor@example.test` | `BorrowDemo#2026` |
| Student | `demo.student@example.test` | `BorrowDemo#2026` |

These are predictable, non-production demo credentials. Do not use them on a public deployment; replace the passwords and remove the accounts after testing. The accounts use the `DEMO` department and a sample student number.

## Local setup

1. Install XAMPP with PHP 8.2+ and MySQL/MariaDB; start Apache and MySQL.
2. Put the repository in `C:\xampp\htdocs\techborrow`.
3. Create a database named `techborrow_db`, then import `database/techborrow_db.sql`.
4. Optionally import `docs/demo-accounts.sql` to add the sample logins.
5. Open `http://localhost/techborrow/` and sign in.
6. To deploy, configure the FTP GitHub Actions secrets and upload `config/database.local.php` to the host using `config/database.local.php.example`. This local-only file must contain the host, database name, username, and rotated password. It is intentionally excluded from deployment and must never be committed.
7. The workflow deploys to `/htdocs/` for the public root URL. `BASE_URL` is `/techborrow` on localhost and empty on the public domain.

## Submission checklist

- [ ] Before redeploying, rotate the live database password: an older public commit contained a hard-coded credential. Update the host-only database config; deleting the old code does not remove it from Git history.
- [ ] Remove or rotate any default/demo accounts shown by the currently deployed login page. Never use the demo-account seed in production.
- [ ] Resolve the live database connection error, then test the public website from a device outside the development machine.
- [ ] Replace presenter/member placeholders in the presentation.
- [ ] Import the demo accounts only into the intended test database; verify each role can sign in.
- [ ] Install and smoke-test `TechBorrow.apk` on a supported Android device (Android 6.0 / API 23 or newer).
- [ ] Import the Postman collection, set `base_url`, and sign in to obtain a PHP session before trying protected actions.
- [ ] Execute the QA cases, record actual results, and update the initial bug log in the PDF/source notes.
- [ ] Upload the repository link, public site link, collection, QA PDF, APK, and presentation to the required classroom.

## Scope note

This codebase is a PHP server-rendered web application with HTML form handlers, not a standalone JSON REST API. The Postman collection documents the real HTTP forms and their session-based behavior. Requests generally redirect back to a page and show a flash message; they do not return JSON payloads.
