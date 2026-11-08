<?php

use App\Http\Controllers\{AgreementController, AuthController, BudgetReconciliationController, ChapterController, ContactInquiryController, ExpenseClaimController, FinanceController, FundReleaseController, PartnerController, RecordController, RegistrationController};
use Illuminate\Support\Facades\Route;

$workspaceRoutes = function () {
Route::get('/', fn () => view('landing'));
Route::get('/activities', [\App\Http\Controllers\PublicChapterController::class, 'activities'])->name('public.activities');
Route::get('/activities/{slug}', [\App\Http\Controllers\PublicChapterController::class, 'story'])->name('public.story');
Route::view('/get-involved', 'public.get-involved')->name('public.involvement');
Route::view('/partners', 'public.partners')->name('public.partners');
Route::view('/our-chapter', 'public.about')->name('public.about');
Route::get('/contact', [ContactInquiryController::class, 'create'])->name('contact');
Route::post('/contact', [ContactInquiryController::class, 'store'])->middleware('throttle:5,10')->name('contact.store');
Route::get('/contact/thank-you', [ContactInquiryController::class, 'thanks'])->name('contact.thanks');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::middleware('guest')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/register', [RegistrationController::class, 'create'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store'])->middleware('throttle:3,60')->name('register.store');
    Route::get('/register/thanks', [RegistrationController::class, 'thanks'])->name('register.thanks');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendRecoveryOtp'])->middleware('throttle:5,1')->name('password.email');
    Route::post('/forgot-password/send-code', [AuthController::class, 'sendRecoveryOtp'])->middleware('throttle:3,10')->name('password.otp.send');
    Route::get('/forgot-password/verify', [AuthController::class, 'showRecoveryOtp'])->name('password.otp');
    Route::post('/forgot-password/verify', [AuthController::class, 'verifyRecoveryOtp'])->middleware('throttle:8,1')->name('password.otp.verify');
    Route::get('/forgot-password/reset', [AuthController::class, 'showRecoveryReset'])->name('password.recovery.reset');
    Route::post('/forgot-password/reset', [AuthController::class, 'updateRecoveryPassword'])->middleware('throttle:5,1')->name('password.recovery.update');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.update');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [ChapterController::class, 'dashboard'])->name('dashboard');
    Route::get('/reports', [\App\Http\Controllers\PmsToolsController::class, 'reports'])->name('reports.generate');
    Route::post('/reports/export', [\App\Http\Controllers\PmsToolsController::class, 'export'])->name('reports.export');
    Route::get('/documents/templates', [\App\Http\Controllers\PmsToolsController::class, 'templates'])->name('templates');
    Route::get('/documents/templates/reference/{form}', [\App\Http\Controllers\PmsToolsController::class, 'referenceFormDownload'])->where('form', '[a-z0-9-]+')->name('templates.reference.download');
    Route::get('/documents/templates/{template}', [\App\Http\Controllers\PmsToolsController::class, 'templateDownload'])->name('templates.download');
    Route::post('/documents/templates', [\App\Http\Controllers\PmsToolsController::class, 'templateStore'])->middleware('permission:accounts.manage')->name('templates.store');
    Route::get('/admin/settings', [\App\Http\Controllers\PmsToolsController::class, 'settings'])->middleware('permission:accounts.manage')->name('pms.settings');
    Route::put('/admin/settings', [\App\Http\Controllers\PmsToolsController::class, 'saveSettings'])->middleware('permission:accounts.manage')->name('pms.settings.update');
    Route::get('/bod-decisions/{decision}/evidence', [\App\Http\Controllers\PmsToolsController::class, 'decisionEvidence'])->name('decisions.evidence');
    Route::get('/projects', [ChapterController::class, 'projects'])->name('projects');
    Route::get('/projects/completed', [ChapterController::class, 'legacyCompletedProjects']);
    Route::get('/projects/library', [ChapterController::class, 'completedProjects'])->name('projects.completed');
    Route::get('/projects/archived', [ChapterController::class, 'archivedProjects'])->name('projects.archived');
    Route::get('/projects/cancelled', [ChapterController::class, 'cancelledProjects'])->name('projects.cancelled');
    Route::get('/projects/removed', [ChapterController::class, 'removedProjects'])->name('projects.removed');
    Route::post('/projects/{project}/archive', [ChapterController::class, 'projectArchive'])->whereNumber('project')->name('projects.archive');
    Route::get('/projects/create', [ChapterController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ChapterController::class, 'save'])->name('projects.store');
    Route::post('/projects/{project}/reuse', [ChapterController::class, 'projectReuse'])->whereNumber('project')->name('projects.reuse');
    Route::get('/projects/{project}/word/{stage}', [ChapterController::class, 'projectWordDocument'])->whereNumber('project')->whereIn('stage', ['concept', 'proposal'])->name('projects.word');
    Route::post('/projects/{project}/partnerships', [ChapterController::class, 'uploadPartnership'])->whereNumber('project')->name('projects.partnerships.store');
    Route::get('/projects/{project}', [ChapterController::class, 'show'])->name('projects.show')->whereNumber('project');
    Route::delete('/projects/{project}', [ChapterController::class, 'projectDelete'])->name('projects.delete')->whereNumber('project');
    Route::post('/projects/{projectId}/restore', [ChapterController::class, 'projectRestore'])->name('projects.restore')->whereNumber('projectId');
    Route::get('/projects/{project}/proposal', [ChapterController::class, 'proposalPacket'])->name('projects.proposal')->whereNumber('project');
    Route::get('/projects/{project}/people/{member}/photo', [ChapterController::class, 'projectMemberPhoto'])->name('projects.people.photo')->whereNumber(['project', 'member']);
    Route::get('/projects/{project}/edit', [ChapterController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project}', [ChapterController::class, 'save'])->name('projects.update');
    Route::post('/projects/{project}/transition', [ChapterController::class, 'transition'])->name('projects.transition');
    Route::post('/projects/{project}/tasks', [ChapterController::class, 'taskSave'])->name('tasks.store');
    Route::put('/projects/{project}/tasks/{task}', [ChapterController::class, 'taskSave'])->name('tasks.update');
    Route::post('/projects/{project}/tasks/{task}/complete', [ChapterController::class, 'taskComplete'])->name('tasks.complete');
    Route::post('/projects/{project}/tasks/{task}/respond', [ChapterController::class, 'taskRespond'])->name('tasks.respond');
    Route::get('/tasks', [ChapterController::class, 'tasks'])->name('tasks');
    Route::get('/calendar', [ChapterController::class, 'calendar'])->name('calendar');
    Route::get('/documents', [ChapterController::class, 'documents'])->name('documents');
    Route::get('/partner-directory', [PartnerController::class, 'index'])->name('partners.index');
    Route::post('/partner-directory', [PartnerController::class, 'save'])->name('partners.store');
    Route::get('/partner-directory/{partner}', [PartnerController::class, 'show'])->name('partners.show');
    Route::put('/partner-directory/{partner}', [PartnerController::class, 'save'])->name('partners.update');
    Route::post('/partner-directory/{partner}/activities', [PartnerController::class, 'activity'])->name('partners.activities.store');
    Route::get('/partnership-agreements', [AgreementController::class, 'index'])->name('agreements.index');
    Route::get('/partner-directory/{partner}/agreements/create', [AgreementController::class, 'create'])->name('agreements.create');
    Route::post('/partner-directory/{partner}/agreements', [AgreementController::class, 'save'])->name('agreements.store');
    Route::get('/partnership-agreements/{agreement}', [AgreementController::class, 'show'])->name('agreements.show');
    Route::get('/partnership-agreements/{agreement}/edit', [AgreementController::class, 'edit'])->name('agreements.edit');
    Route::put('/partner-directory/{partner}/agreements/{agreement}', [AgreementController::class, 'save'])->name('agreements.update');
    Route::post('/partnership-agreements/{agreement}/transition', [AgreementController::class, 'transition'])->name('agreements.transition');
    Route::post('/partnership-agreements/{agreement}/signed', [AgreementController::class, 'signed'])->name('agreements.signed');
    Route::get('/partnership-agreements/{agreement}/signed', [AgreementController::class, 'download'])->name('agreements.download');
    Route::post('/calendar', [ChapterController::class, 'eventSave'])->name('calendar.store');
    Route::post('/projects/{project}/documents', [ChapterController::class, 'upload'])->name('documents.store');
    Route::post('/projects/{project}/evidence', [ChapterController::class, 'storeEvidence'])->name('projects.evidence.store');
    Route::get('/documents/{document}/preview', [ChapterController::class, 'previewDocument'])->name('documents.preview');
    Route::get('/documents/{document}/original-preview', [ChapterController::class, 'originalDocumentPreview'])->name('documents.original-preview');
    Route::get('/documents/{document}/download', [ChapterController::class, 'download'])->name('documents.download');
    Route::get('/notifications', [ChapterController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/read', [ChapterController::class, 'readNotifications'])->name('notifications.read');
    Route::post('/notifications/{notification}/open', [ChapterController::class, 'openNotification'])->name('notifications.open');
    Route::delete('/notifications/{notification}', [ChapterController::class, 'deleteNotification'])->name('notifications.delete');
    Route::get('/activity-log', [ChapterController::class, 'audit'])->middleware('permission:audit.view')->name('activity-log');
    Route::get('/audit', [ChapterController::class, 'audit'])->middleware('permission:audit.view')->name('audit');
    Route::get('/inquiries', [ContactInquiryController::class, 'index'])->middleware('permission:inquiries.manage')->name('inquiries');
    Route::get('/inquiries/{inquiry}', [ContactInquiryController::class, 'show'])->middleware('permission:inquiries.manage')->name('inquiries.show');
    Route::put('/inquiries/{inquiry}', [ContactInquiryController::class, 'update'])->middleware('permission:inquiries.manage')->name('inquiries.update');
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance');
    Route::get('/expense-claims', [ExpenseClaimController::class, 'index'])->name('expense-claims.index');
    Route::post('/projects/{project}/expense-claims', [ExpenseClaimController::class, 'store'])->name('expense-claims.store');
    Route::put('/expense-claims/{claim}', [ExpenseClaimController::class, 'update'])->name('expense-claims.update');
    Route::post('/expense-claims/{claim}/decision', [ExpenseClaimController::class, 'review'])->name('expense-claims.review');
    Route::get('/expense-claims/{claim}/receipt', [ExpenseClaimController::class, 'receipt'])->name('expense-claims.receipt');
    Route::post('/projects/{project}/budget', [FinanceController::class, 'budget'])->middleware('permission:finance.manage')->name('budget.store');
    Route::post('/projects/{project}/fund-releases', [FundReleaseController::class, 'store'])->name('fund-releases.store');
    Route::post('/fund-releases/{fundRelease}/response', [FundReleaseController::class, 'respond'])->name('fund-releases.respond');
    Route::post('/fund-releases/{fundRelease}/cancel', [FundReleaseController::class, 'cancel'])->name('fund-releases.cancel');
    Route::post('/projects/{project}/budget/reconcile-plan', [BudgetReconciliationController::class, 'plan'])->middleware('permission:finance.manage')->name('budget.reconcile-plan');
    Route::post('/projects/{project}/budget/reconcile-allocation/{allocation}', [BudgetReconciliationController::class, 'allocation'])->middleware('permission:finance.manage')->name('budget.reconcile-allocation');
    Route::post('/projects/{project}/budget/reconcile-entry/{entry}', [BudgetReconciliationController::class, 'entry'])->middleware('permission:finance.manage')->name('budget.reconcile-entry');
    Route::get('/projects/{project}/liquidation', [FinanceController::class, 'liquidationReport'])->middleware('permission:finance.manage')->name('projects.liquidation');
    Route::get('/ledger/export', [FinanceController::class, 'export'])->middleware('permission:finance.manage')->name('ledger.export');
    Route::get('/ledger', [FinanceController::class, 'ledger'])->middleware('permission:finance.manage')->name('ledger');
    Route::post('/ledger', [FinanceController::class, 'store'])->middleware('permission:finance.manage')->name('ledger.store');
    Route::post('/ledger/{entry}', [FinanceController::class, 'update'])->middleware('permission:finance.manage')->name('ledger.update');
    Route::get('/ledger/{entry}/receipt', [FinanceController::class, 'receipt'])->name('ledger.receipt');
    Route::get('/dues', [FinanceController::class, 'dues'])->name('dues');
    Route::get('/dues/manage', [FinanceController::class, 'dues'])->middleware('permission:finance.manage')->name('dues.manage');
    Route::post('/dues', [FinanceController::class, 'openDues'])->middleware('permission:finance.manage')->name('dues.store');
    Route::post('/dues/{due}/payment', [FinanceController::class, 'payDues'])->middleware('permission:finance.manage')->name('dues.payment');
    Route::get('/records/{kind}', [RecordController::class, 'index'])->name('records');
    Route::get('/records/{kind}/create', [RecordController::class, 'create'])->name('records.create');
    Route::post('/records/{kind}', [RecordController::class, 'save'])->name('records.store');
    Route::get('/records/{kind}/{id}', [RecordController::class, 'show'])->name('records.show')->whereNumber('id');
    Route::get('/records/{kind}/{id}/edit', [RecordController::class, 'edit'])->name('records.edit');
    Route::put('/records/{kind}/{id}', [RecordController::class, 'save'])->name('records.update');
    Route::post('/records/{kind}/{id}/transition', [RecordController::class, 'transition'])->name('records.transition');
    Route::get('/records/{kind}/{id}/attachment', [RecordController::class, 'attachment'])->name('records.attachment');
    Route::get('/members', [RecordController::class, 'members'])->name('members');
    Route::get('/members/{member}/photo', [RecordController::class, 'memberPhoto'])->name('members.photo');
    Route::get('/members/manage', [RecordController::class, 'members'])->middleware('permission:members.manage')->name('members.manage');
    Route::get('/admin/accounts', [RecordController::class, 'members'])->middleware('permission:accounts.manage')->name('accounts');
    Route::get('/registrations', [RegistrationController::class, 'index'])->middleware('permission:members.manage')->name('registrations');
    Route::get('/registrations/{registration}', [RegistrationController::class, 'show'])->middleware('permission:members.manage')->name('registrations.show');
    Route::get('/registrations/{registration}/photo', [RegistrationController::class, 'photo'])->middleware('permission:members.manage')->name('registrations.photo');
    Route::post('/registrations/{registration}/review', [RegistrationController::class, 'review'])->middleware('permission:accounts.manage')->name('registrations.review');
    Route::get('/members/create', [RecordController::class, 'memberForm'])->middleware('permission:accounts.manage')->name('members.create');
    Route::post('/members', [RecordController::class, 'memberSave'])->middleware('permission:accounts.manage')->name('members.store');
    Route::get('/members/{member}/edit', [RecordController::class, 'memberForm'])->middleware('permission:members.manage')->name('members.edit');
    Route::put('/members/{member}', [RecordController::class, 'memberSave'])->middleware('permission:members.manage')->name('members.update');
    Route::delete('/members/{member}', [RecordController::class, 'memberDelete'])->middleware('permission:accounts.manage')->name('members.delete');
    Route::post('/members/{memberId}/restore', [RecordController::class, 'memberRestore'])->whereNumber('memberId')->middleware('permission:accounts.manage')->name('members.restore');
    Route::get('/account', [RecordController::class, 'account'])->name('account');
    Route::get('/account/photo', [RecordController::class, 'accountPhoto'])->name('account.photo');
    Route::put('/account', [RecordController::class, 'accountSave'])->name('account.update');
    Route::get('/settings', [RecordController::class, 'settings'])->name('settings');
    Route::put('/settings', [RecordController::class, 'settingsSave'])->name('settings.update');

    // Preserve bookmarked workspace URLs from the original prototype.
    foreach (['admin', 'bod', 'treasurer', 'member'] as $role) {
        Route::get('/'.$role.'/{path?}', function (string $path = '') {
            $destination = match (true) {
                $path === '' => 'dashboard',
                str_starts_with($path, 'finance') || in_array($path, ['budget', 'utilization']) => 'finance',
                in_array($path, ['ledger', 'expenses', 'liquidation']) => 'ledger',
                $path === 'loi' => 'records/letters',
                in_array($path, ['reports', 'report']) => 'records/reports',
                $path === 'members/registration' => 'members/create',
                default => $path,
            };
            return redirect(url('/'.$destination));
        })->where('path', '.*');
    }
});
};

$workspaceRoutes();
Route::prefix('workspaces/{workspace}')
    ->where(['workspace' => '[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}'])
    ->name('scoped.')->group($workspaceRoutes);
