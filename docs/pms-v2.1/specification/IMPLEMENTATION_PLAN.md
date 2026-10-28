# Phase-by-phase Laravel implementation plan

## Phase 0 — Foundation
Verify PHP/XAMPP MySQL compatibility; Laravel install, Vite, Tailwind, limited Bootstrap JS, Chart.js, Vitest, MySQL config. Scaffold models, migrations, app layout, grouped sidebar, empty states for all real menu routes; set initial policies and database-backed seed examples. Verify migration, build, basic tests.

## Phase 1 — Authentication and accounts
Signup/login/logout, pending-approval enforcement, role dashboards, Admin signup decisions, Admin/BOD member management (BOD cannot approve signup), role assignment, profiles, directory. Test approval and bypass prevention.

## Phase 2 — Proposals and official authorization
Concept form, versioned review notes, Admin checks, Treasurer feasibility review, BOD agenda/decision record, resolution number, project-scoped chair assignment, complete PPF, lock plan. Test wrong-order and unauthorized actions.

## Phase 3 — Tasks, schedules, calendar and monitoring
Project chairs manage their project tasks, assignees accept/decline/update, all users monitor chapter tasks, project schedule and progress; charts and filtering. Test chair scope and completion rules.

## Phase 4 — Finances and dues
Approved budgets, income/expense transactions, protected receipts, financial summary and warnings, Treasurer-only member dues/ledger, personal dues page for all, chapter budget read for all. Decimal validation, no overbroad records.

## Phase 5 — Notifications and reports
Database notifications for updates, report templates PDF/DOCX with JCI logo and Arial 11, all-user authorized exports, chair-only PCR/liquidation drafts/submissions, Admin/Treasurer approvals, no self-approval, archive lock.

## Phase 6 — Audit, security, polish, deployment
Audit trail, login and upload protections, responsive/mobile UX, keyboard accessibility, pagination, actual graphs, queue support, tests, backups, developer docs, local XAMPP configuration and safe production notes.

## Done criteria for EACH phase
- Real usable pages linked from the sidebar, no dead controls or fabricated chart counts.
- MySQL persistence and valid migration/rollback path.
- Laravel policies enforce role and project scope on server.
- `php artisan test`, `npm run build`, `npm run test:js` passing or documented real blocker.
- Verify sample project flows with active Admin, Treasurer, BOD, Member accounts.
