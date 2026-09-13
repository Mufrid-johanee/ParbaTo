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

### Added — LearnQuest Mission Workspace MVP

- **Category:** Backend / Frontend / API / Database / Testing  
- **Feature/change:** Connected full mission lifecycle: tasks → progress → phase → submit → teacher evaluate → learning evidence  
- **Files/modules:** `app/Services/MissionProgressService.php`, `app/Policies/MissionEnrollmentPolicy.php`, `app/Http/Controllers/LearnQuestController.php`, `app/Http/Controllers/MissionEvaluationController.php`, `app/Http/Controllers/Api/LearnQuestApiController.php`, `resources/views/learnquest/workspace.blade.php`, `resources/views/learnquest/evaluations/*`, `database/seeders/DatabaseSeeder.php`, `routes/web.php`, `routes/api.php`, `tests/Feature/LearnQuestMissionWorkspaceTest.php`  
- **Description:** Progress is calculated server-side (`completed/total×100`). Current phase advances only when phase tasks are done. Students cannot complete evaluate tasks or evaluate themselves. Campus Navigation Micro-App now has Discover→Evaluate tasks and real MDN resources. Evaluation awards XP, updates skill mastery, writes portfolio evidence, and regenerates FlexLearn recommendations.  
- **Testing status:** 5/5 `LearnQuestMissionWorkspaceTest` passing; browser E2E campus complete-task flow passing  

### Added — FlexLearn MVP (rule-based personalized learning)

- **Category:** Backend / Frontend / API / Database / Testing / Documentation  
- **Feature/change:** Learning evidence → deterministic mastery → strengths/weaknesses → explainable recommendations wired to LearnQuest evaluation  
- **Files/modules:** `app/Services/MasteryService.php`, `app/Services/FlexLearnRecommendationService.php`, `app/Models/LearningEvidence.php`, `app/Models/StudentSkill.php`, `app/Http/Controllers/FlexLearnController.php`, `app/Http/Controllers/Api/FlexLearnApiController.php`, `database/migrations/2026_09_13_100000_create_learning_evidences_and_flexlearn_updates.php`, `resources/views/flexlearn/*`, `routes/web.php`, `routes/api.php`, `tests/Feature/FlexLearnMvpTest.php`, `PROGRESS.md`, `CHANGELOG.md`  
- **Description:** No external AI. Mastery is a weighted average of `learning_evidences` (0–100 bands: Needs Support / Developing / Proficient / Advanced). Teacher evaluation writes evidence per mission skill, recalculates mastery, and refreshes recommendations linked to real published missions when available. Student FlexLearn UI shows path, mastery, strength/support bands, and reasons; teacher can view student mastery at `/flexlearn/students`. Recommendation lifecycle: active → started → completed | dismissed. Empty state for students without evidence.  
- **Testing status:** 7/7 `FlexLearnMvpTest` passing; full suite 20/20 passing; LearnQuest workspace tests still green  

### Added — ClassTwin MVP + real classroom/session system (Phase 1)

- **Category:** Backend / Frontend / API / Database / Testing / Documentation  
- **Feature/change:** Classroom create/join, live session lifecycle, hashed attendance, presence heartbeat/polling, session history, ClassTwin APIs  
- **Files/modules:** `ClassroomService`, `ClassSessionService`, `AttendanceService`, `ClassroomController`, `ClassTwinController`, `Api/ClassTwinApiController`, policies, Form Requests, `2026_09_13_120000_extend_classtwin_classroom_session_system.php`, `resources/views/classrooms/*`, `resources/views/classtwin/live.blade.php`, `tests/Feature/ClassTwinMvpTest.php`, docs  
- **Description:** Extended existing ClassTwin schema (kept Course ≠ Classroom). Teachers create classrooms with unique join codes; students join by code; teachers start/end live sessions with hashed attendance codes (plain code only in cache/flash); QR-style code panel + manual attendance; presence states (active/idle/absent) from `last_seen_at` + 15s polling (no Reverb yet); ended sessions store attendance snapshots for history. LearnQuest/FlexLearn left intact and linked from classroom UI.  
- **Testing status:** 11/11 `ClassTwinMvpTest`; full suite **31 passed (96 assertions)**; `migrate:fresh --seed` OK  
- **Limitations:** No WebSockets/Reverb; attendance not auto-fed into FlexLearn mastery  

### Completed — Phase 1 production-ready ClassTwin (final)

- **Category:** Backend / Frontend / API / Testing / Documentation  
- **Feature/change:** Closed remaining Phase 1 gaps — edit/archive/remove member, local SVG QR, dashboard classrooms, full E2E chain  
- **Files/modules:** `QrCodeService` + `bacon/bacon-qr-code`, classroom edit/archive/remove (web+API), `Phase1EndToEndTest`, dashboard classrooms, docs  
- **Description:** Teachers can edit/archive classrooms and remove members. Attendance QR is generated locally as SVG (`PARBATO-ATTEND:{code}`; no external QR API). Prefix stripped on check-in. E2E test proves Classroom → Session → Attendance → LearnQuest evaluate → FlexLearn mastery/recommendation. Verified `migrate:fresh --seed` and `npm run build`.  
- **Testing status:** **33 passed (127 assertions)**; 0 failures  

---
