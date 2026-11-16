<?php
require __DIR__.'/../../vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Settings;

$out = dirname(__DIR__).'/JCI_Carmona_System_Thesis_Reference.docx';
$w = new PhpWord();
Settings::setOutputEscapingEnabled(true);
$w->setDefaultFontName('Calibri');
$w->setDefaultFontSize(11);
$w->setDefaultParagraphStyle(['spaceBefore'=>0, 'spaceAfter'=>120, 'lineSpacing'=>300]);
$w->getDocInfo()->setCreator('JCI Carmona / JCISTEM System Reference');
$w->getDocInfo()->setTitle('JCI Carmona Project Management System: Thesis Reference');
$w->getDocInfo()->setSubject('System description and implementation reference based on repository review on 14 November 2026');
$section = $w->addSection(['paperSize'=> 'Letter', 'marginTop'=>1440,'marginBottom'=>1440,'marginLeft'=>1440,'marginRight'=>1440,'headerHeight'=>708,'footerHeight'=>708]);
$w->addTitleStyle(1,['name'=>'Calibri','size'=>28,'bold'=>true,'color'=>'0B2545'],['spaceBefore'=>0,'spaceAfter'=>240]);
$w->addTitleStyle(2,['name'=>'Calibri','size'=>16,'bold'=>true,'color'=>'2E74B5'],['spaceBefore'=>360,'spaceAfter'=>160]);
$w->addTitleStyle(3,['name'=>'Calibri','size'=>13,'bold'=>true,'color'=>'1F4D78'],['spaceBefore'=>280,'spaceAfter'=>120]);
$w->addFontStyle('SmallGray',['name'=>'Calibri','size'=>9,'color'=>'5B6573']);
$w->addFontStyle('CodeRef',['name'=>'Consolas','size'=>9,'color'=>'263445']);
$w->addParagraphStyle('Lead',['spaceBefore'=>0,'spaceAfter'=>220,'lineSpacing'=>300]);
$w->addParagraphStyle('TableText',['spaceBefore'=>30,'spaceAfter'=>30,'lineSpacing'=>240]);
$w->addParagraphStyle('Callout',['spaceBefore'=>120,'spaceAfter'=>180,'indentation'=>['left'=>180,'right'=>180],'lineSpacing'=>280]);
$header=$section->addHeader(); $header->addText('JCI CARMONA  |  JCISTEM SYSTEM REFERENCE',['name'=>'Calibri','size'=>8,'bold'=>true,'color'=>'627184'],['alignment'=>Jc::END,'spaceAfter'=>60]);
$footer=$section->addFooter(); $footer->addText('Thesis reference • Repository reviewed 14 November 2026                                             Page {PAGE}', ['name'=>'Calibri','size'=>8,'color'=>'627184']);

function p($s,$text,$style=null){$s->addText($text,$style??[], $style==='Lead'?'Lead':null);}
function h($s,$text,$level=2){$s->addTitle($text,$level);}
function bullet($s,$text){$s->addListItem($text,0,['name'=>'Calibri','size'=>10],['listType'=>PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED]);}
function numitem($s,$text){$s->addListItem($text,0,['name'=>'Calibri','size'=>10],['listType'=>PhpOffice\PhpWord\Style\ListItem::TYPE_NUMBER]);}
function table($s,$headers,$rows,$widths=null){
 $widths=$widths??array_fill(0,count($headers),9360/count($headers));
 $t=$s->addTable(['borderSize'=>4,'borderColor'=>'D6DEE8','cellMargin'=>100,'width'=>9360,'unit'=>PhpOffice\PhpWord\SimpleType\TblWidth::TWIP,'layout'=>PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED]);
 $t->addRow(null,['tblHeader'=>true]); foreach($headers as $i=>$v){$c=$t->addCell((int)$widths[$i],['bgColor'=>'E8EEF5','valign'=>'center']);$c->addText($v,['bold'=>true,'size'=>9,'color'=>'0B2545'],['spaceAfter'=>0]);}
 foreach($rows as $row){$t->addRow();foreach($row as $i=>$v){$t->addCell((int)$widths[$i],['valign'=>'center'])->addText((string)$v,['size'=>9],['spaceAfter'=>0,'lineSpacing'=>230]);}}
 $s->addTextBreak(1);
}
function source($s,$text){$s->addText('Repository source: '.$text,'SmallGray',['spaceBefore'=>30,'spaceAfter'=>160]);}

