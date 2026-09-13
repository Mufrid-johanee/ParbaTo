# ParbaTo — Project Master Document

> **Source of truth** for product identity, architecture, and intended system design.  
> Do not casually alter core concept, vision, mission, or architectural decisions without owner approval.

**Last updated:** 2026-09-13  
**Status:** Phase 1 documentation complete; implementation starting from greenfield + Stitch design prototypes

---

## Project name

**ParbaTo**

## Tagline

**One Classroom. Many Paths. Real-World Learning.**

---

## Project overview

ParbaTo is a production-oriented blended-learning EdTech SaaS platform that bridges physical classrooms with digital environments. It unifies three tightly integrated engines:

1. **ClassTwin** — physical ↔ digital classroom synchronization  
2. **LearnQuest** — mission / project-based real-world learning  
3. **FlexLearn** — explainable, personalized learning paths  

The platform is designed for academic demonstration, professional presentation, GitHub publication, and extension into a real product. It is **not** a generic LMS; it remains centered on classroom presence, missions, personalization, learning evidence, and analytics.

---

## Executive summary

Conventional learning systems fragment attendance, content, assessment, and practice into disconnected tools. Teachers lack live situational awareness; students lack meaningful project work and adaptive support; institutions lack explainable evidence of learning.

ParbaTo solves this by treating the physical classroom as a first-class digital twin (ClassTwin), converting curriculum into structured real-world missions (LearnQuest), and generating transparent rule-based recommendations from learning evidence (FlexLearn). Laravel + MySQL form the backend; the Stitch-generated dark immersive UI is the visual source of truth for the frontend.

---

## Problem statement

Learners and educators operate across disconnected physical and digital channels. Attendance is unreliable or gameable; classroom engagement is invisible after class; assignments rarely map to real problems; personalization is either absent or opaque (“AI said so”); and analytics stop at average scores instead of actionable insight.

---

## Existing problems in conventional learning systems

- Physical classroom activity is not captured digitally in real time  
- Digital LMS tools ignore seat presence, help requests, and live participation  
- Content delivery dominates over project-based problem solving  
- Gamification is often childish or cosmetic rather than professional PBL  
- Recommendation engines (when present) are black boxes  
- Analytics report scores without explaining *why* students struggle  
- Mobile experience is usually a shrunk desktop layout  
- Learning evidence is scattered and not portfolio-ready  

---

## Proposed solution

A single platform where:

- A live ClassTwin session mirrors the physical room (presence, activities, help, quizzes)  
- LearnQuest missions drive Discover → Evaluate learning workflows  
- FlexLearn recommends next practice/missions with human-readable reasons  
- Teachers receive insight-oriented analytics computed from real stored data  
- Students accumulate a professional learning profile and portfolio  

---

## Core concept

**Physical Classroom + Digital Classroom + Real-World Missions + Personalized Learning + Learning Evidence + Learning Analytics**

Product identity is permanently **ParbaTo**. Engine names are permanently **ClassTwin**, **LearnQuest**, and **FlexLearn**.

---

## ClassTwin concept

ClassTwin connects the physical classroom with a digital representation (“twin stage”).

**Capabilities (target):** classroom creation/scheduling, live sessions, QR + validated attendance, student presence map, live activity status, quizzes, participation tracking, help requests, announcements, session history, classroom analytics.

The UI represents desks/nodes with telemetry states while remaining practical and responsive (tabbed/single-column on mobile).

---

## LearnQuest concept

LearnQuest converts traditional lessons into professional, mission-based learning with controlled gamification (XP, levels, badges) — not childish game UI.

**Mission lifecycle:** Discover → Learn → Practice → Build → Submit → Present → Evaluate

Teachers author missions (problem, objectives, skills, tasks, resources, criteria, XP, team/individual). Students browse, start, complete tasks, submit, receive feedback, and build evidence.

---

## FlexLearn concept

FlexLearn builds personalized paths from learning evidence (assessments, attendance, classroom activity, mission performance, skill mastery, history).

**MVP approach:** transparent rule/score-based recommendations with explicit reasons.  
**Non-goal for MVP:** mandatory AI. Future AI may be layered on without replacing the explainable engine.

Example: *IF validation mastery < threshold THEN recommend Validation Practice* — “Recommended because your recent assessment showed difficulty with form validation.”

---

## How the three systems work together

