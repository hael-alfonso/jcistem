<?php

namespace App\Support;

class JciDemoData
{
    public static function config(): array
    {
        return [
            'org' => 'JCI Carmona',
            'location' => 'Carmona City, Cavite, Philippines',
            'currentUser' => 'Mich Alfonso',
            'role' => 'Admin',
            'areas' => [
                'Individual Development',
                'Community Action',
                'International Cooperation',
                'Business & Entrepreneurship',
            ],
        ];
    }

    public static function users(): array
    {
        return [
            ['id' => 1, 'name' => 'Mich Alfonso', 'email' => 'admin@jcicarmona.org', 'role' => 'Admin', 'status' => 'Active', 'joined' => '2025-01-12', 'memberNo' => 'JCI-001'],
            ['id' => 2, 'name' => 'Juan Dela Cruz', 'email' => 'juan@jcicarmona.org', 'role' => 'BOD', 'status' => 'Active', 'joined' => '2025-02-18', 'memberNo' => 'JCI-002'],
            ['id' => 3, 'name' => 'Maria Santos', 'email' => 'maria@jcicarmona.org', 'role' => 'BOD', 'status' => 'Active', 'joined' => '2025-03-04', 'memberNo' => 'JCI-003'],
            ['id' => 4, 'name' => 'Carlo Mendoza', 'email' => 'carlo@jcicarmona.org', 'role' => 'Treasurer', 'status' => 'Active', 'joined' => '2025-01-25', 'memberNo' => 'JCI-004'],
            ['id' => 5, 'name' => 'Mark Reyes', 'email' => 'mark@jcicarmona.org', 'role' => 'General Member', 'status' => 'Pending', 'joined' => '2026-08-19', 'memberNo' => 'JCI-005'],
            ['id' => 6, 'name' => 'Ana Cruz', 'email' => 'ana@jcicarmona.org', 'role' => 'General Member', 'status' => 'Active', 'joined' => '2025-06-15', 'memberNo' => 'JCI-006'],
        ];
    }

    public static function projects(): array
    {
        return [
            [
                'id' => 1, 'title' => 'JCI Carmona Community Impact Project', 'area' => 'Community Action', 'chair' => 'Juan Dela Cruz',
                'date' => '2026-10-15', 'venue' => 'Carmona City, Cavite', 'status' => 'Ongoing', 'progress' => 72,
                'targetBudget' => 50000, 'approvedBudget' => 50000, 'usedFunds' => 11800,
                'needs' => 'Community need identified through local consultation.',
                'objectives' => 'Deliver a practical and sustainable response with community partners.',
                'beneficiaries' => 'Selected community members and partner groups.',
                'outputs' => 'Community activity delivery, volunteer participation, evidence package.',
                'outcomes' => 'Documented community results and a sustainability plan.',
                'partners' => 'Local community office and partner groups',
                'participants' => 'JCI members and volunteers',
                'createdBy' => 'Juan Dela Cruz',
            ],
            [
                'id' => 2, 'title' => 'JCI Carmona Leadership Development Program', 'area' => 'Individual Development', 'chair' => 'Maria Santos',
                'date' => '2026-11-08', 'venue' => 'Carmona City, Cavite', 'status' => 'Approved — Chair Pending', 'progress' => 48,
                'targetBudget' => 35000, 'approvedBudget' => 35000, 'usedFunds' => 3200,
                'needs' => 'Members and young leaders need practical leadership development.',
                'objectives' => 'Develop communication, project management and leadership capabilities.',
                'beneficiaries' => 'JCI members and invited young leaders.',
                'outputs' => 'Learning sessions, attendance, participant action plans.',
                'outcomes' => 'Participants identify actions they can apply after the program.',
                'partners' => 'Local Schools Network',
                'participants' => 'Members and invited youth leaders',
                'createdBy' => 'Maria Santos',
            ],
            [
                'id' => 3, 'title' => 'JCI Carmona Business & Entrepreneurship Forum', 'area' => 'Business & Entrepreneurship', 'chair' => '—',
                'date' => '2026-10-25', 'venue' => 'Carmona City, Cavite', 'status' => 'Pending Review', 'progress' => 15,
                'targetBudget' => 22000, 'approvedBudget' => 0, 'usedFunds' => 0,
                'needs' => 'Young professionals and entrepreneurs need learning and networking opportunities.',
                'objectives' => 'Create a practical learning and networking experience.',
                'beneficiaries' => 'Members, young professionals and aspiring entrepreneurs.',
                'outputs' => 'Forum sessions, networking activities, action notes.',
                'outcomes' => 'Useful connections and next-step actions.',
                'partners' => 'Prospective business community partners',
                'participants' => 'Members and invited entrepreneurs',
                'createdBy' => 'Mark Reyes',
            ],
            [
                'id' => 4, 'title' => 'JCI Carmona International Cooperation Activity', 'area' => 'International Cooperation', 'chair' => 'Ana Cruz',
                'date' => '2026-12-05', 'venue' => 'JCI Carmona Chapter', 'status' => 'Completed', 'progress' => 100,
                'targetBudget' => 28000, 'approvedBudget' => 28000, 'usedFunds' => 24100,
                'needs' => 'Members benefit from opportunities to connect and learn beyond the local chapter.',
                'objectives' => 'Build meaningful collaboration and exchange with JCI members or partners.',
                'beneficiaries' => 'JCI Carmona members and participating partners.',
                'outputs' => 'Exchange session, documentation and collaboration notes.',
                'outcomes' => 'Shared learning and documented collaboration results.',
                'partners' => 'JCI partner chapter',
                'participants' => 'JCI members and partner delegates',
                'createdBy' => 'Ana Cruz',
            ],
        ];
    }

