---
name: better-security-auth
description: Use whenever building or reviewing login, registration, Google OAuth, logout, password change/reset, account deletion, session handling, admin/staff role checks, impersonation, or ANY endpoint that loads, changes, or deletes a record by ID on ElitCV. Load before writing the access check, not after. Owns authentication, authorization, and preventing one user from reaching another user's data (IDOR).
---

# Better Security Auth — Senior Authentication & Authorization Specialist

## Role
You are acting as a senior authentication and authorization specialist for ElitCV (vanilla PHP 8.3 + MariaDB on Hostinger). ElitCV has three kinds of principal — **users** (`$_SESSION['user_id']`), **staff**, and **full admins** (both `$_SESSION['admin_id']` + `$_SESSION['admin_role']`, via `admin/login.php` / `admin/google-callback.php`) — plus an **impersonation** mode where an admin acts as a user. Every one of those boundaries is enforced by hand in PHP.

## Purpose
Guarantee that every request is made by who it claims, that it can only do what that principal is allowed to do, and — above all — that no user, staff member, or bug can read, change, or delete another user's CV, account, payments, or tickets.

## Responsibilities
- Login security (email/password and Google OAuth, user and admin), registration, logout.
- Account security: password change, account deletion, email/identity changes.
- Authentication flow review end to end, including OAuth `state` validation.
- Authorization: user vs. staff vs. full admin, `admin/_auth.php`, `require_full_admin()`, impersonation.
- Password reset flow (`forgot.php` → `reset.php`, and admin-triggered resets in `admin/user_action.php`).
- Session management: creation, regeneration, expiry, logout, impersonation enter/exit.
- **Cross-user access prevention (IDOR):** every ID-taking endpoint, as its own explicit check.

## When to Use This Skill
Before writing or changing: any page or endpoint guard; any query that selects/updates/deletes by an ID or email that came from the request; any login, OAuth, reset, logout, or impersonation code; any new admin/staff page or action; any feature gated by payment (CV unlock).

## How to Think When Using This Skill
1. **Identity comes from the session, never the request.** The owner of a record is `$_SESSION['user_id']`; an ID in `$_GET`/`$_POST`/JSON is only *which* record — ownership must be re-proven in the same query.
2. **Ask "what if this ID belongs to someone else?"** for every endpoint that takes one. If the answer is anything other than "the query returns nothing", it's an IDOR.
3. **Authorization is server-side and per-request.** The admin sidebar hiding links for staff is cosmetic (`admin/_sidebar.php` says so); the boundary is `require_full_admin()` in each restricted page/action.
4. **Know the role semantics exactly:** `require_full_admin()` denies only the literal role `'staff'` — *any other role value is treated as full admin*. Adding a new role means deciding its access deliberately, not by accident.
5. **Severity:** cross-user data access or account takeover → Critical; privilege escalation (staff → admin, user → admin) → Critical; user enumeration or weak reset token → High; missing session hardening with no direct exploit → Medium.

## Design Principles
1. **Deny by default:** a page with no guard is a public page — that must be a conscious decision.
2. **Ownership in the WHERE clause:** `… WHERE id = ? AND user_id = ?` — not a separate "check then act" that can drift.
3. **Least privilege:** staff get only what support work needs; impersonation never grants more than the impersonated user has.
4. **Re-authenticate for sensitive actions:** password change and account deletion already require the current password (`settings.php`) — keep that pattern for any new sensitive action.
5. **Uniform responses:** login, register, and forgot-password never reveal whether an account exists.

## Implementation Rules
- **User pages/endpoints:** first lines check `empty($_SESSION['user_id'])` → redirect (pages) or `401` JSON (AJAX), as `dashboard.php` and `ai.php` do. Cast once: `$uid = (int)$_SESSION['user_id'];`.
- **Admin pages/actions:** include `admin/_auth.php` first; call `require_full_admin()` (or `require_full_admin(json: true)` for AJAX) on anything staff must not reach; keep the CSRF block every `admin/*_action.php` already has.
- **Login:** `password_verify()`; `session_regenerate_id(true)` immediately after success (present in `login.php`, `admin/login.php`, both `google-callback.php`); `rate_limit.php` on every attempt; one generic failure message for wrong email and wrong password. Consider `password_needs_rehash()` on success (not currently used).
- **OAuth:** reject the callback unless `state` matches `$_SESSION['csrf']` via `hash_equals`; bind the account by the provider's verified email/subject, never by a request parameter.
- **Password reset:** tokens are 32 random bytes (`bin2hex(random_bytes(32))`), 1-hour expiry, deleted per-email on new request and after use (`reset.php` deletes on success — keep it single-use). Tokens are currently stored raw in `password_resets.token`; the stronger pattern stores `hash('sha256', $token)` and looks up by hash, so a DB leak can't be replayed. Always: identical response whether or not the email exists; rate-limited; link host hardcoded (`https://elitcv.com/reset.php?token=…`, never from `$_SERVER['HTTP_HOST']`); after reset, invalidate other sessions for that account.
- **Logout:** `logout.php` currently only calls `session_destroy()`. A complete logout also clears `$_SESSION = []` and expires the session cookie (`setcookie(session_name(), '', time()-3600, …)` with matching params) before destroying.
- **Impersonation (`admin/impersonate.php` / `impersonate_exit.php`):** full-admin only, CSRF-protected, and audit-logged (`audit_lib.php`) — keep all three. Regenerate the session on enter and exit; the original admin identity lives only in `$_SESSION['impersonating_admin_*']`. While impersonating, block account-level actions (password change, account deletion, payments) — the admin must not act irreversibly as the user.
- **Payment-gated features:** a CV unlocks only after server-side verification of the payment with the Moyasar secret key (`checkout.php`), never from a client callback or query flag; the unlock query stays scoped `WHERE id = ? AND user_id = ?` (`cv_lib.php`).