1. ClassTwin captures live presence and in-class performance signals  
2. LearnQuest structures curriculum as missions and produces submission/mastery evidence  
3. FlexLearn consumes that evidence to recommend next practice, missions, or remediation  
4. Teacher analytics aggregates all three for cohort insight and intervention  

Evidence flows one way into recommendations and analytics; product UX keeps the three engines visible and named.

---

## Vision

To become the reference blended-learning platform where every physical class session produces digital learning evidence that drives real-world missions and personalized growth.

## Mission

Deliver a serious, scalable, responsive EdTech product that makes classroom presence, project-based learning, and explainable personalization inseparable.

## Objectives

- Implement ClassTwin, LearnQuest, and FlexLearn as working product engines  
- Preserve Stitch design fidelity  
- Enforce RBAC (Student, Teacher, Admin) with secure Laravel architecture  
- Provide meaningful teacher analytics from real data  
- Ship responsive desktop/tablet/mobile experiences  
- Maintain documentation (`PROJECT_REPORT`, `PROGRESS`, `CHANGELOG`) as living project artifacts  

### Short-term goals

- Laravel + MySQL foundation, auth/RBAC, core schema  
- Port Stitch screens into Blade/Vite/Tailwind application UI  
- Working ClassTwin sessions + QR attendance validation  
- LearnQuest mission CRUD + student workspace flow  
- FlexLearn rule engine + student path UI  
- Teacher analytics v1 from real aggregates  

### Long-term goals

- Real-time sync (websockets/SSE), richer twin visualization  
- Portfolio export / shareable learning records  
- Optional Firebase Auth and optional AI recommendation layer  
- Multi-tenant institutional deployment  
- Mobile apps / PWA enhancements  

---

## Target users

- University / college students in blended courses  
- Teachers / faculty running lab or lecture sessions  
- Program admins / academic coordinators  

## User roles

| Role | Primary access |
|------|----------------|
| **Student** | Dashboard, ClassTwin join/QR, missions, FlexLearn path, portfolio |
| **Teacher** | Courses/classrooms, live session control, mission authoring, analytics |
| **Admin** | Users, roles, system configuration, audit visibility |

Authorization is policy/gate based so additional roles can be added later.

---

## Educational model

Blended, evidence-based, project-centered learning with adaptive remediation.

## Blended learning model

Physical session (presence + live activity) ↔ Digital twin and materials ↔ Mission practice/build ↔ Personalized follow-up practice ↔ Analytics feedback loop for teachers.

## Learning workflow

Enroll → Attend ClassTwin session → Engage activities → Launch related LearnQuest mission → Submit evidence → FlexLearn updates path → Teacher reviews analytics → Portfolio grows.

## User journeys

**Student:** Register → Join course/classroom → Scan QR / join live session → Complete activity → Start mission → Practice → Submit → View recommendations → Build portfolio  

**Teacher:** Register → Create course/classroom → Schedule/start session → Monitor twin + attendance → Author mission → Grade/feedback → Review analytics → Intervene  

**Admin:** Manage users/roles → Monitor system health → Configure integrations  

---

## Functional requirements (summary)

- Auth: register, login, logout, password reset; optional email verification; session management; RBAC  
- Course management (CRUD, modules, materials, enrollment)  
- Classroom management + live sessions  
- Attendance (QR server-validated + manual teacher)  
- ClassTwin live activities, help requests, announcements  
- LearnQuest missions, tasks, resources, submissions, feedback, XP  
- Assessment engine (MCQ, T/F, short answer, quiz, assignment, project, pre/post)  
- FlexLearn recommendations with reasons  
- Student learning profile + portfolio foundation  
- Teacher analytics insights from stored data  
- Notifications / toasts / empty/loading/error states  

## Non-functional requirements

- Responsive: desktop, tablet, mobile (no required horizontal scroll)  
- Security: CSRF, XSS, SQLi prevention, IDOR protection, mass-assignment guards, rate limiting, safe uploads  
- Performance: indexes, pagination, eager loading, queues for heavy work  
- Accessibility: focus states, reduced-motion respect, semantic structure  
- Secrets only via `.env`; `.env.example` documents names only  
- No fabricated analytics  

---

## Core features

ClassTwin live classroom, LearnQuest hub + workspace, FlexLearn adaptive path, student dashboard, teacher analytics, auth/RBAC, courses/classrooms/attendance.

## Advanced features

