# JCI Carmona Project Management System

A Laravel 12 application for JCI Carmona project management, with separate Admin, Treasurer, Board of Directors, and Member workspaces.

## Requirements

- PHP 8.2 or later with the extensions required by Laravel 12
- Composer
- Node.js and npm
- SQLite for the default local setup, or another database supported by Laravel

## Local setup

1. Install PHP dependencies with `composer install`.
2. Copy `.env.example` to `.env` if one does not already exist, then set the database connection and credentials.
3. Run `php artisan key:generate`.
4. Run `php artisan migrate --seed` to create the schema and local demo accounts.
5. Run `npm install` and `npm run build` to build frontend assets.
6. Start the application with `php artisan serve` and open the URL it prints.

The default configuration uses SQLite. Set `DB_CONNECTION=sqlite` and provide a writable database path in `.env` (for example, `database/database.sqlite`) before migrating if the file does not exist.

Password recovery uses email reset links. The default `MAIL_MAILER=log` writes messages to the application log for local development; it does not deliver email. Configure an SMTP mailer and a chapter-approved `MAIL_FROM_ADDRESS` in `.env` before using password recovery with real accounts.

## Local demo accounts

The database seeder creates one account for each workspace. Seeded accounts use `password123` unless `JCI_BOOTSTRAP_PASSWORD` is set before seeding. Rerunning the local seeder restores these accounts as active and resets them to that configured password so the documented credentials remain usable.

| Workspace | Username | Email |
| --- | --- | --- |
| Admin | `chapter-admin` | `jci.admin@gmail.com` |
| Treasurer | `chapter-treasurer` | `jci.treasurer@gmail.com` |
| Board of Directors | `board-reviewer` | `jci.bod@gmail.com` |
| Member | `chapter-member` | `jci.member@gmail.com` |

These credentials are for local development only. Change or remove seeded accounts before deploying anywhere accessible to other people.

## Available commands

- `php artisan test` runs the PHPUnit suite.
- `npm run build` builds the production frontend assets.
- `composer run dev` starts the Laravel server, queue worker, log viewer, Vite development server, and scheduler.
- `php artisan schedule:run` runs due scheduled jobs. Configure a system cron or Windows Task Scheduler entry to call this every minute in a deployed environment.
- `php artisan members:remove-inactive` runs the member retention job manually.

## Current scope

The application includes role-protected, database-backed workspaces for projects, tasks, reports, calendar, member accounts, and Treasurer financial workflows. A project moves from concept review through proposal, budget verification, Board decision, delivery, completion review, and the completed-project library. A Treasurer cash advance becomes a posted ledger debit only after the Chair confirms receipt; receipt-backed reimbursement claims follow a separate Treasurer review.

Project concepts and full proposals can be saved as incomplete drafts; the required details and official upload are checked when they are submitted for review. A project with no planned expenses can record a zero budget without a placeholder expense. After project details change, an uploaded submission must be updated before it can be sent to reviewers.

Admin can remove member accounts and closed projects from active lists and restore them from the removed lists. Removal is a soft delete so project history, ledger entries, and audit records remain intact. General-member accounts marked inactive for a full year are moved to Removed Accounts automatically by the daily scheduled `members:remove-inactive` command. This does not apply to Admin, Treasurer, or BOD accounts. Members chairing an active project are held until that assignment is resolved. For inactive accounts that predate the tracking migration, the year starts when the migration is applied because the original deactivation date is unknown.

Admin financial pages are intended for read only monitoring. Treasurer routes hold the financial recording workflows. Role middleware enforces these workspace boundaries on the server.

## Design notes

See `docs/ADMIN_REBUILD_SPEC.md`, `docs/ADMIN_SYSTEM_V50_CLEAN.md`, and the other files under `docs/` for the feature and interface specifications.
