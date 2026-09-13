# Changelog

All meaningful ParbaTo project changes are recorded here chronologically.  
Historical entries are never erased to “clean up” the project.

Format per entry: **Date · Category · Feature/change · Files/modules · Description · Testing**

---

## 2026-09-13

### Documentation — Project master triad

- **Category:** Documentation  
- **Feature/change:** Created permanent project documentation set  
- **Files/modules:** `PROJECT_REPORT.md`, `PROGRESS.md`, `CHANGELOG.md`  
- **Description:** Established product identity (ParbaTo + ClassTwin + LearnQuest + FlexLearn), architecture decisions, honest progress tracking, and changelog process after full repository/design inspection.  
- **Testing status:** N/A (documentation)

### Documentation — Repository inspection findings

- **Category:** Documentation  
- **Feature/change:** Phase 0 inspection recorded  
- **Files/modules:** `PROGRESS.md`, `PROJECT_REPORT.md`  
- **Description:** Confirmed local workspace contained only Stitch prototypes under `ParbaTo design/` (7 screens + `DESIGN.md` + screenshots). No Laravel application code existed. GitHub remote README-only. Toolchain: PHP 8.2, Composer, Node 24, XAMPP MariaDB available; `gh` not authenticated.  
- **Testing status:** Inspection complete

---

## 2026-09-13 (continued)

### Added — Laravel application foundation

- **Category:** Backend / Database / Frontend / API / Documentation  
- **Feature/change:** Scaffolded ParbaTo Laravel 12 app with Stitch-token Tailwind UI, MySQL schema, auth/RBAC, and MVP engines  
- **Files/modules:** `app/`, `database/migrations/`, `database/seeders/`, `resources/views/`, `resources/css/app.css`, `routes/web.php`, `routes/api.php`, `tests/Feature/*`, `README.md`, `.env.example`  
- **Description:** Implemented session auth; Student/Teacher/Admin roles; courses/ClassTwin/LearnQuest/FlexLearn schema; AttendanceService (hashed codes); FlexLearnRecommendationService (explainable rules); landing, dashboard, live classroom, mission hub/workspace, FlexLearn path, teacher analytics; demo seeder; feature tests. Stitch HTML under `ParbaTo design/` preserved as visual reference. Product chrome branded **ParbaTo**.  
- **Testing status:** 6 feature tests passing (`AuthAndDashboardTest`, `AttendanceServiceTest`); `npm run build` succeeded; `migrate:fresh --seed` succeeded on local MariaDB  

### Unreleased (planned next)

- Teacher ClassTwin session controls UI  
- Mission submission / task completion workflow  
- Course management screens  
- GitHub push after auth  
- Password reset / email verification  
