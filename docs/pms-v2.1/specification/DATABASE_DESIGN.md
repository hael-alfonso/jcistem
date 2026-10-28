# MySQL database design — Eloquent models and tables

All tables use `id` primary keys, created_at/updated_at unless intentionally immutable. Prefer indexed unsigned big integer foreign keys and InnoDB. Use database constraints where supported; mirror status rules in PHP services and policies.

| Table | Important fields | Relationships and constraints |
|---|---|---|
| users | name, email unique, password hash, role enum/string, status pending/active/rejected/disabled, approved_by nullable | admin approval required before login |
| member_profiles | user_id unique, phone, chapter metadata | 1:1 user, restrict public exposure |
| registration_reviews | applicant_id, reviewer_id, decision, comments, decided_at | immutable Admin decisions |
| projects | title, proposer_id, chair_user_id nullable, status, version, proposed_start/end, approved_at | user can chair multiple projects; only approved can assign chair |
| concept_proposals | project_id, rationale, objectives, beneficiary data, planned_budget | revisions versioned |
| proposal_reviews | project_id, stage admin/treasurer, reviewer_id, outcome, comments, reviewed_at | review history, never overwrite |
| bod_decisions | project_id, meeting_date, decision, resolution_number nullable, recorded_by, evidence_path | Admin records official decision; unique valid resolution if present |
| project_plans | project_id, PPF structured fields, finalized_at, locked_at | chair only, only after authorization |
| project_events | project_id, title, start/end, venue, visibility | calendar view |
| project_tasks | project_id, title, description, priority, status, due_at, completed_at | chair creates, general chapter read summary |
| task_assignments | task_id, user_id, response, progress_note, updated_at | assignee response/update; unique active assignment rules |
| project_budgets | project_id, category, approved_amount decimal(15,2) | versioned budget; status controls |
| financial_transactions | project_id, type income/expense, amount decimal(15,2), category, occurred_on, entered_by | protected details; ledger-linked |
| transaction_attachments | transaction_id, storage_path, mime_type, uploaded_by | private receipt files |
| member_dues | user_id, period, amount_due decimal(15,2), due_on, status | Treasurer write, owner read |
| due_payments | due_id, amount decimal(15,2), paid_on, reference, recorded_by | avoid duplicate reference |
| project_reports | project_id, type PCR/liquidation, content_json, status, submitted_by, submitted_at, version | only chair edits/submits |
| report_reviews | report_id, reviewer_id, outcome, comments, decided_at | Admin PCR, Treasurer liquidation, forbid self-approval |
| generated_reports | type, scope_key, generated_by, storage_path, generated_at | permission-checked download |
| notifications | user_id, type, title, data JSON, read_at | own inbox only |
| audit_logs | actor_id, action, entity_type, entity_id, before_json, after_json, created_at | append only, no secrets |

### Critical constraints
- Never expose all dues/receipts to every user merely because project budget summaries are public inside the chapter.
- Store monetary values with `DECIMAL(15,2)` and validated arithmetic; specify PHP currency.
- State transitions + review rows + audit rows must commit atomically in Laravel `DB::transaction`.
- Archived project edits forbidden; corrections require documented reopen policy and audit event.
- Store attachments under `storage/app/private`, not `public/`.
