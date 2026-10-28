# Tests and acceptance criteria (Laravel + Vitest)

### Laravel Feature / Unit tests
- Registration pending blocks login; BOD can view/edit member information but cannot approve/reject signup; Admin can approve; rejected signup cannot login.
- Any of four roles submits proposal; Admin and Treasurer review in order; BOD records decision; Admin records official resolution; only after approval submitter has chair permission for that project.
- Any user reads chapter task progress, schedules and budget/expense **summaries**; cannot edit another chair's project or see other member dues.
- Task assignee accepts/declines and updates assigned task; no user can update an unassigned task.
- Treasurer manages dues and ledger; non-Treasurer write attempts return 403.
- Admin alone approves PCR; Treasurer alone approves liquidation; no self-approval; archive only after both approvals.
- Report generator available to all with proper scoped data; private receipts and reports have authorized downloads.
- Audit log records changes and no passwords/tokens.

### Vitest tests (JavaScript)
- Currency and date formatting; chart dataset labels and aggregation formatting; task progress helpers; frontend form helpers.
- No Vitest tests for Laravel database access: that belongs in Laravel feature/integration tests.

### User acceptance flows
1. Applicant signs up -> BOD sees pending -> Admin approves -> new user logs in.
2. Member proposes -> revision -> correction -> Treasurer review -> BOD approves -> Admin records resolution -> chair manages project.
3. Chair delegates -> member accepts -> completes -> all users see dashboard progress.
4. Chair records expense with receipt -> members see budget summary -> Treasurer sees detailed record.
5. Chair submits PCR + liquidation -> independent approvals -> archive -> all authorized users generate report.

### Commands
`php artisan test`; `npm run test:js`; `npm run build`; `php artisan migrate:status`. Add lint/formatter scripts to `composer.json` and `package.json` and record exact installed versions.
