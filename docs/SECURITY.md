# ParbaTo Security Summary

## Authentication

- Session regeneration on login/register
- Session invalidate + regenerate CSRF on logout
- Password hashing via Laravel `hashed` cast
- Login/register throttled (`throttle:5,1` + RateLimiter)
- Failed/successful logins written to `audit_logs` (no passwords logged)

## Authorization

- Role middleware (`student` / `teacher` / `admin`)
- Policies for Classroom, ClassSession, MissionEnrollment, Assessment, AssessmentAttempt, PortfolioItem, Notifications
- Teacher analytics scoped to owned classrooms
- Admin overview at `/admin` is admin-only

## IDOR

Protected across classrooms, sessions, attendance, missions, assessments/attempts, portfolio, analytics, and notifications. Automated coverage in Phase 1–3 security/feature tests.

## Rate limiting

| Surface | Limit |
|---------|-------|
| Login / register | 5/min |
| Classroom join | 10/min |
| Attendance / join session | 30/min |
| Heartbeat | 120/min (supports ~15s polling) |
| Assessment submit | 20/min |

## Security headers

`SecurityHeaders` middleware sets:

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy` (camera/mic/geo disabled)
- CSP allowing self + Google Fonts / Material Symbols used by the UI
- `script-src` includes `'unsafe-inline' 'unsafe-eval'` because Alpine.js (non-CSP build) evaluates expressions via `Function()`; migrate to `@alpinejs/csp` later to tighten
- `Strict-Transport-Security` only when `APP_ENV=production` and the request is HTTPS (does not break localhost)

## Mass assignment

`users.xp` and `users.level` are not fillable. XP changes go through `XpService` + `xp_ledger` only. Assessment scoring remains server-side.

## Uploads

No general student file-upload feature in the current MVP. If uploads are added later, store privately with MIME/size checks and authorized downloads.

## Audit logging

`AuditLogService` records login success/failure, logout, attendance, mission evaluation (extend as needed). Never log secrets.

## Secrets

- `.env` is gitignored
- `.env.example` uses placeholders only
- Production: `APP_DEBUG=false`, secure session cookies (`SESSION_SECURE_COOKIE=true` behind HTTPS)

## Production checklist

1. HTTPS + HSTS at reverse proxy
2. `APP_DEBUG=false`
3. Strong `APP_KEY`
4. Database credentials rotated
5. Queue worker + scheduler running
6. Backups verified off-host
7. `php artisan optimize`
