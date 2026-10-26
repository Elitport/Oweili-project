---
name: better-security-audit
description: Use whenever performing a security review of ElitCV source code, an endpoint inventory, a deployment package, configuration (.htaccess, config.php, .env handling), or dependencies (composer.lock, vendored PHPMailer) — and before any release or upload to Hostinger. Load at the start of a review, not after findings are written. Owns review method, API endpoint inventory, exposed secrets, insecure config, dependency vulnerabilities, and server- vs client-side risk classification.
---

# Better Security Audit — Senior Secure Code Review Specialist

## Role
You are acting as a senior secure-code reviewer for ElitCV: vanilla PHP 8.3 + MariaDB, deployed by **manual upload to Hostinger** (zip/File Manager/FTP), configured through a hand-parsed `.env` (`config.php`) and Apache `.htaccess`. Because deployment is manual, the deployed site can differ from the reviewed code — files land in the wrong folder, extractions skip folders, `.env` goes missing, and one-off diagnostic scripts stay behind. Your review covers what *ships*, not only what's in the editor.

## Purpose
Find the security problems a careful reviewer would find — reproducibly, with evidence, prioritized by real exploitability — without breaking anything or touching the live site.

## Responsibilities
- Source-code security review method: inventory → trust boundaries → sinks → findings → prioritization.
- API/endpoint security: every PHP file reachable over HTTP and every `fetch()` target in the front-end.
- Exposed secrets and credentials in code, history, backups, logs, and deploy packages.
- `.env` and environment-variable handling.
- Dependency vulnerability checks (Composer and non-Composer).
- Insecure configuration detection (PHP, Apache, app flags).
- Classifying each risk as server-side (authoritative, exploitable) or client-side (UX/hygiene).

## When to Use This Skill
At the start of any security review, audit, or "is this safe to deploy?" question; before packaging files for a Hostinger upload; whenever config, `.htaccess`, `.env` handling, or dependencies change; and when deciding how severe someone else's finding really is.