// Cover page
$section->addTextBreak(4);
$section->addText('JCI CARMONA', ['name'=>'Calibri','size'=>13,'bold'=>true,'color'=>'1687A7'], ['alignment'=>Jc::CENTER,'spaceAfter'=>220]);
$section->addText('Project Management\nSystem', ['name'=>'Calibri','size'=>30,'bold'=>true,'color'=>'0B2545'], ['alignment'=>Jc::CENTER,'spaceAfter'=>240,'lineSpacing'=>360]);
$section->addText('Thesis Reference Guide', ['name'=>'Calibri','size'=>19,'color'=>'2E74B5'], ['alignment'=>Jc::CENTER,'spaceAfter'=>360]);
$section->addText('System purpose • features • workflows • architecture • data • security • evaluation', ['name'=>'Calibri','size'=>11,'color'=>'526274'], ['alignment'=>Jc::CENTER,'spaceAfter'=>120]);
$section->addText('Prepared from the JCI Carmona / JCISTEM application repository', ['name'=>'Calibri','size'=>10,'color'=>'526274'], ['alignment'=>Jc::CENTER,'spaceAfter'=>120]);
$section->addText('Repository review date: 14 November 2026', ['name'=>'Calibri','size'=>10,'color'=>'526274'], ['alignment'=>Jc::CENTER,'spaceAfter'=>480]);
$section->addText('Working reference for thesis drafting. Confirm chapter-specific policies, deployment details, and evaluation results with the project owner before submission.', ['name'=>'Calibri','size'=>9,'italic'=>true,'color'=>'7A5A00'], ['alignment'=>Jc::CENTER,'spaceAfter'=>0]);
$section->addPageBreak();

h($section,'How to use this reference',1);
p($section,'This guide summarizes the system that exists in the reviewed codebase. It is organized to support common thesis sections such as the introduction, system analysis, design and methodology, implementation, testing, and recommendations. It is not a substitute for the institution’s thesis format or for measured user and performance results.');
$section->addText('Evidence rule: implementation claims below are grounded in repository code, routes, migrations, and project documentation. Recommendations and study variables are identified as items for the researcher to verify or measure.',[], 'Callout');
h($section,'Quick system profile',2);
table($section,['Item','Repository-supported description'],[
 ['System name','JCI Carmona Project Management System; the authenticated member portal is branded JCISTEM.'],
 ['Primary users','JCI Carmona administrators, Board of Directors (BOD), Treasurer, and general members.'],
 ['Primary purpose','Coordinate project proposal review, execution tasks, project finance, membership records, documents, partnerships, schedules, reports, and chapter communications.'],
 ['Application type','Browser-based, server-rendered Laravel web application with responsive Blade views and JavaScript/CSS assets.'],
 ['Backend and database','PHP 8.2+; Laravel 12; Eloquent ORM; relational database supported through Laravel configuration. Local implementation documentation records MariaDB 10.4.32.'],
 ['Main supporting libraries','PHPWord for DOCX and document processing; Dompdf for PDF; Vite and JavaScript test/build tooling.'],
 ['Review snapshot','14 November 2026. Current working tree contained uncommitted changes during review; this document does not certify a released build.']
],[2400,6960]);
h($section,'Thesis-ready system summary',2);
p($section,'The JCI Carmona Project Management System (JCISTEM) is a role-aware web application intended to support the lifecycle of chapter projects, from concept preparation and review through implementation, financial tracking, reporting, and archival. It combines project and task records with member and partner data, private supporting documents, notifications, audit history, and finance workflows. Server-side authorization uses the user’s permanent role together with record relationships and current workflow state.');
source($section,'composer.json; routes/web.php; app/Support/Permissions.php; docs/pms-v2.1/IMPLEMENTATION_STATUS.md.');

