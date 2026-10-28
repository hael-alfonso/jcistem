# JCI Carmona PMS v2.1 implementation

## Scope and design

The supplied v2.1 package and the updated complete system design supplied on 27 October 2026 are the functional requirements sources. The later design controls the shared navigation, online/upload submissions, and project workspace. The user's instruction to preserve the existing design takes precedence over starter UI and technology suggestions. Existing Blade layouts, CSS, branding, public pages, dashboard charts, and mobile navigation styling are retained.

The source documents are retained under [specification](specification/). Internal Concept Letters remain separate from external partnership LOIs.

## Implemented behavior

- Four permanent roles remain `admin`, `bod`, `treasurer`, and `member`. Chairmanship is project-specific and requires a recorded BOD approval. Admin alone approves registrations, creates accounts, or changes credentials, role, and account status. BOD can read registration requests and maintain ordinary member profile information.
- Proposals pass Admin compliance review, Treasurer feasibility review (including zero-budget proposals), and the BOD agenda. Admin records the offline board decision with meeting date and signed evidence; approval requires a unique resolution number. Review history retains snapshots. The proposer becomes chair only after approval.
- Concept and PPF submissions can be completed online or uploaded as a finished DOC/DOCX/PDF template. Both methods enter the same review state machine. Uploaded versions are stored privately, linked to the version they replace, timestamped, and included in the audit history; searchable project essentials remain required.
- The chair completes the PPF and locks it before managing execution. Approved budgets cannot be silently changed through the PPF. Tasks support assignment acceptance/decline, progress, dependencies, and completion evidence. All active roles can monitor chapter progress and schedules.
- Chairs can record receipts-backed income/expenses for their own active projects. Treasurer controls the chapter ledger and member dues. Amount validation and budget decisions use integer cents. General members see approved project summaries, their own dues, and authorized reports; detailed project receipts remain limited to the Treasurer and project chair.
- Completion and liquidation reports have independent Admin and Treasurer approvals. The chair cannot approve their own reports. Returning one report does not erase the other's approval. Both final approvals and completed tasks are required for automatic archival; archived operational and financial records are locked.
- The shared sidebar now follows the final design: Dashboard; Projects; Tasks; Financial; Members; Reports & Documents; Calendar; Notifications; Settings; and Profile. Restricted children are generated from server-side permissions. Redundant chairmanship, document, LOI, and report-list sidebar entries were removed; those functions live in the project workspace.
- Every project opens one workspace with Overview, Proposal, Tasks, Schedule, Team, Budget, Documents, and Completion sections. Project-scoped permissions continue to control every management action.
- PDF/DOCX project summary exports include chapter branding and scoped data. Private generated documents have authorized download routes. Admin can upload versioned official templates and update the chapter report name. Report lists use server-side pagination.
- In-app notifications cover approvals and workflow updates. The daily reminder command checks upcoming/overdue open tasks and outstanding personal dues, with an event key preventing duplicate notifications on repeated runs that day. Each member can disable task or dues reminders under Preferences.
- Audit records cover workflow, account, report, and financial changes, with sensitive values redacted. Existing session, login, upload, and backup protections are retained.

## Existing data and chapter decisions

Existing projects and financial records are retained. Older projects without a recorded BOD decision do not receive invented resolutions or automatic chair authority. Their owner or Admin can use **Submit Existing Project for Review**, then complete the current approval process.

The original package contains no binary form templates. The user subsequently supplied four Word reference forms: external partnership letter, internal project letter, project proposal form, and liquidation report. These are bundled unchanged and downloadable by active accounts from Templates & Forms; see [form references](FORM_REFERENCES.md). Admin can still publish additional approved versions. Exported summary documents do not replace signed official PCR/liquidation forms.

A separate authorized Admin must review an Admin chair's PCR; a separate Treasurer must review a Treasurer chair's liquidation. The software does not bypass self-approval restrictions. Reopening archived projects, amending locked budgets/plans, and alternate officer authority require a chapter policy; no bypass is provided. Existing void/audit behavior remains the financial correction mechanism.

## Environment and setup

Verified local versions: PHP 8.2.12, Laravel 12.69.2, MariaDB 10.4.32 through the MySQL driver, Node 24.16.0, npm 11.13.0, Vite 7.3.7, Vitest 5.0.5, Dompdf 3.1.6, and PHPWord 1.4.0. Eloquent, Blade, and existing SVG charts are retained. Laravel 13/PHP 8.3 and a wholesale CSS/chart replacement were not prerequisites for this existing installation.

PHP GD and ZIP were enabled in `C:\xampp\php\php.ini`. The previous configuration is backed up at `C:\xampp\php\php.ini.before-jci-pms-v21`. **Restart XAMPP Apache** so its PHP process loads these extensions. CLI exports have already been verified. Fonts default to Windows Arial; configure `REPORT_FONT_PATH` with a readable Arial TTF when deploying elsewhere. PDF generation uses local assets with remote fetching disabled.

The four additive v2.1 migrations are applied to the configured local database. No real project was advanced or seeded for verification; the real-database smoke test rolled back its fixtures.

For another checkout, install dependencies and apply migrations:

```powershell
composer install
npm.cmd ci
php artisan migrate --force
npm.cmd run build
```

The existing project must already have its normal application key, database, and private storage configuration. Do not generate a replacement key for an existing installation. Keep private storage outside the web root, serve Laravel's `public` directory, and back up the database and private uploads before deployment.

Run Laravel's scheduler locally using `php artisan schedule:work`, or configure the deployment host to invoke `php artisan schedule:run` every minute. Reminders run at 08:00 Asia/Manila; the existing backup command remains scheduled at 02:00 in the application timezone. A continuously running scheduler was not installed as a Windows service. These reminders are in-app; external email/SMS delivery is not part of this implementation.

## Verification

Commands:

```powershell
php artisan test --compact
composer lint
npm.cmd run test:js
npm.cmd run lint
npm.cmd run build
php artisan view:cache
php artisan migrate:status
git diff --check
```

`composer format` provides the existing Pint formatter for changed files; it is optional and can reformat existing code. Automated workflow tests cover all four roles, wrong-order transitions, evidence requirements, self-approval, locked budgets/archive, task acceptance, private receipt/report access, every visible menu, official templates, and real PDF/DOCX output signatures/content. Registration and account authorization tests passed before project workflow work.

A transaction-scoped smoke test against the configured MariaDB database passed the complete concept-to-archive lifecycle; its records and evidence fixture were removed afterward. SQLite feature tests provide repeatable isolated coverage and are not presented as a substitute for that database check.

Final checks: 67 Laravel tests passed (1,477 assertions); 7 Vitest tests and 1 Node workspace test passed; changed PHP files passed syntax checks; JavaScript syntax check, Vite production build, Composer validation, Blade template compilation, migration status, and Git diff whitespace checks passed. The original three v2.1 migrations passed rollback/reapplication in isolated in-memory SQLite, and the new additive submission migration passed the full isolated test suite and was applied to MariaDB. Browser rendering is **unverified**: the in-app browser returned `Browser is not available: iab`. Desktop/mobile appearance, interactive motion, and keyboard behavior still need a browser pass. No deployment or publication was performed.
