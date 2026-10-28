# Confirmed requirements versus recommendations

## Confirmed by user
- Four permanent roles: Admin, Local Treasurer, BOD, General Member; all can be project chair if proposal is approved.
- Only Admin approves sign-up registrations; Admin and BOD can manage registration/member records.
- All users can monitor project tasks, progress, schedules, project budget and expense summaries.
- All users can generate authorized project/financial reports.
- Admin reviews proposal requirements and approves PCR; Treasurer reviews financial feasibility, dues and liquidation; BOD decides project approval at meeting, Admin records resolution.
- Main modules: members, projects, tasks, finance, notifications, reports/archive; calendar and dashboard support navigation.
- Preferred stack: HTML, CSS, JS, Tailwind, Bootstrap, Laravel/PHP, MySQL, XAMPP; requested consideration of Recharts, Prisma, Vitest.

## Technical decisions made for coherence
- Blade + vanilla JavaScript + Chart.js instead of React/Recharts; Eloquent instead of Prisma; keep Vitest for JS, PHP testing in Laravel.
- Tailwind primary CSS, Bootstrap JS used selectively to avoid dual styling conflicts.
- Laravel 13 + PHP 8.3+ preferred if environment supports it; fall back only to still-supported PHP/Laravel combination based on actual installed versions.
- Database sessions initially; email/SMS reminders optional follow-up.

## Chapter policy decisions to confirm before live use
- Who can act as alternate PCR/liquidation approver if the chair is Admin or Treasurer (self-approval never allowed).
- Can BOD member decisions be input directly or only recorded by Admin based on signed minutes? Current design: decision at meeting, Admin records.
- Who can edit an archived project or reopen an approved PPF, and how corrections are authorized.
- Whether public-to-members financial views show transaction-level detail or only approved summaries. Default: summaries to all, detailed records to Treasurer and current project chair.
- Document template versioning, official signature authorities, calendar event privacy, notification channels, backup/retention.