h($section,'1. Organizational and problem context',1);
p($section,'JCI Carmona is the organization represented by the public site and member workspace. The public pages explain the chapter and invite contact or participation. JCISTEM is the authenticated workspace used for internal chapter operations. The application therefore contains both a public information layer and a private administrative and project-management layer.');
h($section,'Operational problem addressed',2);
p($section,'Chapter projects commonly involve multiple officers, volunteers, approvals, schedules, budgets, receipts, partner communications, and post-activity reports. The system provides a shared record and controlled workflow so members can submit project information, reviewers can record decisions, project chairs can coordinate implementation, and authorized officers can verify financial and completion records.');
h($section,'System objectives reflected in the code',2);
foreach([
 'Centralize project concepts, formal proposals, review comments, tasks, schedules, documents, reports, and partnership records.',
 'Support distinct authority for Admin, BOD, Treasurer, and general member roles, with additional project-scoped Chair permissions.',
 'Keep project status, approval evidence, budget allocations, spending records, and report review history connected to the relevant project.',
 'Provide chapter-wide views and summaries while limiting access to private records and sensitive attachments.',
 'Record important user and system actions through notifications, review snapshots, and audit logs.'
] as $x) bullet($section,$x);
h($section,'Scope boundary',2);
p($section,'The codebase supports chapter administration and project management. It does not by itself prove organizational outcomes, community impact, user satisfaction, adoption, or improvement in project completion time. Those are empirical thesis questions requiring study data. Public descriptions and seeded examples must not be presented as operational results unless independently verified.');
source($section,'resources/views/landing.blade.php; resources/views/public; routes/web.php; docs/public-pages.md; docs/pms-v2.1/IMPLEMENTATION_STATUS.md.');

h($section,'2. Users, roles, and access model',1);
p($section,'The four permanent account roles are Admin, BOD, Treasurer, and Member. Chair is a temporary project-level assignment, not a fifth account role. An active user assigned as Chair receives authority over the relevant project only when the project state allows it.');
table($section,['Role','Main responsibilities supported','Key limits'],[
 ['Admin','Manage accounts and registrations; review project submissions; record formal BOD decisions and evidence; manage settings, partner records and inquiries; review completion reports; view audit history; perform permitted archive and workflow actions.','Does not automatically receive Treasurer-only ledger, dues, or financial-management permission. Cannot self-approve own submitted records where independent review is required.'],
 ['BOD','View chapter projects and member records; participate in board governance through recorded decision workflows.','Does not manage accounts/roles or chapter finance through Treasurer permissions.'],
 ['Treasurer','Review proposed budgets; manage allocations, expense claims, ledger, dues, fund releases, liquidation review, and finance exports.','Cannot exercise Admin-only account, inquiry, or project-final-decision privileges. Independent review limits apply.'],
 ['General Member','Submit concepts, view permitted projects and chapter information, complete assigned tasks, access own dues/claims, and act as project Chair when assigned.','Cannot enter officer-only finance or member-management actions; access to drafts and files follows visibility rules.'],
 ['Project Chair (project scoped)','Prepare an endorsed proposal, and after approval manage the locked project plan, tasks, schedule, project documents, eligible expense claims, and completion/reporting submissions.','Authority applies to one project and a valid stage; chair cannot approve their own finance/report records.']
],[1550,4430,3380]);
h($section,'Authorization layers',2);
foreach([
 'Authentication and active-account status: a logged-in account must be active to use protected workspace routes.',
 'Role permission: centralized permissions and route middleware restrict officer functions.',
 'Record relationship: owner, assigned Chair, assignee, partner/project visibility, or document association can further restrict a record.',
 'Workflow state: server-side transition rules reject actions that are out of order or invalid for the current status.',
 'Navigation filtering: menu items reflect permissions for usability; hidden links are not the security boundary because controllers and services recheck access.'
] as $x) bullet($section,$x);
source($section,'app/Support/Permissions.php; app/Support/PmsNavigation.php; app/Services/ChapterService.php; app/Http/Middleware/EnsureActive.php; app/Http/Middleware/EnsurePermission.php; routes/web.php.');