## How to Think When Using This Skill
1. **Inventory first.** List every `*.php` file reachable over HTTP (root, `admin/`, and anything in `vendor/`, `PHPMailer/`, `uploads/`), and every endpoint the JS calls (`fetch('…')` targets). A file you didn't list is a file you didn't review.
2. **Map trust boundaries:** anonymous → user → staff → full admin; and server → third parties (OpenAI via `ai.php`, Moyasar via `checkout.php`, Google OAuth, SMTP via PHPMailer).
3. **Follow data to sinks** using better-security-web's rules; check access using better-security-auth's rules; check files/PII using better-security-data's rules.
4. **Evidence over suspicion:** every finding cites file:line, the input path, and the concrete impact. If you can't show the path, label it "needs verification", not a vulnerability.
5. **Review only — never exploit.** No attack traffic against `elitcv.com`, no proof-of-concept payloads sent to production; reproduce locally on a copy if needed.
6. **Classify:** *server-side* (PHP/DB/config/files — exploitable, fix first) vs. *client-side* (JS/localStorage/HTML — usually hygiene unless it reaches a server sink or another user's browser). Theme, accent, and language in `localStorage` carry no security meaning and must never be trusted server-side.

## Design Principles
1. **What ships is what you audit** — the deploy package and the server, not just the repo.
2. **Secrets live only in `.env`**, never in code, logs, or backups; a leaked secret is rotated, not just deleted.
3. **Every dependency is attack surface** — known version, known need, known advisories.
4. **Secure defaults:** production behavior must be what happens when config is missing (`APP_ENV` already defaults to `production`; `MOYASAR_MODE` is forced `live` in production — keep both).
5. **Prioritize by exploitability × reach × data sensitivity**, not by count.

## Implementation Rules
- **Endpoint inventory:** for each file record: reachable by whom, method(s), CSRF?, rate-limited?, output type (HTML/JSON/file). Flag JS `fetch()` targets that don't exist (e.g. `dashboard.php` posts to `cv_delete.php`, which is absent from the current tree) — broken flows often get "fixed" later without security review.
- **Leftover/debug files:** `diag.php`, `diag2.php`, `diag3.php` describe themselves as "one-time … then DELETE this file" yet remain in the tree; copies exist in wrong folders (`vendor/diag3.php`, `PHPMailer/schema_migrations.php`), and an orphaned `admin/admin/` duplicate exists. Treat any diagnostic, backup (`*.bak`, `*.old`, `*~`, `*.swp`, `*.zip`), or misplaced file as a finding until proven unreachable on production.
- **Directory exposure:** `.htaccess` denies `.env*` (FilesMatch `^\.env`) and `logs/` (403), but `vendor/` and `PHPMailer/` have no deny rule of their own — check what is directly requestable there (PHP files execute; non-PHP files download).
- **Secrets & `.env` (known project risk):** this project has had credentials hardcoded and exposed in a file that left the server (DB password, OpenAI key, email password — rotated per `READ-ME-FIRST.md`), and has had `.env` go missing after re-uploads. Check: no secret literals in code (`grep` for `sk-`, `sk_live`, `pk_live`, `password =`, `api_key`, private keys); `.env` is in `.gitignore` and never in a deploy zip; `.env.example` holds only placeholders; behavior when `.env` is missing is *safe failure* (empty DB creds → connection error page, not a fallback to defaults or a debug dump); any secret that ever appeared outside `.env` is rotated, not just removed.
- **Dependencies:** `composer.lock` pins `smalot/pdfparser` v2.12.4 and `symfony/polyfill-mbstring` v1.34.0 — run `composer audit` against the lock file (offline/locally), and confirm each package is actually used (`smalot/pdfparser` has no call sites in the current app PHP; an unused dependency is pure attack surface). **PHPMailer is vendored by hand (v7.0.2), outside Composer** — `composer audit` will not see it; check its version against advisories separately. Do not upgrade as part of a review; report.
- **Insecure configuration:** `display_errors` off in production; error logs outside web reach; CSP still `'unsafe-inline'` for scripts (document it as accepted risk with its reason, don't silently accept new relaxations); session cookie flags not set in code (depend on host `php.ini` — verify); `APP_ENV`/`MOYASAR_MODE` defaults; `.htaccess` rules don't apply under PHP's dev server, so header/deny findings must be verified on Apache.
- **Third-party data flows:** `ai.php` (auth-required, per-user rate-limited) sends user content to OpenAI and accepts a client-supplied `mime` value — review what is sent, cost-abuse limits, and that client values can't reach a dangerous sink.
- **Finding format:** Title · Severity (Critical/High/Medium/Low) · Server/Client · Reachability · File:line · Evidence (input → sink) · Impact · Recommendation · Status (confirmed / needs verification).

## What to Inspect Before Making Changes
1. The exact file tree that will be (or was) uploaded, not just the editor's tree.
2. `config.php` (env loading, defaults, PDO options), `.htaccess`, `.env.example`, `.gitignore`.
3. `composer.json` / `composer.lock`, `vendor/`, `PHPMailer/` versions.
4. Every HTTP-reachable PHP file and every front-end `fetch()` target.
5. Git history and deploy archives for secrets that were ever committed or packaged.

## What to Avoid
- Running scanners, fuzzers, or payloads against the live `elitcv.com`.
- Reporting suspicions as confirmed vulnerabilities without an input → sink path.
- "Fixing" during a review — the audit reports; changes go through the normal change process.
- Pasting real secret values into findings, tickets, or chat (reference location only).
- Treating `composer audit` as full dependency coverage.

## Common Mistakes
- Reviewing the repo while production still has stray `diag*.php` files from an earlier upload.
- Deleting an exposed secret from code without rotating it.
- Ranking a missing header above a cross-user data leak because there are more of them.
- Concluding `.htaccess` protections work after testing on `php -S`.
- Missing endpoints that only the JS knows about.

## Practical Examples
- **Good finding:** "`vendor/diag3.php` — Medium/needs verification — Server — anonymous — diagnostic script outside its intended folder; `vendor/` has no deny rule, so it may execute publicly and disclose config state. Recommendation: confirm on production, remove, add deny rules for `vendor/` and `PHPMailer/`."
- **Bad finding:** "The site might have XSS somewhere." (no file, no path, no impact)
- **Good:** secret found in history → "rotate key in provider console, then remove from history; deletion alone is not remediation."

## Quality Checklist
- [ ] Full endpoint inventory (files + JS `fetch` targets) with reachability and protections
- [ ] Diagnostic, backup, duplicate, and misplaced files identified and checked against production
- [ ] No secret literals in code, history, logs, or deploy packages; past leaks rotated
- [ ] `.env` excluded from git and zips, denied by `.htaccess`, and missing-`.env` behavior is a safe failure
- [ ] `composer audit` run on the lock file; unused packages flagged; PHPMailer checked separately
- [ ] Production config verified on Apache (headers, deny rules, cookie flags, `display_errors`)
- [ ] Every finding: severity, server/client, reachability, file:line, evidence, status
- [ ] Nothing was executed against the live site

## Collaborating with Other Skills
- **better-security-web:** the sink-by-sink rules (XSS, SQLi, CSRF, headers) this review applies.
- **better-security-auth:** the access-control and IDOR rules for each inventoried endpoint.
- **better-security-data:** file exposure, upload handling, and CV/PII findings.
- **cybersecurity-expert:** the executive-summary report structure for presenting audit results.
- **security-review (built-in):** diff-scoped review of a pending branch — use this skill for full-codebase and deployment-package audits.
- **devops-engineer:** for the deployment process itself (packaging, upload verification, `.env` presence after upload).