## Cross-User Access (IDOR) — Mandatory Check
For **every** endpoint that accepts an ID, email, filename, or token from the request:
1. The record's owner is constrained in the same SQL statement (`AND user_id = ?` with the session `$uid`). Applies to `cvs`, `support_tickets`, payments/unlocks, avatars, and any future user-owned table.
2. "Not found" and "not yours" return the same response (no enumeration).
3. Follow-up queries reuse the already-ownership-checked row's ID, not a fresh request value (e.g. download counters in `cv_action.php` must stay downstream of an owned `$cv`).
4. Staff/admin access to a user's data is an explicit, guarded, and logged path (`admin/cv_view.php`, `admin/user.php`) — never a side effect of a user endpoint skipping its check.
5. Sequential IDs are assumed guessable; ownership checks, not obscurity, are the control.

## What to Inspect Before Making Changes
1. Which principal reaches this code, and where that's enforced (file + line).
2. Every request-supplied identifier, and whether the query constrains ownership.
3. Whether the change creates or alters a role, or touches `require_full_admin()` semantics.
4. Whether session identity changes here (needs regeneration) or ends here (needs full logout).
5. Whether a staff account or an impersonating admin could reach it, and whether it should.

## What to Avoid
- `SELECT … WHERE id = ?` on user-owned tables without `AND user_id = ?`.
- Taking `user_id`, `role`, `is_admin`, or `is_unlocked` from the request.
- Distinct error messages that reveal whether an email is registered.
- Relying on hidden UI (sidebar, disabled buttons) as access control.
- Long-lived, reusable, or plaintext-compared reset tokens without expiry.

## Common Mistakes
- New admin action copied from an old one, keeping `_auth.php` but forgetting `require_full_admin()` — staff gain admin powers.
- Ownership checked on the page that shows the "Delete" button, but not in the endpoint that deletes.
- Adding a role like `'support_lead'` and not realizing `require_full_admin()` treats it as a full admin.
- Regenerating the session on password login but not on the OAuth path (or the reverse).
- Logout that destroys server data but leaves the cookie, so the old ID lingers.

## Practical Examples
- **Good:** `$st = $pdo->prepare('DELETE FROM cvs WHERE id = ? AND user_id = ?'); $st->execute([(int)$_POST['cv_id'], $uid]); if ($st->rowCount() === 0) { /* same response as not found */ }`
- **Bad:** `$pdo->prepare('DELETE FROM cvs WHERE id = ?')->execute([$_POST['cv_id']]);` — any logged-in user deletes anyone's CV by changing a number.
- **Good:** `forgot.php` shows "If an account exists, we've sent a link" for every email.
- **Bad:** "No account found with that email" — free user enumeration.

## Quality Checklist
- [ ] Every page/endpoint has an explicit guard (or is intentionally public)
- [ ] Staff-restricted actions call `require_full_admin()`; new roles have deliberate access
- [ ] **IDOR: every request-supplied ID is ownership-scoped in the same query; "not yours" == "not found"**
- [ ] Login: `password_verify`, generic errors, rate-limited, `session_regenerate_id(true)` on every path
- [ ] OAuth `state` validated with `hash_equals`
- [ ] Reset tokens: random, hashed at rest, 1-hour expiry, single-use, no enumeration, other sessions invalidated
- [ ] Logout clears `$_SESSION`, expires the cookie, destroys the session
- [ ] Sensitive actions require re-authentication
- [ ] Impersonation: full-admin only, CSRF, audit-logged, regenerated, irreversible actions blocked
- [ ] Paid unlocks verified server-side with Moyasar before any state change

## Collaborating with Other Skills
- **better-security-web:** owns CSRF tokens, session cookie flags, and output encoding used by every auth flow here.
- **better-security-data:** owns file-level and CV-data authorization (serving uploads, exports); this skill's IDOR rule is the same principle applied to records.
- **better-security-audit:** uses this skill's checklist when reviewing auth code and endpoint inventories.
- **better-ui / better-accessibility:** auth forms keep real labels, bilingual errors announced via `aria-live`, and visible focus — generic security messages must still be accessible.
- **cybersecurity-expert:** report format for full reviews.
