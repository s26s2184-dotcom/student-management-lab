# Mitigations — Secure Implementation (`APP_MODE=secure`)

Each mitigation below was manually verified against the running application.

## SQL Injection → PDO Prepared Statements
- `lab/secure/login.php`, `lab/secure/search.php`, `lab/secure/student_details.php`, `lab/secure/edit_student.php` all use `$pdo->prepare()` with bound parameters (`:named` or `?` placeholders). No user input is ever concatenated into a SQL string.
- `includes/db.php` sets `PDO::ATTR_EMULATE_PREPARES => false`, so placeholders are sent to MySQL as true parameterized queries, not just client-side string substitution.
- **Verified:** the same UNION payload that leaked all password hashes in vulnerable mode returns zero extra rows in secure mode, and the login boolean-blind oracle (`' OR '1'='1`) no longer changes server behavior (both existing and non-existing usernames get the same generic error).

## Reflected & Stored XSS → Output Encoding
- All page output goes through `h()` (a thin wrapper around `htmlspecialchars($s, ENT_QUOTES, 'UTF-8')`, in `includes/auth.php`) or, for announcement/comment bodies, `render_announcement_output()` in `lab/secure/announcements.php`.
- Content is still **stored exactly as submitted** — the fix is entirely at render time, which is the correct place to apply encoding (storing pre-encoded HTML would break other consumers of the same data and can cause double-encoding bugs).
- **Verified:** a `<script>` payload posted as a comment renders as literal, inert text (`&lt;script&gt;...`) when viewed by a different user in secure mode.

## Authentication
- **Password hashing:** `password_hash()` / `password_verify()` are used everywhere credentials are checked (registration always did this; secure-mode login does too).
- **Generic error messages:** `lab/secure/login.php` returns "Invalid username or password." regardless of whether the username exists — no enumeration oracle.
- **Rate limiting:** a simple per-username, session-based counter locks out further attempts for 60 seconds after 5 failures (`MAX_ATTEMPTS` / `LOCKOUT_SECONDS` constants). A production system should track this server-side (e.g. per IP + username in a database or cache), not only in the session, but this is sufficient to demonstrate the mitigation and its effect in the lab.
- **Session fixation:** `session_regenerate_id(true)` is called immediately after a successful login, issuing a brand-new session ID.
- **Cookie hardening:** `includes/auth.php`'s `start_app_session()` sets `httponly` and `samesite=Lax` on the session cookie in secure mode.
- **Verified:** 6 consecutive failed logins against the same username (with a persisted session cookie) produced the lockout message on the 6th attempt.

## IDOR → Explicit Authorization Checks
- `lab/secure/student_details.php` (`secure_get_student()`) and `lab/secure/edit_student.php` (`secure_can_edit_student()`) check that the current user is either an admin, or the student who owns the record, before returning data or allowing a write. Unauthorized requests get HTTP 403.
- The check is re-run immediately before every write in `secure_save_student()`, not just once at page load — defending against stale/forged state.
- **Verified:** a non-owner student (`mwong`) attempting to view or edit another student's record (`id=1`) received HTTP 403 in secure mode, while the same request succeeded (and leaked/modified data) in vulnerable mode.

## Insecure File Upload → Defense in Depth
- **Extension allowlist** (`jpg`, `jpeg`, `png`, `gif` only) instead of a blocklist.
- **MIME verification** via `finfo_file()` against the actual file bytes (not the client-supplied `Content-Type` header, which is trivially spoofable).
- **Size limit** (2 MB).
- **Random filenames** (`bin2hex(random_bytes(16))`) — no trust in client-supplied names, no collisions, no information disclosure via filenames.
- **Storage outside the web root:** files land in `secure_uploads/`, a sibling of `public/` that Apache never serves directly; they are only retrievable through `serve_upload.php`, which re-checks ownership/admin status and streams the file with the correct `Content-Type`.
- **Verified:** a `.php` file upload attempt was rejected ("File extension not allowed.") in secure mode, while the identical upload succeeded in vulnerable mode.

## What Did Not Need to Change
Registration (`public/register.php`) and the basic admin user-management listing/toggle already used prepared statements and `password_hash()` in both modes — they were not part of the intentional vulnerability surface, so no "before/after" exists for them.