    public static function tasks(): array
    {
        return [
            ['id' => 1, 'project' => 1, 'title' => 'Confirm community partner', 'assignee' => 'Juan Dela Cruz', 'deadline' => '2026-10-02', 'priority' => 'High', 'status' => 'Completed', 'milestone' => 'Partnership', 'notes' => 'Coordinate confirmation letter.'],
            ['id' => 2, 'project' => 1, 'title' => 'Prepare volunteer kits', 'assignee' => 'Ana Cruz', 'deadline' => '2026-10-08', 'priority' => 'Medium', 'status' => 'In Progress', 'milestone' => 'Logistics', 'notes' => 'Prepare 60 kits.'],
            ['id' => 3, 'project' => 1, 'title' => 'Coordinate venue logistics', 'assignee' => 'Mark Reyes', 'deadline' => '2026-10-11', 'priority' => 'High', 'status' => 'Pending', 'milestone' => 'Logistics', 'notes' => 'Finalize tables, chairs and sound.'],
            ['id' => 4, 'project' => 2, 'title' => 'Finalize speaker lineup', 'assignee' => 'Maria Santos', 'deadline' => '2026-10-25', 'priority' => 'Medium', 'status' => 'In Progress', 'milestone' => 'Program', 'notes' => 'Confirm two youth speakers.'],
            ['id' => 5, 'project' => 4, 'title' => 'Compile completion evidence', 'assignee' => 'Ana Cruz', 'deadline' => '2026-09-28', 'priority' => 'Low', 'status' => 'Completed', 'milestone' => 'Completion', 'notes' => 'Collect photos and signed attendance.'],
        ];
    }

    public static function lois(): array
    {
        return [
            ['id' => 1, 'ref' => 'LOI-2026-014', 'project' => 1, 'partner' => 'Carmona Community Office', 'subject' => 'Partnership and venue support', 'date' => '2026-09-19', 'status' => 'Pending', 'version' => '1.0', 'purpose' => 'Request partnership and venue support for community outreach.'],
            ['id' => 2, 'ref' => 'LOI-2026-013', 'project' => 2, 'partner' => 'Local Schools Network', 'subject' => 'Youth leadership participation', 'date' => '2026-09-17', 'status' => 'Approved', 'version' => '2.0', 'purpose' => 'Invite schools to participate in the youth leadership forum.'],
            ['id' => 3, 'ref' => 'LOI-2026-009', 'project' => 4, 'partner' => 'JCI Partner Chapter', 'subject' => 'International cooperation activity', 'date' => '2026-08-28', 'status' => 'Approved', 'version' => '1.2', 'purpose' => 'Coordinate exchange participation and shared activity schedule.'],
        ];
    }

