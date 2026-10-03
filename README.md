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

The database seeder creates one account for each workspace. Each seeded account uses the local demo password `password`:

| Workspace | Email |
| --- | --- |
| Admin | `admin@jcicarmona.org` |
| Treasurer | `treasurer@jcicarmona.org` |
| Board of Directors | `bod@jcicarmona.org` |
| Member | `member@jcicarmona.org` |

These credentials are for local development only. Change or remove seeded accounts before deploying anywhere accessible to other people.

## Available commands

- `php artisan test` runs the PHPUnit suite.
- `npm run build` builds the production frontend assets.
- `composer run dev` starts the Laravel server, queue worker, log viewer, and Vite development server.

## Current scope

The application includes role protected workspace routes and Blade views for projects, tasks, reports, calendar, member accounts, and Treasurer financial workflows. The pages currently use `App\Support\JciDemoData` for their displayed records. Although database models and migrations exist for the planned records, most workspace pages do not yet read or write those records. Treat the current data and interactions as a demonstration; persistent project workflows, validated record management, document handling, notifications, and audit capture still need implementation before operational use.

Admin financial pages are intended for read only monitoring. Treasurer routes hold the financial recording workflows. Role middleware enforces these workspace boundaries on the server.

## Design notes

See `docs/ADMIN_REBUILD_SPEC.md`, `docs/ADMIN_SYSTEM_V50_CLEAN.md`, and the other files under `docs/` for the feature and interface specifications.
