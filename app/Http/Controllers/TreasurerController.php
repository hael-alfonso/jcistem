<?php

namespace App\Http\Controllers;

use App\Support\JciDemoData;
use Illuminate\View\View;

class TreasurerController extends Controller
{
    public function dashboard(): View
    {
        $ledger = JciDemoData::ledger();
        $dues = JciDemoData::dues();
        $expenses = JciDemoData::expenses();
        $projects = JciDemoData::projects();
        $collected = array_sum(array_column(array_filter($ledger, fn ($r) => $r['type'] === 'Dues Collection'), 'amount'));
        $paidOut = array_sum(array_column(array_filter($ledger, fn ($r) => $r['type'] === 'Expense / Payment'), 'amount'));
        $approved = array_sum(array_column($projects, 'approvedBudget'));
        $used = array_sum(array_column($projects, 'usedFunds'));
        $dueLabels = ['Paid', 'Partial', 'Unpaid', 'Overdue'];
        $dueColors = ['#57BCBC', '#EFC40F', '#1F4789', '#D66A5F'];
        $expCats = array_values(array_unique(array_column($expenses, 'category')));

        return $this->page('treasurer.dashboard', 'Treasurer Dashboard', [
            'collected' => $collected,
            'paidOut' => $paidOut,
            'remaining' => max(0, $approved - $used),
            'pendingLiquidation' => JciDemoData::countBy($expenses, 'status', 'Pending'),
            'duesChart' => [
                'labels' => $dueLabels,
                'values' => array_map(fn ($s) => JciDemoData::countBy($dues, 'status', $s), $dueLabels),
                'colors' => $dueColors,
            ],
            'expenseChart' => [
                'labels' => $expCats,
                'values' => array_map(fn ($c) => array_sum(array_column(array_filter($expenses, fn ($e) => $e['category'] === $c), 'amount')), $expCats),
            ],
        ]);
    }

    public function ledger(): View
    {
        return $this->page('treasurer.ledger', 'Treasurer Ledger', [
            'rows' => JciDemoData::ledger(),
        ]);
    }

    public function expenses(): View
    {
        return $this->page('treasurer.expenses', 'Disbursements', [
            'expenses' => JciDemoData::expenses(),
        ]);
    }

    public function liquidation(): View
    {
        $pending = array_values(array_filter(JciDemoData::expenses(), fn ($e) => $e['status'] !== 'Posted'));
        $posted = array_values(array_filter(JciDemoData::expenses(), fn ($e) => $e['status'] === 'Posted'));

        return $this->page('treasurer.liquidation', 'Liquidation', [
            'pending' => $pending,
            'posted' => $posted,
        ]);
    }

    public function dues(): View
    {
        $dues = JciDemoData::dues();
        $labels = ['Paid', 'Partial', 'Unpaid', 'Overdue'];

        return $this->page('treasurer.dues', 'Member Dues Recording', [
            'dues' => $dues,
            'chart' => [
                'labels' => $labels,
                'values' => array_map(fn ($s) => JciDemoData::countBy($dues, 'status', $s), $labels),
                'colors' => ['#57BCBC', '#EFC40F', '#1F4789', '#D66A5F'],
            ],
        ]);
    }

    public function budget(): View
    {
        return $this->page('treasurer.budget', 'Budget Allocation', [
            'allocations' => JciDemoData::allocations(),
        ]);
    }

    public function utilization(): View
    {
        $projects = JciDemoData::projects();

        return $this->page('treasurer.utilization', 'Fund Utilization', [
            'projects' => $projects,
            'chart' => [
                'labels' => array_column($projects, 'title'),
                'used' => array_column($projects, 'usedFunds'),
                'remaining' => array_map(fn ($p) => max(0, $p['approvedBudget'] - $p['usedFunds']), $projects),
            ],
        ]);
    }

    public function report(): View
    {
        return $this->page('treasurer.report', 'Overall Financial Report', [
            'reports' => array_values(array_filter(JciDemoData::reports(), fn ($r) => str_contains($r['type'], 'Financial'))),
            'ledger' => JciDemoData::ledger(),
        ]);
    }

    public function notifications(): View
    {
        $items = [
            ['type' => 'Finance', 'title' => 'Pending expense EXP-1003 needs Treasurer posting.', 'time' => 'Yesterday, 3:10 PM', 'read' => false, 'href' => route('treasurer.expenses')],
            ['type' => 'Dues', 'title' => 'Two member dues records are unpaid for September.', 'time' => 'Today, 8:15 AM', 'read' => false, 'href' => route('treasurer.dues')],
            ['type' => 'Report', 'title' => 'August Overall Financial Report is ready to finalize.', 'time' => 'Sep 5, 2026', 'read' => true, 'href' => route('treasurer.report')],
        ];

        return $this->page('shared.notifications', 'Notifications', ['items' => $items]);
    }

    public function account(): View
    {
        return $this->page('shared.account', 'My Account');
    }

    private function page(string $view, string $title, array $data = []): View
    {
        return view($view, array_merge($data, [
            'pageTitle' => $title,
            'unread' => 2,
        ]));
    }
}
