# Navigation and access policy

## Always-visible primary navigation for authenticated users

- **Dashboard** `/dashboard`: personal assigned tasks, upcoming activities, notifications, chapter summary cards/charts.
- **Projects**
  - My Proposals `/projects/my-proposals`
  - Submit Concept `/projects/new`
  - Chapter Projects `/projects`
  - Review Queue `/projects/reviews` (Admin/Treasurer)
  - BOD Agenda & Decisions `/projects/board` (BOD view, Admin records resolution)
  - My Chairmanships `/projects/chairing` (available to users currently assigned as chair; otherwise explanatory empty state)
- **Tasks**
  - My Tasks `/tasks/my`
  - Chapter Task Monitor `/tasks` (all four roles, read-only chapter-wide)
  - Project Task Manager `/projects/{project}/tasks` (chair for own project)
- **Calendar** `/calendar`: chapter events, project milestones, assigned task deadlines.
- **Financial**
  - Chapter Project Budgets `/finance/budgets` (all four, read-only overview)
  - My Membership Dues `/finance/my-dues` (all four, own records)
  - Project Financials `/projects/{project}/finance` (chapter budget summary to all, detailed entry to own chair/Treasurer)
  - Chapter Master Ledger `/finance/ledger` (Treasurer only)
  - Manage Member Dues `/finance/dues` (Treasurer only)
  - Liquidation Reviews `/finance/liquidations` (Treasurer only)
- **Members / Directory**
  - Member Directory `/members` (all four)
  - Manage Member Records `/members/manage` (Admin/BOD)
  - Registration Requests `/members/registrations` (Admin/BOD view; Admin decision buttons)
  - User Accounts & Roles `/admin/accounts` (Admin only)
- **Documents & Reports**
  - Templates & Forms `/documents/templates` (all)
  - Generate Reports `/reports` (all; authorization enforced per dataset)
  - My Project Reports `/reports/my-projects` (project chairs)
  - Completion Report Reviews `/reports/pcr-review` (Admin only)
  - Project Archive `/archive` (all, filtered)
- **Notifications** `/notifications` (all)
- **Profile** `/profile` (all); change password, preferences
- **Settings** `/admin/settings` (Admin only); configuration, authorized document templates, audit log
- **Log Out** session action

Use collapsible grouped sidebar menus on desktop and accessible mobile drawer. Do not show privileged actions to unauthorized users, but server checks are mandatory regardless.

## Capability matrix

Legend: `R` read, `W` create/edit permitted, `A` approve/authorize, `C` chair-of-that-project only, `—` no access.

| Capability | Admin | Treasurer | BOD | Member |
|---|---|---|---|---|
| Sign up and own profile | R/W | R/W | R/W | R/W |
| View member directory | R | R | R | R |
| Manage ordinary member records | W | — | W | — |
| View pending sign-ups | R | — | R | — |
| Approve/reject sign-ups | A | — | — | — |
| Assign permanent roles / activate accounts | A | — | — | — |
| Submit/revise own concept | W | W | W | W |
| Admin proposal review | A | — | — | — |
| Treasurer financial review | — | A | — | — |
| View BOD agenda and deliberation summary | R | R | R | R (approved/public summary only) |
| Make BOD decision offline | Member of Board | Member of Board if seated | A | — |
| Record authorized BOD decision/resolution | W | — | — | — |
| View project tasks, progress, schedule | R | R | R | R |
| Chair: create tasks, schedules, PPF | C | C | C | C |
| Respond to own task assignment | W | W | W | W |
| View chapter budget totals/expense summaries | R | R | R | R |
| Chair: enter project transactions / liquidation draft | C | C | C | C |
| Manage dues/master ledger | — | W | — | — |
| Approve PCR | A | — | — | — |
| Approve liquidation | — | A | — | — |
| Generate permitted/downloadable reports | R | R | R | R |
| View authorized project archive | R | R | R | R |
| Chapter security settings/audit logs | A | — | — | — |

## Permission algorithm (must live on server)

A permission is granted only if all are true:
1. account is `ACTIVE` and session is valid;
2. the requested action is permitted by permanent role **or** relevant chair/assignee relationship;
3. the requested project/document/record is in an allowed state;
4. the user is not approving their own submission, transaction, PCR or liquidation;
5. project-specific visibility and privacy filters are satisfied.

`isChair(userId, projectId) = project.chairUserId === userId && project.status >= APPROVED_BY_BOD && chair assignment active` (implement states explicitly, not string order comparisons). `isTaskAssignee` checks membership in `TaskAssignment`. Viewing global task/budget summaries is not equivalent to editing.

**Security rule:** no user receives chapter-global write access merely by being chair of one project. Deny by default, enforce on every server action/API request, validate object ownership.
