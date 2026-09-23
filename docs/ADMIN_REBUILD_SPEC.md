# JCI Carmona Admin System — Master Rebuild v41

This build is based on the uploaded **JCI Carmona Online Project Management System — Final Master Functionality, Navigation & Workflow Document**.

## Admin scope mapped to the master blueprint
- Dashboard
- Projects: All Projects, Create Project, My Projects, Pending Review, Approved, Ongoing, Completed, Archived
- Tasks & Milestones
- Letters of Intent
- Calendar
- Project Reports
- Financial Monitoring: overview, Budget Allocation, Fund Utilization, Expense Monitoring, Financial Reports
- Members & Accounts
- Member Registration
- My Member Dues
- Notifications
- My Account & Settings
- Audit Log (administrative traceability support retained from the supplied Admin Functionality notes)

## Permission boundaries implemented in the UI
- All registered users may create project proposals.
- Any registered user may be assigned Project Chair after approval.
- Project Chair is project-specific, not a permanent role.
- All authenticated users may monitor projects and project-level funds/budgets/expenses.
- Admin can review projects and monitor submitted reports.
- Admin financial pages are monitoring/read-only; Treasurer-only financial-recording controls are not exposed in Admin navigation.
- Treasurer Ledger, transaction recording, disbursement recording, liquidation maintenance, Overall Financial Report preparation, and official member-dues recording remain outside the Admin workspace.
- Admin My Member Dues is personal self-service only.
- Project-scoped Chair task management is shown as conditional when the Admin is actually assigned as Chair.
- Critical records use archive/soft-delete/void concepts rather than destructive delete.
- Audit history is retained for workflow and financial traceability.

## Forms represented in the build
- Member Registration Form
- User Account structure (under Members & Accounts)
- Project Creation Form
- LOI linkage and fields
- Task fields
- Budget Allocation monitoring fields
- Expense monitoring fields
- Project Completion / PCR reporting concept
- Treasurer Ledger and Treasurer Member Dues are documented as separate authority areas, not Admin-editable modules.

## LOI automation represented
Project-linked LOI views display project reference, project title and project description from the source project record, together with LOI-specific partner, recipient, purpose, version and status fields.

## Financial formulas represented
- Remaining Funds = Approved Allocation − Actual Expenses
- Expense Utilization % = Actual Expenses ÷ Approved Allocation × 100
- Unallocated Amount = Target / Proposed Budget − Approved Allocation
- Production implementation requires server-side validation.

## Production note
This package is a front-end prototype. Authentication, API authorization, database persistence, file storage, notification delivery, server-side validation, and persistent audit storage must be implemented on the backend before production deployment.
