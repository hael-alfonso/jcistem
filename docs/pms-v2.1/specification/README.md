# JCI Carmona PMS — Laravel Development Blueprint (v2.0)

**Purpose:** Build a real, responsive, maintainable JCI Carmona Project Management System in Codex, with working pages, MySQL persistence, authorization, and tests. This package is an implementation plan, **not** a finished application.

## Technology choices
- **Frontend:** HTML5, CSS3, JavaScript (ES modules), Laravel Blade, Tailwind CSS as the primary design system, Bootstrap 5 **JavaScript components only when needed**, Vite, Chart.js (responsive dashboard graphs).
- **Backend:** PHP 8.3+ (compatible with selected Laravel release), Laravel 13 preferred when your local PHP/extensions meet its requirements, Laravel Eloquent ORM, Form Requests, Policies, Gates, services, jobs, notifications, migrations.
- **Database & local runtime:** MySQL 8.x; XAMPP Apache/MySQL/PHP for Windows local development, Composer, Node.js and npm. Confirm PHP CLI and Apache use compatible PHP versions and extensions.
- **Testing:** Laravel PHPUnit or Pest for PHP feature/unit tests, Vitest for independent JavaScript modules, Playwright optional for browser tests.
- **Not part of this architecture:** Recharts (requires React) and Prisma (JS/TS ORM). Use Chart.js and Eloquent respectively. Do not install React just to use Recharts.

**Compatibility:** Choose a supported Laravel version matched to installed PHP. The documentation uses Laravel 13 / PHP 8.3+ as a target, not a promise every XAMPP bundle is compatible. Verify requirements before creating the app.

## Start in Codex
1. Put `AGENTS.md` at repository root, put all remaining blueprint files in `docs/`.
2. Review `DECISIONS_AND_ASSUMPTIONS.md`, `WORKFLOWS.md`, `RBAC_AND_MENUS.md`, `DATABASE_DESIGN.md`, `ARCHITECTURE.md`, `API_AND_PAGES.md` and `TEST_PLAN.md`.
3. Paste `CODEX_MASTER_PROMPT.md` into Codex. Begin at **Phase 0**, test it, then implement phases in order.
4. Place the user-provided official JCI logo in `public/images/branding/jci-logo.png`; ensure permission to use it. Exports must use **Arial 11** and JCI Carmona branding.

## Local Windows setup (XAMPP)
1. Start MySQL in XAMPP. Apache is optional if using `php artisan serve`, but useful for deployment-like local testing.
2. Verify `php -v`, `composer --version`, `node -v`, `npm -v`; ensure `php -m` has required extensions. **Do not assume bundled XAMPP PHP meets Laravel requirements.**
3. Run `composer create-project laravel/laravel jci-carmona-pms` and enter folder.
4. Create an empty MySQL database `jci_carmona_pms`; copy `.env.example` to `.env` (Laravel creates one on install) and set `DB_CONNECTION=mysql`, host, port, database, username, password. Never publish `.env`.
5. Run `php artisan key:generate`, `php artisan migrate`, `npm install`, `npm run dev`, and `php artisan serve`. In a second terminal run queue worker (`php artisan queue:work`) if the chosen notifications implementation uses queues.
6. Use Laravel's `public/` directory as the web root. **Never point Apache DocumentRoot to the Laravel project root**, which contains `.env` and source files.

## Fixed functional scope
Four permanent roles: `ADMIN`, `TREASURER`, `BOD`, `MEMBER`. A project's chair is **not** a permanent fifth role. All four can submit proposals, become chair after official BOD approval, monitor chapter tasks and budget summaries, and generate authorized reports. Admin+BOD manage member records, but Admin alone approves/rejects sign-ups and assigns user roles.

Main modules: (1) Members & Accounts; (2) Project Proposals & Approval; (3) Tasks & Schedule; (4) Finance & Dues; (5) Notifications; (6) Reports & Archive. Dashboard, Calendar, Profile, Audit Log, Settings are supporting pages.

## Definition of done
Every visible menu goes to a working page with database-backed data and correct authorization. No decorative buttons, hard-coded performance numbers, bypassable approval rules, or placeholder report exports. All phase-specific tests pass.
