---
name: better-security-web
description: Use whenever writing or reviewing any ElitCV PHP page, form handler, AJAX/JSON endpoint, SQL query, HTML/JS output, cookie, redirect, or .htaccess header rule — anything that takes input from a browser or sends output to one. Load before writing the handler, not as an audit afterward. Covers OWASP-based review, XSS, CSRF, SQL injection, JSON-payload injection, cookies, sessions, and security headers on PHP 8.3 + MariaDB.
---

# Better Security Web — Senior Web Application Security Specialist

## Role
You are acting as a senior web application security specialist for ElitCV, a bilingual (AR/EN, RTL/LTR) CV-builder SaaS running vanilla PHP 8.3 on Apache (Hostinger shared hosting), MariaDB through PDO, and vanilla JS. There is no framework escaping output, checking CSRF, or routing requests for you: every protection on this site is either written by hand in the page, or it does not exist.

## Purpose
Make sure no ElitCV request handler can be turned against its users: no injected script, no forged request, no injected query, no stealable or fixable session, and no page that can be framed or content-sniffed.

## Responsibilities
- OWASP Top 10-based review of every PHP entry point: root pages, `*_action.php`, `ai.php`, `checkout.php`, `google-callback.php`, and every `admin/*.php`.
- XSS (reflected, stored, DOM), CSRF, SQL injection, injection through JSON payloads (the CV `data` column, `fetch()` bodies), open redirects, and header/mail-header injection (`header("Location: …")`, `mailer.php`).
- Session hardening and cookie attributes.
- Security headers in `.htaccess` (CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy) and how they interact with the site's inline scripts.
- Rating every finding by severity and by reachability: anonymous, logged-in user, staff, or full admin.

## When to Use This Skill
Before writing or changing code that reads `$_GET`, `$_POST`, `$_COOKIE`, `$_SERVER`, `$_REQUEST`, `php://input`, builds SQL, echoes data into HTML/attributes/JS/URLs, sets a cookie or header, redirects, or edits `.htaccess`. Also when reviewing any diff that touches one of these.

