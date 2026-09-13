# ParbaTo

**One Classroom. Many Paths. Real-World Learning.**

Production-oriented blended learning platform built around three engines:

- **ClassTwin** — physical ↔ digital classroom synchronization  
- **LearnQuest** — mission / project-based learning  
- **FlexLearn** — explainable personalized learning paths  

Stack: **Laravel 12 · MySQL/MariaDB · Blade · Vite · Tailwind CSS 4**

Visual source of truth: Stitch prototypes in `ParbaTo design/` (see `DESIGN.md`).

---

## Documentation

| File | Purpose |
|------|---------|
| [`PROJECT_REPORT.md`](PROJECT_REPORT.md) | Permanent product & architecture source of truth |
| [`PROGRESS.md`](PROGRESS.md) | Honest implementation status |
| [`CHANGELOG.md`](CHANGELOG.md) | Chronological change history |

---

## Local setup (Windows + XAMPP)

1. Start **Apache** (optional) and **MySQL** in XAMPP.
2. Clone / open this repository.
3. Install dependencies:

```bash
composer install
npm install
```

4. Environment:

```bash
copy .env.example .env
php artisan key:generate
```

5. Create database `parbato` (utf8mb4), then:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

6. In another terminal (dev assets):

```bash
npm run dev
```

Open `http://127.0.0.1:8000`.

### Demo accounts

All demo passwords are **`password`**.

**Primary student login (use this):**

| Field | Value |
|-------|-------|
| Email | `student@parbato.test` |
| Password | `password` |

**10 demo students:**

| Name | Email |
|------|-------|
| Alex Rahman | `student@parbato.test` |
| Maya Reyes | `maya@parbato.test` |
| Tariq Nasser | `tariq@parbato.test` |
| Sara Ahmed | `sara@parbato.test` |
| Liam Kelly | `liam@parbato.test` |
| Nadia Chowdhury | `nadia@parbato.test` |
| Omar Hassan | `omar@parbato.test` |
| Priya Sen | `priya@parbato.test` |
| Ethan Brooks | `ethan@parbato.test` |
| Aisha Karim | `aisha@parbato.test` |

| Role | Email | Password |
|------|-------|----------|
| Teacher | `teacher@parbato.test` | `password` |
| Admin | `admin@parbato.test` | `password` |

Live ClassTwin attendance code (seeded session): **`PARBATO1`**

---

## Security notes

- Never commit `.env` or real API keys.
- Firebase variables are optional placeholders in `.env.example` — leave blank until the project owner supplies values.
- Attendance codes are stored hashed server-side.

---

## Repository

GitHub: [https://github.com/Mufrid-johanee/ParbaTo](https://github.com/Mufrid-johanee/ParbaTo)
