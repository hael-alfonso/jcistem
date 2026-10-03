<?php

use App\Http\Controllers\{AuthController, ChapterController, FinanceController, RecordController};
use Illuminate\Support\Facades\Route;

$workspaceRoutes = function () {
Route::get('/', fn () => redirect()->route('login'));
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::middleware('guest')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [ChapterController::class, 'dashboard'])->name('dashboard');
    Route::get('/projects', [ChapterController::class, 'projects'])->name('projects');
    Route::get('/projects/create', [ChapterController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ChapterController::class, 'save'])->name('projects.store');
    Route::get('/projects/{project}', [ChapterController::class, 'show'])->name('projects.show')->whereNumber('project');
    Route::get('/projects/{project}/edit', [ChapterController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project}', [ChapterController::class, 'save'])->name('projects.update');
    Route::post('/projects/{project}/transition', [ChapterController::class, 'transition'])->name('projects.transition');
    Route::post('/projects/{project}/tasks', [ChapterController::class, 'taskSave'])->name('tasks.store');
    Route::put('/projects/{project}/tasks/{task}', [ChapterController::class, 'taskSave'])->name('tasks.update');
    Route::get('/tasks', [ChapterController::class, 'tasks'])->name('tasks');
    Route::get('/calendar', [ChapterController::class, 'calendar'])->name('calendar');
    Route::post('/calendar', [ChapterController::class, 'eventSave'])->name('calendar.store');
    Route::post('/projects/{project}/documents', [ChapterController::class, 'upload'])->name('documents.store');
    Route::get('/documents/{document}/download', [ChapterController::class, 'download'])->name('documents.download');
    Route::get('/notifications', [ChapterController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/read', [ChapterController::class, 'readNotifications'])->name('notifications.read');
    Route::post('/notifications/{notification}/open', [ChapterController::class, 'openNotification'])->name('notifications.open');
    Route::get('/activity-log', [ChapterController::class, 'audit'])->name('activity-log');
    Route::get('/audit', [ChapterController::class, 'audit'])->name('audit');
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance');
    Route::post('/projects/{project}/budget', [FinanceController::class, 'budget'])->name('budget.store');
    Route::get('/ledger/export', [FinanceController::class, 'export'])->name('ledger.export');
    Route::get('/ledger', [FinanceController::class, 'ledger'])->name('ledger');
    Route::post('/ledger', [FinanceController::class, 'store'])->name('ledger.store');
    Route::post('/ledger/{entry}', [FinanceController::class, 'update'])->name('ledger.update');
    Route::get('/ledger/{entry}/receipt', [FinanceController::class, 'receipt'])->name('ledger.receipt');
    Route::get('/dues', [FinanceController::class, 'dues'])->name('dues');
    Route::get('/dues/manage', [FinanceController::class, 'dues'])->name('dues.manage');
    Route::post('/dues', [FinanceController::class, 'openDues'])->name('dues.store');
    Route::post('/dues/{due}/payment', [FinanceController::class, 'payDues'])->name('dues.payment');
    Route::get('/records/{kind}', [RecordController::class, 'index'])->name('records');
    Route::get('/records/{kind}/create', [RecordController::class, 'create'])->name('records.create');
    Route::post('/records/{kind}', [RecordController::class, 'save'])->name('records.store');
    Route::get('/records/{kind}/{id}', [RecordController::class, 'show'])->name('records.show')->whereNumber('id');
    Route::get('/records/{kind}/{id}/edit', [RecordController::class, 'edit'])->name('records.edit');
    Route::put('/records/{kind}/{id}', [RecordController::class, 'save'])->name('records.update');
    Route::post('/records/{kind}/{id}/transition', [RecordController::class, 'transition'])->name('records.transition');
    Route::get('/records/{kind}/{id}/attachment', [RecordController::class, 'attachment'])->name('records.attachment');
    Route::get('/members', [RecordController::class, 'members'])->name('members');
    Route::get('/members/create', [RecordController::class, 'memberForm'])->name('members.create');
    Route::post('/members', [RecordController::class, 'memberSave'])->name('members.store');
    Route::get('/members/{member}/edit', [RecordController::class, 'memberForm'])->name('members.edit');
    Route::put('/members/{member}', [RecordController::class, 'memberSave'])->name('members.update');
    Route::get('/account', [RecordController::class, 'account'])->name('account');
    Route::put('/account', [RecordController::class, 'accountSave'])->name('account.update');

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
