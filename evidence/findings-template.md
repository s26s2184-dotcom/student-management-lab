# Security Finding Template

Copy this block once per vulnerability you actually tested. Do not fill in a
block for anything you have not personally reproduced — leave it blank or
mark it "Not yet tested" instead of inventing a result.

---

## Finding: <Vulnerability Name>

- **Affected page:**
- **Affected parameter / form field:**
- **Vulnerability class (OWASP):** e.g. A03:2021 – Injection
- **Root cause:**
- **Severity (High/Medium/Low):**
- **Date tested:**
- **Tester:**

### Steps to Reproduce
1.
2.
3.

### Expected Vulnerable Behavior

### Actual Observed Result
(What you actually saw — screenshot reference below)

### Screenshot Evidence Checklist
- [ ] Request (raw HTTP or browser URL bar) showing the payload
- [ ] Response showing the vulnerable behavior
- [ ] (If applicable) sqlmap / ZAP / Nikto console output
- [ ] Same test repeated against secure mode, showing the mitigation holds

### Mitigation Applied
(Reference the relevant section of `docs/mitigations.md`)

### Secure Implementation Comparison
(What changed, and what the same test now shows)

---