h($section,'3. Functional modules',1);
h($section,'3.1 Public chapter pages and authentication',2);
p($section,'Public routes present chapter information, activities and stories, ways to get involved, partner information, and a contact form. The member portal provides login, registration request, password recovery, and protected logout flows. Registration is subject to chapter review and account state. Authentication uses local application accounts; public social links are not external login providers.');
h($section,'3.2 Dashboard and project workspace',2);
p($section,'The dashboard provides role-aware entry to active work, project summaries, charts, notifications, tasks, and schedules. A project has one workspace whose sections organize Overview, Proposal, Tasks, Schedule, Team, Budget, Documents, and Completion information. Project visibility and edit authority are enforced in backend queries and actions.');
h($section,'3.3 Project concepts and proposal review',2);
p($section,'Project owners can save a draft concept and submit required information online or attach a completed supported template. The concept is reviewed by Admin, then Treasurer feasibility review, after which the concept can proceed to development. The Chair role can begin at proposal-preparation stage. A formal proposal captures detailed plans and itemized expenses; Treasurer review is tied to the exact submitted revision. Admin records the official BOD decision, meeting date, and signed evidence. A decision record preserves the result and evidence path.');
p($section,'The state machine records review actions, comments, before/after status, and snapshots. “Needs Revision” and “Returned for Revision” are distinct from final rejection. When a proposal changes after review, revision checks require the relevant resubmission and budget review to be repeated.');
h($section,'3.4 Task and schedule management',2);
p($section,'Project Chairs create tasks with descriptions, assignees, priority, dates, milestones, dependencies, notes, and completion evidence. Members can respond to task assignment and update assigned work. Task completion contributes to project progress and completion readiness. Tasks can be soft-deleted and restored under authorized project access. The calendar includes project events and supports month-scoped filtering.');
h($section,'3.5 Finance, claims, fund releases, and dues',2);
p($section,'Finance features include proposed and allocated project budgets, line-item spending checks, Treasurer ledger entries, receipts, fund-release requests and Chair confirmation, member dues assessment/payment records, expense claim correction and review, finance summaries, exports, and liquidation reports. An approved expense claim creates a posted debit ledger entry. Budget checks consider posted spending and pending claims/releases so that concurrent pending obligations do not exceed an allocated line.');
p($section,'The project’s spent amount is based on posted debit ledger entries. Voided or non-posted entries are not treated as current posted spending. Financial actions use database transactions and row locks in key workflows.');
h($section,'3.6 Reports and closure',2);
p($section,'Completion and liquidation reports are separate report types and have independent reviewers: Admin for completion and Treasurer for liquidation. Reviewers may return reports for correction. The system checks that approvals are recorded by the expected role and are not self-approvals before changing the project to Completed. Archival is a later lifecycle action; the source includes scheduled archival support.');
h($section,'3.7 Documents and templates',2);
p($section,'Project documents include uploaded concept/proposal files, supporting documents, receipts, partnership materials, and evidence. Private files are stored on Laravel’s local private disk and returned through access-checked routes. Submissions uploaded as documents are linked to project revision and prior-version metadata. The active account Templates & Forms page offers user-supplied Word reference forms and Admin-managed templates. Generated project summaries can be exported as DOCX/PDF; these exports do not replace signed official chapter forms.');
h($section,'3.8 Partnerships and external letters',2);
p($section,'A partner directory stores partner identity and contact information, status, activities, and project associations. The partnership-agreement workflow supports draft, submission, Admin review, return or approval, and upload of the signed agreement. External LOI/letter records remain separate from internal concept and proposal documents.');
h($section,'3.9 Notifications, inquiries, and audit',2);
p($section,'Notifications inform users about review decisions, tasks, dues reminders, claims, fund releases, agreements, and other workflow events. Important notifications can require opening. Event keys or explicit state checks reduce duplicate reminders/actions. Public contact inquiries are stored for Admin review. Audit rows record selected changes with redaction of sensitive values such as passwords, profile payloads, and private file paths.');
source($section,'routes/web.php; app/Http/Controllers; app/Services; app/Support; resources/views/chapter; database/migrations.');