Live quizzes, help-request workflows, skill mastery graphs, explainable recommendations, mission performance matrix, portfolio evidence.

## Future features

AI recommendation layer, Firebase Auth (when keys supplied), websocket twin sync, institutional multi-tenancy, exported credentials / badges, deeper mobile PWA.

---

## System architecture

```
[Browser / Responsive UI]
        │  HTTPS
[Laravel Web + API]
        │
[Services / Policies / Jobs]
        │
[MySQL]
        │
[Optional: Firebase Auth, Queue worker, Object storage]
```

## Application architecture

Laravel MVC + Service classes for complex domain logic (attendance validation, recommendation engine, analytics aggregation). Form Requests, Policies, API Resources, Events/Listeners, Jobs, Notifications as needed. Repository pattern only where it clearly helps.

## Database architecture

Normalized MySQL schema with FKs, indexes, unique constraints, timestamps; soft deletes where justified. Derived aggregates computed or cached only with clear need.

## API architecture

RESTful JSON endpoints for SPA-like interactions and future clients; web routes for Blade views. Consistent envelope, HTTP status codes, pagination/filter/sort, authenticated + authorized.

## Authentication architecture

Primary: Laravel session auth (Breeze/Fortify-style or custom).  
Optional later: Firebase Authentication via env-configured credentials — structure prepared, not mandatory for MVP.

## Authorization / RBAC

Roles on users (`student`, `teacher`, `admin`); Laravel Policies/Gates for resources (course, classroom, mission, assessment). Server-side checks on every mutating action.

## Frontend architecture

- Stitch design tokens (colors, type, spacing) as Tailwind theme  
- Blade layouts + reusable components extracted from repeated Stitch structures  
- Vite for assets; Alpine.js for light interactivity; progressive enhancement  
- Design folder `ParbaTo design/` retained as visual reference (not deleted)  
- Product brand in app UI: **ParbaTo**; engines labeled ClassTwin / LearnQuest / FlexLearn  

## Backend architecture

Laravel 11+ (PHP 8.2+), MySQL/MariaDB, queued jobs for heavy analytics/notifications.

## Technology stack

| Layer | Choice |
|-------|--------|
| Backend | Laravel (PHP 8.2+) |
| DB | MySQL / MariaDB (XAMPP for local) |
| Frontend | Blade + Vite + Tailwind CSS 3 |
| Icons/Fonts | Material Symbols, Plus Jakarta Sans, Inter, JetBrains Mono |
| Auth | Laravel session (Firebase optional later) |
| Tests | PHPUnit / Pest |
| Hosting target | Standard PHP host / VPS (documented in deployment section) |

## External services

None required for MVP core. Optional later: Firebase Auth, mail provider, object storage. Missing keys must degrade gracefully.

## Data flow

User actions → Controllers/Form Requests → Services → Eloquent/MySQL → Resources/Views. Evidence tables feed FlexLearn rules and analytics services.

## Feature-to-technology mapping

| Feature | Tech |
|---------|------|
| Live classroom UI | Blade + Tailwind + polling/SSE (later websockets) |
| QR attendance | Server token + signed session code |
| Missions | Eloquent + file storage for submissions |
| Recommendations | PHP rule engine service |
| Analytics | SQL aggregates + service layer |
| Charts | Lightweight SVG/CSS or Chart.js |

---

## Security architecture

CSRF on web forms; hashed passwords; policies for IDOR; validated uploads; rate limits on auth/attendance; no secrets in repo; audit_logs for sensitive actions.

## Performance strategy

Indexes on FKs and query filters; pagination; eager loading; queue expensive work; avoid premature caching.

## Scalability strategy

Stateless app servers + MySQL; queue workers; future read replicas / Redis cache; modular engines allow independent scaling of real-time layer.

## Testing strategy

Feature tests for auth, RBAC, attendance validation, mission workflow, recommendation rules, analytics calculations. Manual responsive checks for major screens.

## Deployment strategy

`.env` configuration; `php artisan migrate --force`; build assets (`npm run build`); web server document root `public/`. Documented in README when production-ready.

## Backup / recovery strategy

Scheduled MySQL dumps; retain migration history; document restore steps before production.

## Accessibility strategy

Keyboard focus rings, semantic headings, contrast on dark theme, `prefers-reduced-motion`, alt text for meaningful images.

## Responsive design strategy

