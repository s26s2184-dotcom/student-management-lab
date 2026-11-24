# Vulnerabilities — Student Management Lab

All vulnerabilities below were manually verified against the running application
(`APP_MODE=vulnerable`) during development. See the "Verified" line on each item.

---

## 1. SQL Injection

| | |
|---|---|
| **Affected pages** | `login.php` (POST `username`), `students.php` (GET `q`), `student.php` (GET `id`) |
| **Root cause** | User input is concatenated directly into SQL strings (`lab/vulnerable/login.php`, `lab/vulnerable/search.php`, `lab/vulnerable/student_details.php`) instead of using PDO prepared statements with bound parameters. |
| **Severity** | High |
| **Steps to reproduce (UNION-based, via `q`)** | Log in, then request: `students.php?q=nomatch' UNION SELECT 1,username,password_hash,role,2 FROM users-- -` |
| **Expected vulnerable behavior** | The page returns a "student list" table whose rows actually contain `username` and the bcrypt `password_hash` from the `users` table. |
| **Verified** | ✅ Confirmed: UNION query returned all 8 usernames and their bcrypt hashes through the normal HTML table rendering. |
| **Steps to reproduce (boolean-blind, via login `username`)** | POST `username=nonexistent' OR '1'='1&password=x` vs. `username=nonexistent' OR '1'='2&password=x` |
| **Expected vulnerable behavior** | The two requests return different error text ("Incorrect password for this account." vs. "Username not found."), giving a true/false oracle. |
| **Verified** | ✅ Confirmed via manual curl testing. |
| **Mitigation** | See `docs/mitigations.md` — PDO prepared statements, bound parameters. |

---

## 2. Reflected XSS

| | |
|---|---|
| **Affected page** | `students.php` (GET `q`) |
| **Root cause** | The submitted search term is echoed back ("You searched for: ...") without `htmlspecialchars()`. |
| **Severity** | Medium |
| **Steps to reproduce** | `students.php?q=<script>alert(1)</script>` |
| **Expected vulnerable behavior** | The literal `<script>` tag appears unescaped in the page source and executes in the browser. |
| **Verified** | ✅ Confirmed: raw `<script>alert(1)</script>` appears unescaped in the HTTP response body. |
| **Mitigation** | Secure mode uses `h()` (a `htmlspecialchars()` wrapper) on every echoed value. |

---

## 3. Stored XSS

| | |
|---|---|
| **Affected page** | `announcements.php` (comments and announcement content) |
| **Root cause** | Comment/announcement content is saved to MySQL as submitted and rendered back with `render_announcement_output()`, which in vulnerable mode returns the content unmodified (see `lab/vulnerable/announcements.php`). |
| **Severity** | High (affects every viewer, not just the submitter) |
| **Steps to reproduce** | Log in as any student, post a comment containing `<script>alert('stored-xss-poc')</script>`, then log in as a *different* user and view the same announcement. |
| **Expected vulnerable behavior** | The script tag executes in the second user's browser even though they never typed it. |
| **Verified** | ✅ Confirmed: a comment posted as `jsmith` appeared as a raw, executable `<script>` tag when the page was subsequently loaded as `admin`. |
| **Mitigation** | Secure mode's `render_announcement_output()` applies `htmlspecialchars()` at render time (content is still stored as-is; only *display* is hardened). |

---

## 4. Authentication Weaknesses

### 4a. Weak / default passwords
Seeded accounts use common, guessable passwords (see `database/seed.sql` and the table below). This is intentional lab data, not a production pattern.

| Username | Password | Role |
|---|---|---|
| admin | Admin@123 | admin |
| jsmith | password123 | student |
| mwong | letmein | student |
| achen | qwerty123 | student |
| rpatel | Student#2024 | student |
| klee | iloveyou | student |
| dgarcia | password | student |
| tnguyen | StudentPass1 | student |

### 4b. Account enumeration
**Root cause:** `lab/vulnerable/login.php` returns "Username not found." for a nonexistent user and "Incorrect password for this account." for a wrong password on an existing user. **Verified** ✅ via manual testing (see SQLi boolean-blind example above, which relies on exactly this behavior).

### 4c. Missing rate limiting
**Root cause:** `attempt_login()` in vulnerable mode has no attempt counter or lockout. **Verified** ✅ — six consecutive failed logins in vulnerable mode all return the same per-attempt result with no throttling (compare with secure mode, which locks out after 5 attempts — see `docs/mitigations.md`).

**Severity:** Medium (enumeration), Medium (rate limiting)

---

## 5. IDOR (Insecure Direct Object Reference)

| | |
|---|---|
| **Affected pages** | `student.php` (GET `id`), `edit_student.php` (GET/POST `id`) |
| **Root cause** | `vulnerable_get_student()` / `vulnerable_load_student_for_edit()` fetch a record by `id` with no check that the logged-in user owns that record or is an admin. |
| **Severity** | High |
| **Steps to reproduce** | Log in as `jsmith` (owns student id 1), then browse to `student.php?id=4` (Raj Patel's record). |
| **Expected vulnerable behavior** | Raj Patel's full profile (DOB, address, phone, GPA) is displayed to jsmith. |
| **Verified** | ✅ Confirmed: jsmith's session could view and (via `edit_student.php`) modify another student's record. |
| **Mitigation** | Secure mode's `secure_get_student()` / `secure_can_edit_student()` check `role === 'admin'` or `user_id === current user id` and return HTTP 403 otherwise. |

---

## 6. Insecure File Upload

| | |
|---|---|
| **Affected page** | `upload.php` |
| **Root cause** | `vulnerable_handle_upload()` only checks a short extension *blocklist* (`exe`, `sh`, `bat`) — `.php` is not blocked — performs no server-side MIME check (via `finfo`), enforces no size limit, and keeps the original filename (collision/overwrite risk). |
| **Severity** | High (if it resulted in code execution) — **mitigated to Informational/Low here** by an infrastructure-level control (see below). |
| **Steps to reproduce** | Upload a file named `shell.php` containing harmless PHP (e.g. `<?php echo 'marker'; ?>`). |
| **Expected vulnerable behavior** | The application accepts the upload without complaint (no extension/MIME rejection). |
| **Verified** | ✅ Confirmed: a `.php` file was accepted and stored by the vulnerable upload handler. |
| **Why this isn't full RCE here** | The Apache configuration (see `Dockerfile`) disables the PHP engine for the `uploads/` directory and forces `.php*`/`.phtml` files there to be served as `text/plain`. This is a deliberate defense-in-depth control required by the lab design — it does **not** replace fixing the application-level validation, which is exactly what the secure implementation does. |
| **Mitigation** | Secure mode (`lab/secure/upload.php`): strict allowlist, `finfo` MIME verification, 2MB size cap, random filename, storage in `secure_uploads/` (outside the Apache document root), served only via an authenticated `serve_upload.php` endpoint. |

---

## Summary Table

| # | Vulnerability | Page/Param | Severity | Verified |
|---|---|---|---|---|
| 1 | SQL Injection | login `username`; students `q`; student `id` | High | ✅ |
| 2 | Reflected XSS | students `q` | Medium | ✅ |
| 3 | Stored XSS | announcements/comments | High | ✅ |
| 4a | Weak/default passwords | seeded accounts | Medium | ✅ (by design) |
| 4b | Account enumeration | login | Medium | ✅ |
| 4c | No rate limiting | login | Medium | ✅ |
| 5 | IDOR | student `id` | High | ✅ |
| 6 | Insecure file upload | upload.php | High (mitigated to Low by infra control) | ✅ |