h($section,'4. Core project lifecycle',1);
table($section,['Stage','Typical activity','Control / output'],[
 ['1. Draft concept','Owner creates project details, objectives, schedule, venue, budget estimate, and concept answers or completed upload.','Draft is primarily visible to owner and authorized officers. Required fields and evidence are checked before submission.'],
 ['2. Admin review','Admin endorses concept or requests revision with comments.','Transition is recorded in project review history.'],
 ['3. Treasurer concept review','Treasurer reviews feasibility and budget basis or requests revision.','Independent Treasurer review; approved concept advances to proposal development.'],
 ['4. Formal proposal','Chair prepares proposal and itemized expenses.','Proposal validation checks completeness, budget totals, and submission revision.'],
 ['5. Treasurer proposal review','Treasurer verifies submitted budget revision.','Review stamp binds to current project revision.'],
 ['6. BOD decision','Admin records the Board’s official decision, meeting date, and signed evidence.','Formal decision record captures approval, rejection, or deferral.'],
 ['7. Plan lock and implementation','Chair locks approved plan, Treasurer allocates budget lines, Chair manages tasks and schedule.','Plan and authorized budget guide delivery; project actions are state- and role-checked.'],
 ['8. Finance / execution','Chair files receipt-backed claims; Treasurer reviews and posts eligible spending; fund releases can be confirmed by Chair.','Approved claims link to ledger debits; balance checks enforce line limits.'],
 ['9. Reporting','Chair completes tasks and submits completion and liquidation data.','Tasks and pending financial actions must be resolved before reporting.'],
 ['10. Independent approvals','Admin reviews completion; Treasurer reviews liquidation.','Both valid independent approvals are required for Completed status.'],
 ['11. Archive','Completed project is archived according to lifecycle rules/scheduler.','Archived records are retained for lookup and protected from normal operational changes.']
],[1600,3650,4110]);
p($section,'The project transition service wraps changes in a database transaction and locks the project row while applying a transition. This reduces race conditions when two requests attempt to change the same project concurrently. Exact labels may vary across historical or legacy records; consult current transition code before describing a particular database record.');
source($section,'app/Services/ProposalWorkflowService.php; app/Services/ReportWorkflowService.php; app/Services/ProjectArchiveService.php; app/Support/ProjectFlow.php; app/Support/ProjectSubmission.php.');

h($section,'5. Technical architecture and implementation',1);
table($section,['Layer','Implementation role','Representative files'],[
 ['Presentation','Blade templates render public pages and authenticated workspace; CSS/JavaScript provide responsive styling and interactions.','resources/views; public/assets; resources/css; resources/js; vite.config.js'],
 ['Routing and middleware','Named HTTP routes connect requests to controller actions. Middleware handles authentication, account status, role/permission checks, workspace session, and response security headers.','routes/web.php; bootstrap/app.php; app/Http/Middleware'],
 ['Controllers','Validate requests and coordinate page data and domain actions.','app/Http/Controllers'],
 ['Domain services and support','Centralize workflow transitions, visibility rules, finance guards, calculations, permissions, navigation, and exports.','app/Services; app/Support'],
 ['Persistence','Eloquent models map application entities to relational tables; migrations evolve the schema.','app/Models; database/migrations'],
 ['Files and generated outputs','Private local storage holds uploads/evidence; PHPWord and Dompdf support Word/PDF output and previews.','storage/app/private; app/Services/ExportService.php; ProjectWordDocumentService.php'],
 ['Scheduled work','Laravel Scheduler runs reminders, backup, inactive-member cleanup, and completed-project archival commands when host scheduler is active.','routes/console.php; app/Console/Commands']
],[1500,4200,3660]);
h($section,'Request flow',2);
foreach([
 'Browser sends a request to a named route.',
 'Web middleware establishes workspace/session behavior and applies security headers; protected routes require authentication and active status.',
 'Permission middleware and controller/service checks authorize the user, record relationship, and workflow stage.',
 'Validated data is persisted through Eloquent; multi-record decisions use transactions and, where required, row locks.',
 'Audit entries and in-app notifications are written for important domain changes.',
 'Controller returns a Blade view, redirect, private file response, or generated export.'
] as $x) numitem($section,$x);
h($section,'Technology inventory',2);
table($section,['Technology','Role in this repository'],[
 ['PHP 8.2+','Backend language requirement listed by Composer.'],
 ['Laravel 12','Routing, middleware, validation, authentication, ORM, migrations, scheduler, storage, and testing framework.'],
 ['MariaDB/MySQL driver','Configured local relational database in the implementation notes; Laravel database configuration is deployment-dependent.'],
 ['Blade / HTML / CSS / JavaScript','Server-rendered interface and browser interactions.'],
 ['Vite / npm','Frontend asset development and production build.'],
 ['PHPWord / Dompdf','DOCX generation/document operations and PDF output.'],
 ['PHPUnit / Vitest / Node test runner','Backend feature and unit tests plus JavaScript tests.']
],[2800,6560]);
source($section,'composer.json; package.json; bootstrap/app.php; routes/web.php; docs/pms-v2.1/IMPLEMENTATION_STATUS.md.');