Desktop multi-pane (nav rail + canvas + sidebar); tablet drawers; mobile bottom nav / single column / tabs for twin & complex views. Source: Stitch `DESIGN.md`.

## Mobile strategy

Touch targets, bottom navigation for app shell, no tiny twin grids — use list/tab representations on small screens.

---

## Current limitations (as of 2026-09-13)

- Local workspace previously contained **only** Stitch HTML prototypes + `DESIGN.md`  
- GitHub remote ([Mufrid-johanee/ParbaTo](https://github.com/Mufrid-johanee/ParbaTo)) had README-only presence  
- No Laravel application code existed before Phase 1  
- No Firebase credentials supplied (optional integration scaffolding only)  
- Real-time twin sync not yet implemented (polling/static first)  

## Future roadmap

Phases 2–13 per master plan: schema → auth → courses/classrooms → ClassTwin → LearnQuest → assessment → FlexLearn → analytics → portfolio → responsive polish → testing/security → production prep.

## AI integration roadmap

Post-MVP optional layer for natural-language feedback and richer path suggestions; must remain secondary to explainable rules; never block core offline rule engine.

---

## Development principles

1. Owner instruction > PROJECT_REPORT > Stitch design > master prompt > existing code > Laravel norms  
2. Preserve Stitch visual language; do not replace with Bootstrap/AdminLTE defaults  
3. Never destroy working work; smallest fix wins  
4. Do not invent API keys or hardcode secrets  
5. Do not rename ParbaTo or the three engines  
6. Mark progress honestly in `PROGRESS.md`  
7. Autonomous implementation for inferable decisions; stop for irreversible/product identity changes  

---

## Project folder structure (target)

```
ParbaTo/
├── PROJECT_REPORT.md
├── PROGRESS.md
├── CHANGELOG.md
├── README.md
├── ParbaTo design/          # Stitch visual source of truth (reference)
├── app/
│   ├── Http/{Controllers,Requests,Middleware,Resources}
│   ├── Models/
│   ├── Policies/
│   ├── Services/            # Attendance, Recommendation, Analytics, ...
│   └── ...
├── database/{migrations,seeders,factories}
├── resources/{views,css,js}
├── routes/{web.php,api.php}
├── tests/
└── public/
```

---

## Database entities (planned MVP set)

`users`, `roles` (or role enum + future roles table), `courses`, `course_modules`, `materials`, `course_enrollments`, `classrooms`, `classroom_members`, `class_sessions`, `attendance_records`, `classroom_activities`, `help_requests`, `announcements`, `missions`, `mission_tasks`, `mission_resources`, `mission_submissions`, `mission_task_progress`, `assessments`, `questions`, `assessment_attempts`, `skills`, `student_skills`, `recommendations`, `achievements`, `badges`, `user_achievements`, `notifications`, `portfolio_items`, `audit_logs`

Create only what each milestone needs; expand with migrations.

---

## Major API endpoints (planned)

```
POST   /api/auth/login|register|logout
GET    /api/me
CRUD   /api/courses, /api/classrooms, /api/sessions
POST   /api/sessions/{id}/attendance/qr
GET|POST activities, help-requests, announcements
CRUD   /api/missions, tasks, submissions
GET    /api/flexlearn/recommendations
GET    /api/analytics/classroom/{id}
GET    /api/portfolio/me
```

Web Blade routes mirror major screens for the primary UI.

---

## Development assumptions

- Local stack: Windows + XAMPP (PHP 8.2, MariaDB), Composer, Node 24  
- Product UI brand is **ParbaTo**; Stitch prototypes historically labeled “ClassTwin” in chrome — application replaces product chrome with ParbaTo while keeping ClassTwin as the classroom engine name  
- Firebase not required until owner supplies config  
- MVP recommendations are rule-based and explainable  
- Design HTML under `ParbaTo design/` remains reference material and is not the runtime app  

---

## Implementation decision log

| Date | Decision | Rationale |
|------|----------|-----------|
| 2026-09-13 | Laravel + Blade/Vite/Tailwind (not SPA-first) | Fastest high-fidelity port of Stitch HTML; API available for progressive enhancement |
| 2026-09-13 | Keep `ParbaTo design/` in repo | Visual source of truth per owner prompt |
| 2026-09-13 | Session auth first; Firebase optional | No credentials supplied; avoid blocking MVP |
| 2026-09-13 | Rule-based FlexLearn for MVP | Explainability + no AI dependency |
