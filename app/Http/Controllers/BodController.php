<?php

namespace App\Http\Controllers;

use App\Support\JciDemoData;
use Illuminate\View\View;

class BodController extends Controller
{
    public function dashboard(): View
    {
        $projects = JciDemoData::projects();
        $pending = array_values(array_filter($projects, fn ($p) => str_contains($p['status'], 'Review') || str_contains($p['status'], 'BOD')));

        return $this->page('bod.dashboard', 'BOD Dashboard', [
            'pending' => $pending,
            'ongoing' => JciDemoData::countBy($projects, 'status', 'Ongoing'),
            'completed' => JciDemoData::countBy($projects, 'status', 'Completed'),
            'reports' => JciDemoData::countBy(JciDemoData::reports(), 'status', 'Submitted to Admin'),
        ]);
    }

    public function projects(): View
    {
        return $this->page('bod.projects', 'Project Review', [
            'projects' => JciDemoData::projects(),
        ]);
    }

    public function reports(): View
    {
        return $this->page('bod.reports', 'Reports', [
            'reports' => JciDemoData::reports(),
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
                ['type' => 'Approval', 'title' => 'Business & Entrepreneurship Forum is waiting for BOD review.', 'time' => 'Today, 8:40 AM', 'read' => false, 'href' => route('bod.projects')],
                ['type' => 'Meeting', 'title' => 'BOD project review is scheduled for Oct 5, 2026.', 'time' => 'Yesterday', 'read' => true, 'href' => route('bod.calendar')],
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