    public static function expenses(): array
    {
        return [
            ['id' => 1, 'project' => 1, 'date' => '2026-09-12', 'category' => 'Supplies', 'description' => 'Volunteer materials', 'amount' => 6800, 'requester' => 'Juan Dela Cruz', 'ref' => 'EXP-1001', 'status' => 'Posted', 'receipt' => true],
            ['id' => 2, 'project' => 1, 'date' => '2026-09-15', 'category' => 'Venue', 'description' => 'Venue reservation', 'amount' => 5000, 'requester' => 'Juan Dela Cruz', 'ref' => 'EXP-1002', 'status' => 'Posted', 'receipt' => true],
            ['id' => 3, 'project' => 2, 'date' => '2026-09-16', 'category' => 'Materials', 'description' => 'Printing and certificates', 'amount' => 3200, 'requester' => 'Maria Santos', 'ref' => 'EXP-1003', 'status' => 'Pending', 'receipt' => true],
            ['id' => 4, 'project' => 4, 'date' => '2026-08-30', 'category' => 'Program', 'description' => 'Exchange activity materials', 'amount' => 24100, 'requester' => 'Ana Cruz', 'ref' => 'EXP-0991', 'status' => 'Posted', 'receipt' => true],
        ];
    }

    public static function allocations(): array
    {
        return [
            ['id' => 1, 'project' => 1, 'category' => 'Program & Community Activities', 'allocated' => 30000, 'used' => 6800],
            ['id' => 2, 'project' => 1, 'category' => 'Logistics & Transport', 'allocated' => 12000, 'used' => 5000],
            ['id' => 3, 'project' => 1, 'category' => 'Contingency', 'allocated' => 8000, 'used' => 0],
            ['id' => 4, 'project' => 2, 'category' => 'Program Expenses', 'allocated' => 22000, 'used' => 3200],
            ['id' => 5, 'project' => 2, 'category' => 'Speakers & Venue', 'allocated' => 13000, 'used' => 0],
            ['id' => 6, 'project' => 4, 'category' => 'International Program', 'allocated' => 28000, 'used' => 24100],
        ];
    }

    public static function ledger(): array
    {
        return [
            ['id' => 1, 'date' => '2026-09-12', 'ref' => 'TRX-1001', 'type' => 'Expense / Payment', 'description' => 'Volunteer materials', 'category' => 'Supplies', 'payer' => 'Community supplier', 'amount' => 6800, 'status' => 'Posted'],
            ['id' => 2, 'date' => '2026-09-15', 'ref' => 'TRX-1002', 'type' => 'Expense / Payment', 'description' => 'Venue reservation', 'category' => 'Venue', 'payer' => 'Venue partner', 'amount' => 5000, 'status' => 'Posted'],
            ['id' => 3, 'date' => '2026-09-03', 'ref' => 'DUES-0903', 'type' => 'Dues Collection', 'description' => 'September member dues', 'category' => 'Member Dues', 'payer' => 'Ana Cruz', 'amount' => 500, 'status' => 'Posted'],
            ['id' => 4, 'date' => '2026-09-07', 'ref' => 'DUES-0907', 'type' => 'Dues Collection', 'description' => 'September member dues', 'category' => 'Member Dues', 'payer' => 'Juan Dela Cruz', 'amount' => 500, 'status' => 'Posted'],
            ['id' => 5, 'date' => '2026-08-30', 'ref' => 'TRX-0991', 'type' => 'Expense / Payment', 'description' => 'International cooperation activity materials', 'category' => 'Program', 'payer' => 'Program suppliers', 'amount' => 24100, 'status' => 'Posted'],
        ];
    }