h($section,'6. Data design and entity relationships',1);
p($section,'The application uses a relational schema managed through migrations. Records are connected through foreign keys or model relations. JSON fields are used for flexible structured content such as proposal answers, report data, profiles, budget items, assignees, and workflow snapshots.');
table($section,['Entity / table family','Purpose and important relationships'],[
 ['users','Accounts, permanent role, active/inactive state, profile and login metadata. Linked to authored/assigned projects, records, reviews, and audit history.'],
 ['projects','Project identity, owner, Chair, status, dates, venue, concept/proposal data, revision, budget items, approval and archive timestamps. Parent record for tasks, finance, files, reports, events, and decisions.'],
 ['project_reviews; bod_decisions','Transition history and recorded Board decisions, including stage, meeting date, evidence location, decision, comments, and reviewer.'],
 ['tasks; calendar_events','Project execution tasks, assignments, dependencies, deadlines, completion evidence, and scheduled chapter/project activities.'],
 ['budget_allocations; ledger_entries; expenses; expense_claims; project_fund_releases; member_dues','Approved/allocated funds, income/expense entries, receipt-backed claims, release requests, and member assessment/payment information. Ledger entries can reference project, member, and due record.'],
 ['project_documents; project_reports; lois','Private project files, independently reviewed completion/liquidation/progress reports, and letters/LOI with version and review data.'],
 ['partners; partner_activities; partnership_agreements','External partner directory, interaction history, project-linked agreements, review and signed-document records.'],
 ['notifications; audit_logs','User inbox and change accountability records.'],
 ['registration_requests; contact_inquiries','Public sign-up applications and contact form messages for authorized administrative processing.'],
 ['document_templates; generated_reports; chapter_settings','Managed templates, generated report metadata, and selected chapter-wide configuration.'],
 ['cache/jobs/sessions tables','Framework infrastructure for cache, queued jobs, session data, and operational support, depending on environment configuration.']
],[2700,6660]);
h($section,'Conceptual relationship summary',2);
p($section,'One user may create many projects; a project has an owner and may have one currently assigned Chair. A project has many tasks, review records, decisions, budget allocations, ledger transactions, claims, releases, documents, reports, letters, and events. Partner records can have activities and agreements, while a partnership agreement may refer to a project. User-linked audit and notification records retain who performed or received an action.');
h($section,'Data integrity mechanisms',2);
foreach([
 'Foreign keys and unique constraints protect key references and identifiers.',
 'Validation rules constrain supported formats, amounts, statuses, and date ranges.',
 'Workflow transactions preserve coherent status and related evidence records.',
 'Integer-cent helper calculations and decimal database columns support money handling; avoid claiming a specific accounting standard without separate evidence.',
 'Soft deletion and archival preserve records while separating active lists from removed or archived items.'
] as $x) bullet($section,$x);
source($section,'database/migrations; app/Models; app/Support/Money.php; app/Support/ProjectBudget.php; app/Services/ProposalWorkflowService.php.');

h($section,'7. Security, privacy, and operational safeguards',1);
table($section,['Area','Controls visible in repository','Thesis description'],[
 ['Confidentiality','Authentication, active-account gate, permission middleware, record-level access, private local file storage, no-store headers for sensitive auth/recovery responses.','Access is restricted by role, project association, and workflow state; private files pass through authorized controllers.'],
 ['Integrity','Server-side validation, controlled state transitions, transaction/row locking, audit entries, version/revision metadata, posted/void ledger states.','Important records cannot rely on UI-only checks; backend rules constrain who can change which data and when.'],
 ['Availability','Health endpoint, scheduled backup command, file/database backups, overlap prevention on scheduled commands.','Availability depends on deployment configuration and the host actually running the scheduler and backups.'],
 ['Authentication protection','Password hashing, login/recovery throttling, session controls, optional session replacement.','Credentials are handled by framework authentication and rate limits are applied to sensitive endpoints.'],
 ['Browser response safety','Security header middleware; HSTS conditional on HTTPS per repository security notes.','Browser security controls reduce selected classes of framing, sniffing, and referrer risks.']
],[1650,4200,3510]);
$section->addText('Operational caveat: automated reminders and backups run only when the deployment host invokes Laravel Scheduler. Email/SMS delivery is not part of the current in-app reminder workflow unless a delivery transport is separately configured.',[], 'Callout');
p($section,'The repository’s security guide notes that backup archives are not encrypted by the backup command itself. It recommends a restricted encrypted volume, off-server copies, protected application-key recovery, and restoration drills. These are deployment practices and should be described as configured only after evidence from the actual target environment.');
source($section,'docs/security.md; app/Http/Middleware; app/Support/Permissions.php; app/Services/ChapterService.php; routes/console.php.');

