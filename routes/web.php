<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BodController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\TreasurerController;
use App\Support\WorkspaceNav;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(WorkspaceNav::home(auth()->user()->role));
    }

    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/projects', [AdminController::class, 'projects'])->name('projects');
    Route::get('/projects/create', [AdminController::class, 'createProject'])->name('projects.create');
    Route::get('/projects/{id}', [AdminController::class, 'showProject'])->name('projects.show')->whereNumber('id');
    Route::get('/tasks', [AdminController::class, 'tasks'])->name('tasks');
    Route::get('/loi', [AdminController::class, 'loi'])->name('loi');
    Route::get('/calendar', [AdminController::class, 'calendar'])->name('calendar');
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::get('/finance', [AdminController::class, 'finance'])->name('finance');
    Route::get('/finance/budget', [AdminController::class, 'financeBudget'])->name('finance.budget');
    Route::get('/finance/utilization', [AdminController::class, 'financeUtilization'])->name('finance.utilization');
    Route::get('/finance/expenses', [AdminController::class, 'financeExpenses'])->name('finance.expenses');
    Route::get('/finance/reports', [AdminController::class, 'financeReports'])->name('finance.reports');
    Route::get('/members', [AdminController::class, 'members'])->name('members');
    Route::get('/members/registration', [AdminController::class, 'memberRegistration'])->name('members.registration');
    Route::get('/dues', [AdminController::class, 'dues'])->name('dues');
    Route::get('/notifications', [AdminController::class, 'notifications'])->name('notifications');
    Route::get('/account', [AdminController::class, 'account'])->name('account');
    Route::get('/audit', [AdminController::class, 'audit'])->name('audit');
});

Route::middleware(['auth', 'role:treasurer'])->prefix('treasurer')->name('treasurer.')->group(function () {
    Route::get('/', [TreasurerController::class, 'dashboard'])->name('dashboard');
    Route::get('/ledger', [TreasurerController::class, 'ledger'])->name('ledger');
    Route::get('/expenses', [TreasurerController::class, 'expenses'])->name('expenses');
    Route::get('/liquidation', [TreasurerController::class, 'liquidation'])->name('liquidation');
    Route::get('/dues', [TreasurerController::class, 'dues'])->name('dues');
    Route::get('/budget', [TreasurerController::class, 'budget'])->name('budget');
    Route::get('/utilization', [TreasurerController::class, 'utilization'])->name('utilization');
    Route::get('/report', [TreasurerController::class, 'report'])->name('report');
    Route::get('/notifications', [TreasurerController::class, 'notifications'])->name('notifications');
    Route::get('/account', [TreasurerController::class, 'account'])->name('account');
});

Route::middleware(['auth', 'role:bod'])->prefix('bod')->name('bod.')->group(function () {
    Route::get('/', [BodController::class, 'dashboard'])->name('dashboard');
    Route::get('/projects', [BodController::class, 'projects'])->name('projects');
    Route::get('/reports', [BodController::class, 'reports'])->name('reports');
    Route::get('/calendar', [BodController::class, 'calendar'])->name('calendar');
    Route::get('/notifications', [BodController::class, 'notifications'])->name('notifications');
    Route::get('/account', [BodController::class, 'account'])->name('account');
});

Route::middleware(['auth', 'role:member'])->prefix('member')->name('member.')->group(function () {
    Route::get('/', [MemberController::class, 'dashboard'])->name('dashboard');
    Route::get('/projects', [MemberController::class, 'projects'])->name('projects');
    Route::get('/dues', [MemberController::class, 'dues'])->name('dues');
    Route::get('/calendar', [MemberController::class, 'calendar'])->name('calendar');
    Route::get('/notifications', [MemberController::class, 'notifications'])->name('notifications');
    Route::get('/account', [MemberController::class, 'account'])->name('account');
});
