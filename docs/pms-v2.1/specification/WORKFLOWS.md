# Workflows, decisions, and valid transitions

Every standalone flow has one START, one or more clearly routed decisions, and an END. "End" of a module action returns to the UI; it does not mean logout.

## 1. Overall access

```mermaid
flowchart TD
 A([START]) --> B[Open site] --> C{Has active account?}
 C -- No --> D[Sign up / pending Admin review] --> E{Admin approved?}
 E -- No --> X([END: pending or rejected])
 E -- Yes --> F[Log in]
 C -- Yes --> F
 F --> G{Credentials and account valid?}
 G -- No --> H[Show error / retry or exit] --> I{Retry?}
 I -- Yes --> F
 I -- No --> Z([END])
 G -- Yes --> J[Role-filtered dashboard] --> K[Choose allowed module]
 K --> L[Complete module action] --> M{Continue?}
 M -- Yes --> J
 M -- No --> N[Log out] --> Z
```

## 2. Member registration

```mermaid
flowchart TD
 A([START]) --> B[Applicant submits sign-up] --> C{Valid and unique?}
 C -- No --> D[Show field errors] --> B
 C -- Yes --> E[Create PENDING account] --> F[Admin/BOD can inspect application]
 F --> G[Admin reviews] --> H{Admin decision}
 H -- Approve --> I[Activate, assign role, notify] --> Z([END])
 H -- Reject --> J[Reject with reason, notify] --> Z
```

BOD can edit permissible profile data and view applications; cannot approve/reject/activate or grant elevated roles. Never make a `PENDING` account authenticated for app access.

## 3. Proposal and BOD authorization

```mermaid
flowchart TD
 A([START]) --> B[Active user creates draft concept] --> C[Submit concept] --> D[Secretary reviews]
 D --> E{Secretary passes?}
 E -- No --> F[Needs revision + comments] --> G[Submitter edits and resubmits] --> D
 E -- Yes --> H[Treasurer financial feasibility review] --> I{Treasurer passes?}
 I -- No --> F
 I -- Yes --> J[Place on BOD meeting agenda] --> K[Offline board evaluation]
 K --> L{Official BOD decision?}
 L -- Deferred --> J
 L -- Rejected --> M[Record rejection and notify] --> Z([END])
 L -- Approved --> N[Admin records meeting date + resolution + evidence]
 N --> O[System verifies resolution details] --> P[Designate submitting user as chair]
 P --> Q[Chair completes PPF] --> R{PPF complete?}
 R -- No --> Q
 R -- Yes --> S[Lock master plan] --> T[Ready to launch] --> Z
```

Review/revision is not BOD rejection. BOD official decision cannot be manufactured via one click by a non-authorized actor. `chairUserId` is assigned only after validated approval record.

## 4. Tasks and scheduling

```mermaid
flowchart TD
 A([START: plan locked]) --> B[Chair creates tasks and schedules] --> C[Chair assigns member] --> D[Notify assignee]
 D --> E{Accepted?}
 E -- Declined --> F[Notify chair / pick another assignee] --> C
 E -- Accepted --> G[Member updates progress] --> H{Task done?}
 H -- No --> G
 H -- Yes --> I[Mark completed / record evidence] --> J{Required tasks finished?}
 J -- No --> B
 J -- Yes --> K[Chair confirms implementation completed] --> L([END: reporting ready])
```

Other users can view chapter task status but cannot edit tasks unless chair or assigned. Chair can update deadlines with audit event, respecting approved plan change policy.

## 5. Project finance and membership dues

```mermaid
flowchart TD
 A([START]) --> B{Choose function}
 B -- Project --> C[Show approved budget + balances] --> D[Chair enters income/expense and receipt]
 D --> E{Valid and within policy?}
 E -- No --> F[Error / approval requirement] --> D
 E -- Yes --> G[Post transaction + audit] --> H[Recalculate balances] --> Z([END])
 B -- Dues --> I[Treasurer posts dues charge/payment] --> J{Valid amount/member?}
 J -- No --> K[Fix details] --> I
 J -- Yes --> L[Save payment + notify member] --> Z
 B -- Monitor --> M[Authorized user views chapter budget summary] --> Z
```