h($section,'8. Testing and verification evidence',1);
p($section,'The repository contains Laravel feature and unit tests, JavaScript unit tests, and project documentation describing prior verification. Tests cover authorization, registration, project workflows, finance, documents, reports, navigation, public pages, notifications, and security-related behavior.');
table($section,['Test area','Examples of repository coverage'],[
 ['Authorization','Role restrictions, active account requirements, member/project visibility boundaries, private receipts and documents.'],
 ['Project workflow','Valid/invalid transition order, review evidence, proposal revision checks, Chair permissions, plan lock, appeals/cancellations, completion and archive.'],
 ['Finance','Budget allocations, claims, ledger entries, fund releases, dues, receipt access, month filtering, and totals.'],
 ['Interface routes','Public pages, navigation destinations, member screens, templates, reports, and workspace routing.'],
 ['Tooling','PHP syntax/lint, Blade view compilation, Laravel feature suite, Vitest/Node tests, JavaScript syntax, Vite production build, migration status, whitespace checks.']
],[2300,7060]);
$section->addText('Verification status note: the repository implementation-status document records an earlier test/build snapshot. The working tree reviewed for this guide contains uncommitted changes dated through 14 November 2026. No fresh full test run or production deployment was performed for this document, so do not cite older passing totals as the test result for the current snapshot.',[], 'Callout');
h($section,'Suggested thesis evidence to collect',2);
foreach([
 'A dated test report generated from the exact thesis submission commit.',
 'User acceptance testing protocol, participant count, role mix, tasks, scoring method, and anonymized results.',
 'Performance measurements with environment, sample size, workload, and response-time statistics.',
 'Accessibility and cross-device checks with browser/device versions and observed defects.',
 'Backup/restore drill record and confirmation of actual production storage, database, scheduler, and mail configuration.'
] as $x) bullet($section,$x);

h($section,'9. Limitations and matters to confirm',1);
foreach([
 'A repository review describes code and documented configuration; it cannot establish the live production environment or current real-world chapter practice.',
 'The current worktree contains uncommitted edits. Use Git revision/commit and rerun tests before freezing thesis statements.',
 'The browser visual and interaction verification status in the maintained implementation notes is not recorded as complete; independently inspect desktop, mobile, keyboard, and screen-reader behavior.',
 'Database engine and version can differ by environment. The implementation notes identify a local MariaDB version, not every possible deployment.',
 'Scheduled actions depend on host scheduling; a command existing in code does not prove the scheduler is running.',
 'The code does not provide measured user satisfaction, adoption, time savings, error reduction, project success, or community impact.',
 'The exact authority to change locked plans, reopen archived projects, and apply exceptional officer substitutions is a chapter policy question; system policy should be confirmed with chapter leadership.'
] as $x) bullet($section,$x);
h($section,'10. Thesis writing map',1);
table($section,['Thesis chapter / section','Relevant material in this guide','Evidence to pair with it'],[
 ['Chapter 1: Introduction','Organizational context, operational problem, system objectives and scope.','Chapter interviews, approved project-management pain points, research objectives, and local context sources.'],
 ['Chapter 2: Review of related literature/systems','Use this guide only to explain the target system and its domain requirements.','Independent scholarly literature on project management, workflow systems, access control, and community organization recordkeeping.'],
 ['Chapter 3: Methodology / system analysis','Users, functional scope, requirements, role and workflow models, architecture and data design.','Stakeholder validation, requirements traceability, process diagrams, ERD, and development method evidence.'],
 ['Chapter 4: Implementation and testing','Technology stack, modules, workflow rules, security controls, test coverage.','Exact source revision, executed test reports, screenshots captured from current build, and environment configuration.'],
 ['Chapter 5: Results and discussion','Only measured outcomes from testing or user evaluation.','Instrument, sampling method, participants, raw anonymized results, calculations, and limitations.'],
 ['Conclusion and recommendations','Implemented outcomes and remaining policy/operational needs.','Comparison against the stated objectives and verified evidence; do not infer benefits from feature existence alone.']
],[2200,3500,3660]);