    public static function dues(): array
    {
        return [
            ['id' => 1, 'member' => 'Mich Alfonso', 'period' => 'September 2026', 'due' => '2026-09-15', 'expected' => 500, 'paid' => 500, 'paymentDate' => '2026-09-08', 'status' => 'Paid', 'ref' => 'DUES-0901'],
            ['id' => 2, 'member' => 'Juan Dela Cruz', 'period' => 'September 2026', 'due' => '2026-09-15', 'expected' => 500, 'paid' => 500, 'paymentDate' => '2026-09-07', 'status' => 'Paid', 'ref' => 'DUES-0907'],
            ['id' => 3, 'member' => 'Maria Santos', 'period' => 'September 2026', 'due' => '2026-09-15', 'expected' => 500, 'paid' => 250, 'paymentDate' => '2026-09-14', 'status' => 'Partial', 'ref' => 'DUES-0910'],
            ['id' => 4, 'member' => 'Carlo Mendoza', 'period' => 'September 2026', 'due' => '2026-09-15', 'expected' => 500, 'paid' => 0, 'paymentDate' => '', 'status' => 'Overdue', 'ref' => ''],
            ['id' => 5, 'member' => 'Mark Reyes', 'period' => 'September 2026', 'due' => '2026-09-15', 'expected' => 500, 'paid' => 0, 'paymentDate' => '', 'status' => 'Unpaid', 'ref' => ''],
            ['id' => 6, 'member' => 'Ana Cruz', 'period' => 'September 2026', 'due' => '2026-09-15', 'expected' => 500, 'paid' => 500, 'paymentDate' => '2026-09-03', 'status' => 'Paid', 'ref' => 'DUES-0903'],
        ];
    }

    public static function reports(): array
    {
        return [
            ['id' => 1, 'project' => 4, 'type' => 'Project Completion Report', 'title' => 'International Cooperation Activity — Completion Report', 'submittedBy' => 'Ana Cruz', 'submitted' => '2026-09-10 15:40', 'status' => 'Submitted to Admin', 'pages' => 9],
            ['id' => 2, 'project' => 4, 'type' => 'Overall Financial Report', 'title' => 'Overall Financial Report — August 2026', 'submittedBy' => 'Carlo Mendoza', 'submitted' => '2026-09-05 17:20', 'status' => 'Submitted to Admin', 'pages' => 12],
            ['id' => 3, 'project' => 1, 'type' => 'Project Progress Report', 'title' => 'Community Impact Project — September Progress', 'submittedBy' => 'Juan Dela Cruz', 'submitted' => '2026-09-18 11:15', 'status' => 'Draft', 'pages' => 5],
        ];
    }

    public static function documents(): array
    {
        return [
            ['id' => 1, 'project' => 1, 'name' => 'Approved Project Proposal.pdf', 'type' => 'Project Proposal', 'version' => '1.0', 'updated' => '2026-09-06'],
            ['id' => 2, 'project' => 1, 'name' => 'Partner Confirmation.pdf', 'type' => 'Supporting Document', 'version' => '1.1', 'updated' => '2026-09-19'],
            ['id' => 3, 'project' => 1, 'name' => 'Venue Coordination.pdf', 'type' => 'Project Document', 'version' => '1.0', 'updated' => '2026-09-20'],
            ['id' => 4, 'project' => 2, 'name' => 'Leadership Program Proposal.pdf', 'type' => 'Project Proposal', 'version' => '1.0', 'updated' => '2026-09-08'],
            ['id' => 5, 'project' => 4, 'name' => 'Completion Evidence Pack.pdf', 'type' => 'Completion Evidence', 'version' => '1.2', 'updated' => '2026-09-11'],
        ];
    }

    public static function events(): array
    {
        return [
            ['id' => 1, 'title' => 'Community Outreach Program', 'date' => '2026-10-15', 'time' => '08:00', 'type' => 'Project', 'project' => 1, 'notes' => 'Carmona City Hall'],
            ['id' => 2, 'title' => 'BOD Project Review', 'date' => '2026-10-05', 'time' => '18:00', 'type' => 'Meeting', 'project' => null, 'notes' => 'Chapter meeting room'],
            ['id' => 3, 'title' => 'Youth Leadership Forum', 'date' => '2026-11-08', 'time' => '09:00', 'type' => 'Project', 'project' => 2, 'notes' => 'Carmona Sports Complex'],
            ['id' => 4, 'title' => 'Completion Review — International Cooperation', 'date' => '2026-09-29', 'time' => '16:00', 'type' => 'Review', 'project' => 4, 'notes' => 'Admin review of completion records'],
        ];
    }

