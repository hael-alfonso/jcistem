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

- Laravel's `/up` endpoint provides an application health check.
- Production must run with `APP_DEBUG=false`, HTTPS, monitored storage capacity and database alerts.
- Back up the database and all private upload directories together. Keep at least one encrypted off-server copy.
- Test restoration regularly in an isolated environment; a backup is not considered usable until restoration succeeds.
- Document the recovery-time target, recovery-point target and the people authorized to perform restoration.

## Required deployment checks

1. Run the focused authorization and workflow tests before deployment.
2. Confirm `APP_ENV=production`, `APP_DEBUG=false`, a unique application key, secure database credentials and HTTPS-only access.
3. Configure the scheduler or hosting backup service for database and private-file backups; do not store backup archives inside the public web root.
4. Monitor `/up`, HTTP 5xx responses, failed login volume, authorization denials, database capacity and backup completion.
5. Perform and record a restoration exercise after the first production backup and after material schema or storage changes.
