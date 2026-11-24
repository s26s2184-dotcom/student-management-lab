# Student Management Security Lab (CSSY3202)

A deliberately vulnerable + secure student-management web application, built for
web application security coursework. It lets you compare the **same**
functionality implemented insecurely vs. securely, and practice vulnerability
assessment (sqlmap, OWASP ZAP, Nikto) against a safe, local, fictional-data
target.

**This is an educational lab. Do not deploy it anywhere but your own machine,
and never expose it beyond `127.0.0.1`.**

## Stack

- PHP 8.3 (vanilla, no framework), PDO for MySQL, native sessions.
- MySQL 8.
- Vanilla HTML/CSS/JS (no frontend framework).
- Docker + Docker Compose.

## Quick Start

```bash
cp .env.example .env
docker compose up --build -d
docker compose ps   # wait for both services healthy
```

Visit **http://localhost:8080** (bound to localhost only — see `docker-compose.yml`).

Default mode is `APP_MODE=vulnerable` (set in `.env`). To switch:
```bash
# edit .env: APP_MODE=secure
docker compose up -d --build app
```

### Seeded accounts

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

These are intentionally weak — see `docs/vulnerabilities.md`.

## Project Structure

```
student-management-lab/
├── docker-compose.yml       # app (127.0.0.1:8080) + db (internal only)
├── Dockerfile                # PHP 8.3 + Apache; disables PHP exec in uploads/
├── .env.example
├── database/
│   ├── init.sql              # schema: 7 tables
│   └── seed.sql               # fictional seed data
├── public/                   # the 9 application pages (front controllers)
├── includes/                 # db.php, auth.php, header.php, footer.php
├── lab/
│   ├── vulnerable/            # intentionally vulnerable logic
│   └── secure/                # corrected/mitigated logic
├── uploads/profiles/          # vulnerable-mode upload target (PHP exec disabled)
├── secure_uploads/            # secure-mode upload target (outside web root)
├── evidence/                  # findings-template.md, report-outline.md
└── docs/
    ├── vulnerabilities.md
    ├── mitigations.md
    ├── testing-guide.md
    └── sqlmap-commands.md
```

Both `public/` pages dispatch to either `lab/vulnerable/*.php` or
`lab/secure/*.php` based on the `APP_MODE` environment variable, so the two
implementations share the same pages, routes, and visual design — only the
underlying logic differs.

## What's Intentionally Vulnerable (vulnerable mode only)

1. **SQL Injection** — login (`username`), student search (`q`), student
   details (`id`). See `docs/sqlmap-commands.md`.
2. **Reflected XSS** — student search (`q`).
3. **Stored XSS** — announcements/comments.
4. **Auth weaknesses** — weak seeded passwords, account enumeration via
   distinct error messages, no login rate limiting.
5. **IDOR** — student profile view/edit by raw `id`, no ownership check.
6. **Insecure upload** — weak extension blocklist, no MIME check (still can't
   achieve code execution — see below).

Full details, root causes, and severities: `docs/vulnerabilities.md`.
Corresponding fixes: `docs/mitigations.md`.

## Safety Controls (apply in both modes)

- The app only binds to `127.0.0.1:8080` — not reachable from other machines.
- MySQL has no published port; it's reachable only from the `app` container
  over a private Docker network.
- The `uploads/` directory has the Apache PHP engine disabled and forces
  `.php`/`.phtml` files to be served as `text/plain`, so even the
  intentionally weak upload validation cannot result in remote code
  execution. **Do not remove this control.**
- No real personal data; everything in `database/seed.sql` is fictional.

## Testing

See `docs/testing-guide.md` for the full walkthrough (SQLi, XSS, auth, IDOR,
upload, and automated scanning with ZAP/Nikto), and `docs/sqlmap-commands.md`
for ready-to-run sqlmap commands.

## Verification Status

This project was built and functionally verified using PHP's built-in
development server against a real local MySQL 8 instance (same PHP 8.3
runtime, same application code and SQL queries the Docker image uses). The
following were manually exercised and confirmed working, in both
`APP_MODE=vulnerable` and `APP_MODE=secure`:

- [x] All 9 application pages load and render.
- [x] Login / logout / registration.
- [x] SQL injection: UNION-based extraction via `students.php?q=`, and a
      boolean-blind oracle via `login.php` username field — both leak data in
      vulnerable mode and are blocked in secure mode.
- [x] Reflected XSS via `students.php?q=` — executes raw in vulnerable mode,
      rendered as inert escaped text in secure mode.
- [x] Stored XSS via announcement comments — a payload posted by one user
      executes for a different viewing user in vulnerable mode; rendered
      escaped in secure mode.
- [x] Account enumeration (distinct error messages) in vulnerable mode vs. a
      single generic message in secure mode.
- [x] Missing rate limiting in vulnerable mode vs. a 5-attempt/60-second
      lockout in secure mode.
- [x] IDOR: a non-owner student could view/edit another student's profile in
      vulnerable mode; the same requests return HTTP 403 in secure mode.
- [x] Insecure upload: a `.php` file is accepted by the weak blocklist in
      vulnerable mode; rejected by the allowlist+MIME check in secure mode.
- [x] Admin user management (list + activate/deactivate).
- [x] `docker-compose.yml` is valid YAML with the expected two services.

**Not yet verified** (could not be run in the environment this project was
built in — no Docker daemon available): an actual `docker compose up --build`
build-and-boot cycle, and live scans with sqlmap/OWASP ZAP/Nikto against the
containerized app. The Dockerfile and compose file were written following
standard, well-tested patterns (official `php:8.3-apache` and `mysql:8.0`
base images, standard healthcheck/volume/network syntax), but you should run
`docker compose up --build` yourself and confirm it comes up cleanly before
relying on it for your assignment, and capture your own sqlmap/ZAP/Nikto
output per `docs/testing-guide.md` — do not submit this README's checklist in
place of your own tool output.

## License / Disclaimer

For educational use only. All data is fictional. Do not reuse any password
or credential pattern shown here for real accounts.