h($section,'Appendix A. High-value source map',1);
table($section,['Topic','Start with these repository paths'],[
 ['Routes and access','routes/web.php; bootstrap/app.php; app/Support/Permissions.php; app/Support/PmsNavigation.php'],
 ['Project workflow','app/Services/ProposalWorkflowService.php; app/Services/ReportWorkflowService.php; app/Services/ProjectAppealService.php; app/Support/ProjectFlow.php; app/Support/ProjectSubmission.php'],
 ['Finance','app/Http/Controllers/FinanceController.php; app/Http/Controllers/ExpenseClaimController.php; app/Http/Controllers/FundReleaseController.php; app/Support/ProjectFinanceGuard.php; app/Support/Money.php'],
 ['Members and registration','app/Http/Controllers/RegistrationController.php; app/Http/Controllers/RecordController.php; app/Models/User.php'],
 ['Documents and exports','app/Http/Controllers/ChapterController.php; app/Services/ExportService.php; app/Services/ProjectWordDocumentService.php; docs/pms-v2.1/FORM_REFERENCES.md'],
 ['Partners and agreements','app/Http/Controllers/PartnerController.php; app/Http/Controllers/AgreementController.php; app/Models/Partner.php; app/Models/PartnershipAgreement.php'],
 ['Schema','database/migrations; app/Models'],
 ['Security and operations','docs/security.md; routes/console.php; app/Http/Middleware; app/Console/Commands'],
 ['Public identity','resources/views/landing.blade.php; resources/views/public; docs/public-pages.md; docs/public-chapter-content.md'],
 ['Tests and status','tests/Feature; tests/Unit; tests/js; docs/pms-v2.1/IMPLEMENTATION_STATUS.md']
],[2500,6860]);

h($section,'Appendix B. Glossary',1);
table($section,['Term','Meaning in this system'],[
 ['BOD','Board of Directors; the chapter body whose formal project decision is recorded through a decision record.'],
 ['Concept','Initial project idea and justification submitted for early review.'],
 ['Formal proposal / PPF','Detailed project plan and budget submitted for formal review and Board decision.'],
 ['Project Chair','Member assigned to coordinate a particular project after the applicable workflow stage.'],
 ['Expense claim','Chair-submitted receipt-backed spending record pending Treasurer review; approval posts the linked ledger debit.'],
 ['Fund release','Treasurer-initiated project funding request that the assigned Chair confirms or declines.'],
 ['Completion report','Project outcome report reviewed by an authorized Admin.'],
 ['Liquidation report','Financial closeout report reviewed by an authorized Treasurer.'],
 ['Private disk','Laravel storage disk intended for files served through authorized application routes rather than a public URL.'],
 ['Soft delete','A record-retention approach that marks a record deleted while keeping it in the database for supported restore/history functions.']
],[2450,6910]);

h($section,'Repository evidence list',1);
foreach([
 'Product and implementation status: docs/pms-v2.1/IMPLEMENTATION_STATUS.md.',
 'Architecture, workflow, role, database, page, and test specifications: docs/pms-v2.1/specification/. These documents include design history; current code is the implementation evidence.',
 'Security practices and deployment runbook: docs/security.md.',
 'Public-facing purpose and page behavior: docs/public-pages.md and docs/public-chapter-content.md.',
 'Project forms and their meaning: docs/pms-v2.1/FORM_REFERENCES.md.',
 'Framework and package requirements: composer.json and package.json.',
 'Routes, models, services, middleware, support classes, migrations, and tests referenced throughout this guide.'
] as $x) bullet($section,$x);

IOFactory::createWriter($w,'Word2007')->save($out);
echo $out, PHP_EOL;