Use decimal money arithmetic, explicit currency `PHP`, no negative amount hack for refunds; adjustments are linked reversal records. All users can monitor aggregate budgets/expenses, not private receipts or other people's dues.

## 6. Notifications

```mermaid
flowchart TD
 A([START: domain event]) --> B[Identify permitted recipients] --> C[Write notification record]
 C --> D[Show unread item in inbox] --> E{Opened?}
 E -- No --> F[Remain unread] --> Z([END])
 E -- Yes --> G[Display details] --> H[Mark read] --> Z
```

Create notifications on sign-up decisions, proposal reviews, BOD decisions, task assignment/decline/deadlines, report revisions/approvals, and dues reminders. Avoid duplicate sends using event idempotency keys.

## 7. PCR, liquidation, closure

```mermaid
flowchart TD
 A([START: implementation complete]) --> B[Chair drafts PCR + liquidation] --> C[Validate required sections, receipts, totals]
 C --> D{Complete?}
 D -- No --> B
 D -- Yes --> E[Submit both / REPORTING] --> F[Admin reviews PCR]
 F --> G{PCR approved?}
 G -- No --> H[Return PCR with comments] --> B
 G -- Yes --> I[Treasurer reviews liquidation] --> J{Liquidation approved?}
 J -- No --> K[Return liquidation with comments] --> B
 J -- Yes --> L{Both approvals valid and not self-approved?}
 L -- No --> M[Route to alternate authorized approver] --> F
 L -- Yes --> N[Close & lock project] --> O[Archive signed snapshots] --> Z([END])
```

In implementation, a returned **liquidation** should re-enter only Treasurer review after correction while retaining a still-valid approved PCR; returned PCR re-enters only PCR review. This avoids unintended loss of prior approvals. Approval should be invalidated only if changed data affects what was approved.

## Proposal status transition table

| From | Event | To | Who |
|---|---|---|---|
| DRAFT | submit | SUBMITTED | proposer |
| SUBMITTED | begin admin review | UNDER_SECRETARY_REVIEW | Admin |
| UNDER_SECRETARY_REVIEW | needs correction | NEEDS_REVISION | Admin |
| NEEDS_REVISION | resubmit | UNDER_SECRETARY_REVIEW | proposer |
| UNDER_SECRETARY_REVIEW | passed | UNDER_TREASURER_REVIEW | Admin |
| UNDER_TREASURER_REVIEW | needs correction | NEEDS_REVISION | Treasurer |
| UNDER_TREASURER_REVIEW | passed | BOD_AGENDA | Treasurer |
| BOD_AGENDA | BOD rejected + recorded | REJECTED | Admin records official decision |
| BOD_AGENDA | BOD approved + resolution recorded | APPROVED_BY_BOD | Admin records official decision |
| APPROVED_BY_BOD | full PPF valid and locked | PLAN_LOCKED | Chair (lock subject to policy) |
| PLAN_LOCKED | launch | LAUNCHING | Chair |
| LAUNCHING | required activities complete | REPORTING | Chair |
| REPORTING | valid PCR + liquidation approvals | ARCHIVED | system transaction |

No transition from `REJECTED` to `APPROVED_BY_BOD` without a **new** submission and full reviews. A `CANCELLED` state can be initiated only by Admin with recorded reason (policy choice).

## Core invariants

- Every approval/denial/revision is timestamped and attributable to a real actor.
- BOD resolution number/date/evidence must exist before chair access begins.
- Audit ledger records prior and new status; statuses cannot be directly edited through generic CRUD.
- A project cannot archive with missing PCR approval, liquidation approval, or outstanding required activities.
- No report can be self-approved; a delegated approver must be configured as needed.
