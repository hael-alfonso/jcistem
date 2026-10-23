# JCISTEM security model

JCISTEM uses the CIA triad as operational goals and layered authorization as its access-control model. A role grants broad access to an area, a record relationship limits access to a specific project or member, and the workflow state limits which action is valid at that moment. Navigation visibility is only a user-interface aid; the server checks every protected request.

## Permission matrix

| Capability | Admin | BOD | Treasurer | General member |
| --- | --- | --- | --- | --- |
| View chapter projects permitted by project visibility | Yes | Yes | Yes | Yes |
| Create a project concept | Yes | Yes | Yes | Yes |
| Review a concept | Only when assigned as concept reviewer | Only when assigned as concept reviewer | No | No |
| Approve a formal proposal | Only when assigned as proposal reviewer | Only when assigned as proposal reviewer | No | No |
| Review a proposed budget | No | No | Yes | No |
| Manage allocations, ledger, liquidation, dues and finance export | No | No | Yes | No |
| Manage member accounts and roles | Yes | No | No | No |
| Review and manage public contact inquiries | Yes | No | No | No |
| Review submitted chapter documents | Yes | No | No | No |
| Complete or archive a project | Yes, at the correct workflow stage | No | No | No |
| Manage an approved project | Only if separately assigned as Chair, except Admin-only workflow actions | Only if separately assigned as Chair | Only if separately assigned as Chair | Only if separately assigned as Chair |
| View audit history | Yes | No | No | No |

`App\Support\Permissions` is the central broad-permission registry. `permission:` route middleware blocks protected areas before controller execution. Controllers and `ChapterService` retain record-level, assignment-level and workflow-level checks for defense in depth. Admin is deliberately not a finance superuser; Treasurer authority remains separate.

## CIA controls

### Confidentiality

- Authentication, active-account checks and permission middleware protect private pages.
- Project and document access is checked against role, ownership, Chair assignment and project visibility.
- Uploads remain on Laravel's private local disk and are returned only through authorized controllers.
- Authenticated and recovery responses use `Cache-Control: no-store, private`.
- Responses set clickjacking, MIME-sniffing, referrer and browser-feature restrictions. HSTS is sent only over HTTPS.
- Passwords are hashed; login and recovery attempts are rate limited.
- Public inquiries are visible only to Admin, store a keyed hash instead of the sender's raw IP address, and keep internal handling notes out of public responses.

### Integrity

- Project transitions validate both reviewer assignment and workflow state.
- Financial changes remain Treasurer-only and use database transactions and row locks.
- Posted ledger entries are voided instead of silently deleted, and finance actions are audited.
- Project approvals, reports and letters retain review or version history.
- Authorization denials are written to the application log without request payloads or personal data.
- Inquiry status and assignment changes are audited without copying the sender's message or contact details into audit history.

### Availability

- Laravel's `/up` endpoint checks application boot, database connectivity and private-storage writability. Monitor it from outside the server.
- `php artisan chapter:backup` snapshots the database and private uploads into a directory outside the web root, signs a SHA-256 manifest using `APP_KEY`, and verifies the finished copy. `php artisan chapter:backup --verify=PATH` checks a stored copy for missing or changed files.
- The Laravel scheduler runs the backup daily at 02:00 server time. The host must actually run `php artisan schedule:run` every minute; without that operating-system task, no automatic backup occurs.
- Production must run with `APP_DEBUG=false`, HTTPS, monitored storage capacity and database alerts.
- Backup contents are **not encrypted by this command**. Set `CHAPTER_BACKUP_PATH` to a restricted, encrypted volume and copy completed backups to encrypted off-server storage. Never expose this path under `public/` or the web server document root. Keep `APP_KEY` separately in secure recovery storage; it is required to verify manifests and restore encrypted application data.
- Test restoration regularly in an isolated environment; a backup is not considered usable until restoration succeeds.
- Document the recovery-time target, recovery-point target and the people authorized to perform restoration.

### Backup and restore runbook

1. Set `CHAPTER_BACKUP_PATH` to a private, encrypted directory outside the application and web root, with space for multiple complete backups (for XAMPP, for example, `C:\xampp\jcistem-backups`). Backups fail closed until this is configured. If `mysqldump` is not on `PATH`, set `CHAPTER_MYSQLDUMP_PATH` to its executable. Give only the application and backup operators access.
2. Run `php artisan chapter:backup`. Confirm it reports `Backup created and verified`. Copy that entire dated directory to encrypted off-server storage. Alert an operator if the command exits nonzero or if no new completed directory appears within 26 hours.
3. On Windows/XAMPP, create a Task Scheduler job that runs `C:\xampp\php\php.exe` with arguments `C:\xampp\htdocs\jci_system\artisan schedule:run` every minute, with the project directory as its working directory. On other hosts, configure the equivalent cron or scheduler service.
4. For a recovery drill, copy one backup to an isolated machine with the same application version and `APP_KEY`. Run `php artisan chapter:backup --verify=PATH` before restoration. Restore `database.sql` with the matching MySQL client (or replace the SQLite database with `database.sqlite` while the application is stopped), then copy the backup's `private/` directory into `storage/app/private/`.
5. Run migrations only after matching the backed-up application version, start the isolated app, and verify login, project records, and private document downloads. Record the time and outcome of the drill. Never restore over the live database without a separate pre-restore backup and an approved recovery window.

## Required deployment checks

1. Run the focused authorization and workflow tests before deployment.
2. Confirm `APP_ENV=production`, `APP_DEBUG=false`, a unique application key, secure database credentials and HTTPS-only access.
3. Configure the scheduler or hosting backup service for database and private-file backups; do not store backup archives inside the public web root.
4. Monitor `/up`, HTTP 5xx responses, failed login volume, authorization denials, database capacity and backup completion.
5. Perform and record a restoration exercise after the first production backup and after material schema or storage changes.
