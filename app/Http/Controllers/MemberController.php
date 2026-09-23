<?php

namespace App\Http\Controllers;

use App\Support\JciDemoData;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function dashboard(): View
    {
        $name = auth()->user()->name;
        $mine = array_values(array_filter(JciDemoData::projects(), fn ($p) => $p['chair'] === $name || $p['createdBy'] === $name));
        $due = collect(JciDemoData::dues())->firstWhere('member', $name);

        return $this->page('member.dashboard', 'Member Dashboard', [
            'projects' => $mine,
            'due' => $due,
        ]);
    }

    public function projects(): View
    {
        $name = auth()->user()->name;
        $mine = array_values(array_filter(JciDemoData::projects(), fn ($p) => $p['chair'] === $name || $p['createdBy'] === $name));

        return $this->page('member.projects', 'My Projects', ['projects' => $mine]);
    }

    public function dues(): View
    {
        $name = auth()->user()->name;
        $mine = array_values(array_filter(JciDemoData::dues(), fn ($d) => $d['member'] === $name));

        return $this->page('member.dues', 'My Member Dues', [
            'current' => $mine[0] ?? null,
            'history' => $mine,
        ]);
    }

    public function calendar(): View
    {
        return $this->page('shared.calendar', 'Calendar', [
            'events' => JciDemoData::events(),
            'cells' => JciDemoData::calendarCells(),
        ]);
    }

    public function notifications(): View
    {
        return $this->page('shared.notifications', 'Notifications', [
            'items' => [
                ['type' => 'Project', 'title' => 'Your project assignment is available in My Projects.', 'time' => 'Today, 9:00 AM', 'read' => false, 'href' => route('member.projects')],
                ['type' => 'Dues', 'title' => 'September member dues status is available.', 'time' => 'Sep 8, 2026', 'read' => true, 'href' => route('member.dues')],
            ],
        ]);
    }

    public function account(): View
    {
        return $this->page('shared.account', 'My Account');
    }

    private function page(string $view, string $title, array $data = []): View
    {
        return view($view, array_merge($data, [
            'pageTitle' => $title,
            'unread' => 1,
        ]));
    }
}