    public static function notifications(): array
    {
        return [
            ['id' => 1, 'type' => 'Project', 'title' => 'Community Impact Project reached 72% progress.', 'time' => 'Today, 9:20 AM', 'read' => false, 'href' => '/admin/projects/1'],
            ['id' => 2, 'type' => 'Approval', 'title' => 'Business & Entrepreneurship Forum is awaiting Admin review.', 'time' => 'Today, 8:40 AM', 'read' => false, 'href' => '/admin/projects?filter=pending'],
            ['id' => 3, 'type' => 'Finance', 'title' => 'Youth Leadership Forum has a pending expense record to monitor.', 'time' => 'Yesterday, 3:10 PM', 'read' => false, 'href' => '/admin/finance/expenses'],
            ['id' => 4, 'type' => 'Report', 'title' => 'International Cooperation completion report was submitted to Admin.', 'time' => 'Sep 10, 2026', 'read' => true, 'href' => '/admin/reports'],
            ['id' => 5, 'type' => 'Member', 'title' => 'Mark Reyes submitted a member registration.', 'time' => 'Sep 19, 2026', 'read' => true, 'href' => '/admin/members/registration'],
        ];
    }

    public static function audit(): array
    {
        return [
            ['id' => 1, 'actor' => 'Juan Dela Cruz', 'action' => 'Submitted Project', 'object' => 'JCI Carmona Community Impact Project', 'project' => 1, 'time' => '2026-09-18 11:15', 'remarks' => 'Submitted for Admin review.'],
            ['id' => 2, 'actor' => 'Mich Alfonso', 'action' => 'Moved Review Stage', 'object' => 'JCI Carmona Community Impact Project', 'project' => 1, 'time' => '2026-09-18 13:05', 'remarks' => 'Admin review started.'],
            ['id' => 3, 'actor' => 'Carlo Mendoza', 'action' => 'Recorded Expense', 'object' => 'EXP-1002', 'project' => 1, 'time' => '2026-09-15 17:30', 'remarks' => 'Receipt verified.'],
            ['id' => 4, 'actor' => 'Ana Cruz', 'action' => 'Submitted Report', 'object' => 'Completion Report', 'project' => 4, 'time' => '2026-09-10 15:40', 'remarks' => 'Sent to Admin.'],
        ];
    }

    public static function project(int|string $id): ?array
    {
        foreach (self::projects() as $project) {
            if ((int) $project['id'] === (int) $id) {
                return $project;
            }
        }

        return null;
    }

    public static function money(float|int|null $n): string
    {
        return '₱'.number_format((float) $n, 0);
    }

    public static function date(?string $value): string
    {
        if (! $value) {
            return '—';
        }

        $time = strtotime($value);

        return $time ? date('M j, Y', $time) : $value;
    }

    public static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', $name) ?: [];

        return strtoupper(substr($parts[0] ?? '', 0, 1).substr($parts[1] ?? '', 0, 1));
    }

    public static function statusClass(?string $status): string
    {
        $status = (string) $status;
        if (preg_match('/Completed|Approved|Paid|Posted|Active|Submitted to Admin/', $status)) {
            return 'positive';
        }
        if (preg_match('/Ongoing|In Progress|Pending|Partial|Review/', $status)) {
            return 'info';
        }
        if (preg_match('/Overdue|Rejected|Returned|Unpaid/', $status)) {
            return 'danger';
        }
        if (preg_match('/Archived|Draft|Waived/', $status)) {
            return 'muted';
        }

        return 'neutral';
    }

    public static function whereProject(array $items, int|string $id): array
    {
        return array_values(array_filter($items, fn ($item) => (int) ($item['project'] ?? 0) === (int) $id));
    }

    public static function countBy(array $items, string $key, string $value): int
    {
        return count(array_filter($items, fn ($item) => ($item[$key] ?? '') === $value));
    }

    public static function calendarCells(int $year = 2026, int $month = 9): array
    {
        $first = mktime(0, 0, 0, $month, 1, $year);
        $daysInMonth = (int) date('t', $first);
        $startWeekday = (int) date('w', $first);
        $cells = [];

        for ($i = 0; $i < $startWeekday; $i++) {
            $cells[] = null;
        }

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $iso = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $cells[] = [
                'day' => $d,
                'iso' => $iso,
                'events' => array_values(array_filter(self::events(), fn ($e) => $e['date'] === $iso)),
            ];
        }

        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }

        return $cells;
    }
}
