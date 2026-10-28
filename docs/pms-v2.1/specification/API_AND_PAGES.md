# Laravel Blade pages and controller actions

The routes are suggested names; use named Laravel web routes and `route()` helpers consistently. All HTTP mutations require CSRF protection, validation and server-side policy checks.

| Navigation | GET page / related actions | Who |
|---|---|---|
| Dashboard | `/dashboard`: summary cards, overdue tasks, charts, notices | all |
| Projects / My Proposals | `/projects/my-proposals`: drafts, submitted and revisions | all, own |
| Submit Concept | `/projects/create` GET; `/projects` POST | all active |
| Chapter Projects | `/projects`: status, progress, budgets (filtered); `/projects/{project}` | all |
| Project Reviews | `/projects/reviews`: admin & treasury review queues | Admin/Treasurer scoped |
| BOD Agenda | `/projects/board`: view agenda, record official decisions | BOD view; Admin record |
| My Chairmanships | `/projects/chairing`: chaired projects | chairs, otherwise empty state |
| My Tasks | `/tasks/my`: accept/decline, update own progress | all |
| Chapter Task Monitor | `/tasks`: read-only task/project status | all |
| Project Task Manager | `/projects/{project}/tasks`: create/assign/manage | chair for project |
| Calendar | `/calendar`: project activities, deadlines | all |
| Chapter Budgets | `/finance/budgets`: budget vs expenses graphs/tables | all read |
| My Dues | `/finance/my-dues`: own amount and payments | all read own |
| Project Finances | `/projects/{project}/finance`: summary all; detailed chair/Treasurer | scope-based |
| Master Ledger | `/finance/ledger`: authorized ledger | Treasurer |
| Manage Member Dues | `/finance/dues`: create, record payment, reminder | Treasurer |
| Liquidation Reviews | `/finance/liquidations`: review approvals | Treasurer |
| Member Directory | `/members`: safe profile fields | all |
| Manage Members | `/members/manage`: edit non-approval information | Admin/BOD |
| Registration Requests | `/members/registrations`: pending view and decision | Admin/BOD view; Admin decide |
| User Accounts | `/admin/accounts`: roles, activation, disable | Admin |
| Documents / Templates | `/documents/templates`: download approved JCI forms | all |
| Report Generator | `/reports`: authorized filtered export PDF/DOCX | all |
| My Project Reports | `/reports/my-projects`: PCR, liquidation draft/submit | designated chair |
| PCR Reviews | `/reports/pcr-review`: review and return/approve | Admin |
| Archive | `/archive`: approved completed projects | all (filtered) |
| Notifications | `/notifications`: read and mark read | own only |
| Profile | `/profile`: own details and password | own only |
| Settings & Audit | `/admin/settings`, `/admin/audit-logs` | Admin |

### Controller/service guidance
- Create conventional controllers (`ProjectController`, `ProposalReviewController`, `TaskController`, `TransactionController`, `ReportController`) and Form Request classes for each mutation.
- Use dedicated service methods `submitConcept`, `requestRevision`, `passAdminReview`, `passTreasurerReview`, `recordBodResolution`, `lockPpf`, `assignTask`, `recordProjectTransaction`, `submitPcr`, `approvePcr`, `approveLiquidation`, `archiveProject`.
- Reject unauthorized record access with 403; distinguish not found with 404. Enforce policy for export generation and file download.
- Financial report generator must filter sensitive dues/transaction details; chapter totals can be broader.
- UI search, filters and pagination should all query MySQL rather than fake client-only arrays.
