# CODEX MASTER PROMPT — JCI CARMONA PMS (LARAVEL)

Act as the lead Laravel/PHP engineer and system analyst for the JCI Carmona PMS. Build a **working** responsive web application in this repository. Read all specifications in `/docs` and root `AGENTS.md` first. Work one phase at a time and verify every phase using automated tests and real database persistence.

## Exact technology requirements
- PHP + Laravel (prefer Laravel 13 only if local PHP >=8.3 and dependencies compatible; otherwise choose the newest supported compatible release with documented reason)
- MySQL using migrations and **Eloquent ORM**; local development uses XAMPP MySQL + Composer/Node/Vite
- HTML5, Blade templates, CSS, JavaScript, Tailwind CSS (primary), selective Bootstrap 5 JS components, Chart.js for dashboards
- Laravel PHPUnit/Pest (backend); Vitest (pure JS); optionally Playwright (end-to-end)
- Do **not** add Next.js, PostgreSQL, Prisma or Recharts; Recharts would require React and Prisma is not the PHP data access layer.

## Four permanent users and authorization
`ADMIN`, `TREASURER`, `BOD`, `MEMBER`. Do not create a `PROJECT_CHAIR` global role. Any active user can submit a proposal; after an official approved BOD decision recorded by Admin, the submitter is assigned chair on **that project only**. Admin and BOD manage member information; Admin alone approves/rejects registrations and changes account roles. All users can read chapter task status, schedules, progress and approved budget/expense summaries; all can generate **authorized** reports. A chair creates and manages their own project's tasks, PPF, transactions and report drafts. Treasurer manages dues/ledger and approves liquidation; Admin approves PCR. Block self-approval and privilege escalation.

## System flow
Sign up -> Admin approval -> Login -> Draft concept -> Admin compliance review -> Treasurer feasibility review -> BOD agenda/offline decision -> Admin records resolution -> project-scoped chair designation -> full PPF locked -> launch -> task acceptance and progress, budget/expense entries -> PCR and liquidation -> Admin PCR approval + Treasurer liquidation approval -> archive. Revision and rejection paths must be explicit and auditable.

## Navigation mandate
Build ALL pages in `RBAC_AND_MENUS.md` and detailed routes in `API_AND_PAGES.md`. Keep working link destinations, real forms and permission-aware menu presentation. Unimplemented routes during a phase may show an honest 'not yet implemented' screen but may NOT pretend to perform an action; complete them by final phase.

## Database and coding standards
Follow `DATABASE_DESIGN.md`, `WORKFLOWS.md`, `TEST_PLAN.md`, and `DECISIONS_AND_ASSUMPTIONS.md`. Use Laravel migrations, foreign keys, validation requests, policies, scoped queries, services, `DB::transaction`, CSRF protection, secure private attachments, notification records and audit logs. Use integer cents or validated DECIMAL for money, never float. All BOD resolutions, decisions and report approvals must be authenticated and recorded. Seed sample data as **DEMO** only.

## Phase 0 — do this first, then STOP
1. Inspect existing repository; preserve correct files rather than overwriting them blindly.
2. Verify PHP, extensions, Composer, MySQL and Node compatibility; document versions and setup commands for XAMPP. Scaffold the Laravel project and install Tailwind/Vite, Chart.js and Vitest, Laravel test tooling.
3. Create initial Eloquent models/migrations, role/authorization policies, common application layout and ALL planned navigation links. Give unfinished routes transparent empty states.
4. Implement secure application shell, basic audit infrastructure, migrations/seeders and foundational unit tests.
5. Run `php artisan migrate`, `php artisan test`, `npm run build`, `npm run test:js`, and lint where configured; fix errors.
6. Explain changed files, commands/tests, any environment blocker and exactly what remains in Phase 1. Do not claim completion without passing verification.

After Phase 0, implement each subsequent phase from `IMPLEMENTATION_PLAN.md` when instructed. When users share official forms/logo, integrate those assets and Arial 11 report formatting.