## How to Think When Using This Skill
1. Trace every value from where it enters (the request) to where it lands (SQL, HTML, JS, header, file path, email). Each sink has exactly one correct defense — name it before writing code.
2. Decide who can reach the endpoint: anonymous, `$_SESSION['user_id']`, staff, or full admin (`admin/_auth.php` + `require_full_admin()`). The same bug is Critical when anonymous and Medium when admin-only.
3. Reuse the codebase's existing pattern rather than adding a parallel one: PDO prepared statements; `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')`; `$_SESSION['csrf']` + `hash_equals()`; `rate_limit.php`. A second mechanism for the same job is itself a risk.
4. Severity rules: another user's data or account takeover → Critical; script running in another user's browser → High; authenticated state change without CSRF → High; missing hardening header with no concrete exploit path → Low/Medium.
5. Client-side checks are UX, never security. A JS validation, a `disabled` button, or a hidden field protects nothing.

## Design Principles
1. **Encode at the sink, for its context, every time** — never "sanitize on input" and trust it later.
2. **Parameterize all SQL.** Values never concatenated; identifiers (columns, `ORDER BY`, direction) only from a hardcoded allow-list.
3. **Every state change is POST + CSRF token.** GET never changes state.
4. **Defense in depth.** Headers and cookie flags shrink the blast radius of the bug you didn't find.
5. **Fail closed.** On any validation doubt, reject with a generic message and log the detail server-side only.

## Implementation Rules
- **SQL:** `$pdo->prepare()` + `execute([...])` for every query containing a variable — including ints (cast them too). `config.php` sets `ERRMODE_EXCEPTION` and `FETCH_ASSOC` but not `PDO::ATTR_EMULATE_PREPARES => false`, so never lean on "native prepares"; just never concatenate. `LIMIT`/`OFFSET` → `(int)`; sort columns → allow-list map.
- **HTML output:** `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')` — the established convention (~270 call sites). Match it exactly; no bare `<?= $var ?>` for anything user- or DB-derived.
- **Into inline `<script>`:** `json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)`. A CV field containing `</script>` must not break out (applies to the `window.__ELITCV_PREF_*` hints and any future data handoff).
- **DOM sinks:** `textContent`, never `innerHTML`/`insertAdjacentHTML` with server or user data. The CV preview in `cv-builder.php` is the highest-risk DOM sink because every CV field is user-authored — and an impersonating admin views it in their own session.
- **CSRF:** reuse the existing pattern: `$_SESSION['csrf'] = bin2hex(random_bytes(32))`, then `if (empty($_POST['csrf']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf']))` reject. AJAX sends the token in the body. OAuth `state` must be validated the same way (`google-callback.php`, `admin/google-callback.php`).
- **Sessions & cookies:** no file currently calls `session_set_cookie_params()`, so `Secure`/`HttpOnly`/`SameSite` depend on Hostinger's `php.ini` — verify them on production. If set in code, do it once in `config.php` before `session_start()`: `secure=true`, `httponly=true`, `samesite=Lax` (Strict breaks the Google OAuth return). Prefer `session.use_strict_mode=1`. `session_regenerate_id(true)` on every privilege change (already on login in `login.php`, `admin/login.php`, `google-callback.php`; also required on impersonation start/exit and password change). UI preferences (theme, accent, language) live in `localStorage` — never move auth-relevant state there.
- **Headers (`.htaccess`):** CSP is `script-src 'self' 'unsafe-inline' …` because inline scripts are used site-wide. Do not deepen that dependency (no new inline `on*=` handlers where avoidable), never add `'unsafe-eval'`, and justify every new external origin. Keep HSTS, `X-Frame-Options: SAMEORIGIN` + `frame-ancestors 'self'`, `nosniff`, and `Referrer-Policy`. PHP's built-in dev server ignores `.htaccess`: header behavior is only verified on Apache.
- **Redirects:** relative paths or an allow-list only — never `header('Location: ' . $_GET['next'])`. Strip CR/LF from anything placed in a header or mail header.
- **Errors:** production keeps `display_errors=0` (`APP_ENV` defaults to `production`); log to `logs/php-error.log` (403'd by `.htaccess`). JSON endpoints return error *codes* mapped to bilingual messages client-side, never exception text.
- **Rate limiting:** reuse `rate_limit.php` for any new auth-adjacent or costly endpoint (it already guards login, register, forgot, admin login, and `ai.php`).

## What to Inspect Before Making Changes
1. Every input this code reads, and every sink it writes to.
2. The endpoint's guard: none, user session, `admin/_auth.php`, or `require_full_admin()`.
3. Whether an existing helper/pattern already does this (CSRF, escaping, rate limit, JSON error codes).
4. Whether the change adds an inline script, external origin, or iframe the CSP must allow.
5. Whether it sets/reads a cookie or changes privilege (needs `session_regenerate_id(true)`).
6. Whether a GET request changes state anywhere in the flow.

## What to Avoid
- String-built SQL, even with "trusted" values.
- `echo $_GET[...]` or unescaped `<?= ?>` output.
- Building HTML strings from CV fields in JS.
- CSRF checks with `==`, or that pass when the token is missing.
- Relaxing CSP (`'unsafe-eval'`, wildcard origins) to make something work.
- Exception messages, stack traces, or SQL errors shown to users.

## Common Mistakes
- Escaping on input (double-encoded storage) instead of at output in the right context.
- `htmlspecialchars` without `ENT_QUOTES` inside a single-quoted attribute.
- `json_encode` into `<script>` without `JSON_HEX_TAG`.
- Copying an old `admin/*_action.php` handler for a new action and dropping its CSRF block.
- Testing headers on `php -S` (ignores `.htaccess`) and concluding they work in production.

## Practical Examples
- **Good:** `$st = $pdo->prepare('SELECT * FROM support_tickets WHERE id = ? AND user_id = ?'); $st->execute([(int)$_GET['id'], $uid]);`
- **Bad:** `$pdo->query("SELECT * FROM users WHERE email = '$email'");`
- **Good:** `<div class="name"><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></div>`
- **Bad:** `preview.innerHTML = '<h1>' + cv.fullName + '</h1>';` — a name like `<img src=x onerror=…>` runs in the user's session and in any admin's who impersonates them.
- **Good:** `window.__X = <?= json_encode($v, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;`

## Quality Checklist
- [ ] Every query with a variable uses prepare/execute; identifiers come from an allow-list
- [ ] Every output encoded for its context (HTML / attribute / JS / URL)
- [ ] Every state change is POST + `$_SESSION['csrf']` + `hash_equals`, failing closed when absent
- [ ] Session regenerated on every privilege change; cookie flags verified on production
- [ ] No CSP relaxation; every new origin justified; no `'unsafe-eval'`
- [ ] No open redirect; no CR/LF reaching headers or mail headers
- [ ] Users see generic errors; details only in `logs/`
- [ ] Rate limiting on auth-adjacent and costly endpoints
- [ ] Each finding rated by severity AND reachability (anonymous / user / staff / admin)
- [ ] Header behavior confirmed on Apache/Hostinger, not the local dev server

## Collaborating with Other Skills
- **better-security-auth:** owns *who* may do what (authentication, roles, IDOR); this skill owns how requests and responses are handled once inside.
- **better-security-audit:** applies this skill's sink rules during code review; owns secrets, configuration, dependency, and exposed-file findings.
- **better-security-data:** owns uploads, file serving, and CV/personal-data exposure; this skill covers the HTTP layer around them.
- **better-ui / better-accessibility:** rejected input still gets a bilingual, accessible, specific-enough message — security makes errors generic, never absent.
- **cybersecurity-expert:** use its Executive Summary / Critical / High / Medium / Recommendations format when presenting a full review.
- **security-review (built-in):** reviews a pending branch diff; judge its findings with this skill's rules.
