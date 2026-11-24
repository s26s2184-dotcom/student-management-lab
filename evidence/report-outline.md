# CSSY3202 Report Outline

Use this as the skeleton for your submitted report. Fill in each section with
your own findings, screenshots, and commentary — do not submit this file
as-is.

1. **Title Page** — course code, assignment name, your name/ID, date.
2. **Introduction and Objectives** — purpose of the lab, scope (local Docker
   environment only, fictional data), tools used.
3. **Environment and Setup** — Docker Compose architecture diagram/description,
   how to reproduce your environment (`docker compose up --build`), versions
   (PHP 8.3, MySQL 8, sqlmap/ZAP/Nikto versions used).
4. **Vulnerability Assessment** — for each of the 6 vulnerability classes in
   `docs/vulnerabilities.md`, a finding written from `evidence/findings-template.md`.
5. **Exploitation** — detailed walkthroughs (with screenshots) of at least one
   exploitation per vulnerability class, referencing the exact commands in
   `docs/sqlmap-commands.md` and `docs/testing-guide.md`.
6. **Mitigations and Recommendations** — summarize `docs/mitigations.md` in
   your own words, and re-test each exploit against `APP_MODE=secure` to show
   it no longer works.
7. **Conclusion and Lessons Learned** — what you learned about secure coding
   practices, and why defense in depth (e.g. the upload directory's
   PHP-execution lockout) matters even when app-level validation also exists.
8. **References** — OWASP Top 10, OWASP Cheat Sheet Series, PHP manual pages
   for `password_hash`/PDO, sqlmap/ZAP/Nikto documentation.
9. **Appendix with Commands** — full command history actually run (copy from
   your terminal / shell history), not reconstructed from memory.
