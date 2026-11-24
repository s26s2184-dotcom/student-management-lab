# Security Testing Guide

> **Scope reminder:** everything below targets `http://localhost:8080` on your own machine,
> running this project's own Docker containers and seeded test database only.
> Do not point any of these tools at systems you do not own or have authorization to test.

## 0. Starting the lab

```bash
cp .env.example .env
# Edit .env if you want, then:
docker compose up --build -d
docker compose ps        # wait for both "db" and "app" to be healthy/running
```

The app is bound to `127.0.0.1:8080` only (see `docker-compose.yml`), and MySQL is not
published to the host at all — it's reachable only from the `app` container over the
private `lab_internal` Docker network.

Switch modes by setting `APP_MODE=vulnerable` or `APP_MODE=secure` in `.env`, then:
```bash
docker compose up -d --build app
```

Seeded login: `admin` / `Admin@123` (see `docs/vulnerabilities.md` for the full list).

---

## 1. SQL Injection

See `docs/sqlmap-commands.md` for ready-to-run sqlmap commands.

Manual checks:
- Login username field: `admin'-- -` style payloads, and the boolean pair
  `nonexistent' OR '1'='1` vs. `nonexistent' OR '1'='2` (compare the error text).
- `students.php?q=` : try `' UNION SELECT 1,username,password_hash,role,2 FROM users-- -`
- `student.php?id=` : try `1 OR 1=1`, `1 AND 1=2`, or feed it to sqlmap directly.

**Screenshots to capture:** the raw HTTP request/response showing injected syntax;
the resulting page showing leaked data (usernames/hashes) or the boolean-blind
differential; the same request against secure mode returning no extra data.

**Secure comparison:** repeat every payload with `APP_MODE=secure` — results should
be empty/generic, and `docker compose logs app` should show normal queries only
(no SQL errors).

---

## 2. Cross-Site Scripting (XSS)

### Reflected
1. Log in, then visit:
   `http://localhost:8080/students.php?q=<script>alert('reflected-xss-poc')</script>`
2. In vulnerable mode, the alert fires. In secure mode, the literal text
   `<script>alert('reflected-xss-poc')</script>` is shown as plain text.

### Stored
1. Log in as one student (e.g. `jsmith` / `password123`) and post a comment on any
   announcement containing: `<script>alert('stored-xss-poc')</script>`
2. Log out, log in as a **different** user (e.g. `admin` / `Admin@123`), and open
   the same announcement.
3. In vulnerable mode, the alert fires for the second user even though they never
   typed the payload. In secure mode it renders as inert text.

**Screenshots to capture:** the comment submission form with the payload typed in;
the alert box firing for the second user; the secure-mode page showing the escaped
`&lt;script&gt;` text instead.

Use only harmless PoC payloads (`alert(...)`, or a `document.body.style.background`
change) — never exfiltrate real data, even within this lab.

---

## 3. Authentication

- **Weak passwords:** attempt login with each seeded account's password from
  `docs/vulnerabilities.md`.
- **Account enumeration:** in vulnerable mode, submit a known-good username with a
  wrong password, then an unknown username, and compare the two error messages.
  In secure mode both should be identical.
- **Controlled repeated-login testing:** from `localhost` only, submit 6+ login
  attempts for the same username in quick succession (keep your session cookie!).
  In vulnerable mode all attempts are processed identically (no lockout). In secure
  mode, the 6th attempt (within 60 seconds) should return a "Too many attempts"
  message. Keep this to a handful of requests — this is a demonstration, not a
  brute-force exercise.

**Mitigation check:** confirm `docker compose logs db` or a direct query shows
passwords stored as bcrypt hashes (`$2y$...`), never plaintext, in both modes
(the `plain_password` column is lab-only metadata, not used for authentication
in secure mode).

---

## 4. IDOR

1. Log in as `jsmith` (owns `student.php?id=1`).
2. Request `student.php?id=4` (Raj Patel's record, owned by `rpatel`).
3. **Vulnerable mode:** the full profile is displayed.
4. **Secure mode:** the request returns HTTP 403 "You are not authorized...".
5. Repeat against `edit_student.php?id=4` with a POST body to confirm writes are
   blocked too in secure mode.

**Screenshots to capture:** the URL bar showing the changed `id`; the leaked profile
in vulnerable mode; the 403 page in secure mode.

---

## 5. File Upload

1. Create a harmless file named `shell.php` containing e.g. `<?php echo 'marker'; ?>`
   (do **not** write a real web shell — this lab does not support or need one).
2. Upload it via `upload.php`.
3. **Vulnerable mode:** the upload succeeds (extension blocklist doesn't cover `.php`,
   no MIME check). Note, however, that the file is still *not executable* — see
   `docs/vulnerabilities.md` section 6 for why.
4. **Secure mode:** the upload is rejected ("File extension not allowed.").
5. Try a real `.jpg`/`.png` image in both modes — it should succeed in both, since
   functionality is meant to be comparable, only validation strictness differs.

**Why this matters even without RCE:** insufficient upload validation is still a
real finding — it indicates the *application* would be exploitable if the hosting
environment's script-execution protections were ever misconfigured or removed.
Defense in depth means both layers (app validation **and** infra hardening) matter.

---

## 6. Automated Scanning (OWASP ZAP / Nikto)

### Using OWASP ZAP
1. Start the lab (`APP_MODE=vulnerable`).
2. Launch ZAP, set it to proxy or use the Automated Scan tab with target
   `http://localhost:8080/`.
3. Log in through ZAP's browser (or configure a authenticated session using the
   seeded `jsmith`/`password123` credentials) so authenticated pages get scanned too.
4. Run **Spider** then **Active Scan**.
5. Review the **Alerts** tab; note severity (High/Medium/Low/Informational) for
   each finding and cross-reference against `docs/vulnerabilities.md`.
6. Repeat the whole scan with `APP_MODE=secure` and compare alert counts/severities.

### Using Nikto
```bash
nikto -h http://localhost:8080/ -o evidence/scanner/nikto-vulnerable.txt
# switch APP_MODE to secure, restart the app container, then:
nikto -h http://localhost:8080/ -o evidence/scanner/nikto-secure.txt
```

**Record:** scan start/end time, tool version, full alert list with severities, and
a short note on which alerts correspond to vulnerabilities documented in
`docs/vulnerabilities.md` vs. generic server-header/informational findings.

Do not scan any host other than your own `localhost:8080` instance.
