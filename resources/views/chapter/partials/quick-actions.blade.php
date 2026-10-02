@php
    $quickActions = match (auth()->user()->role) {
        'admin' => [
            ['Review projects', 'Open concepts and proposals waiting for a decision.', 'projects', ['filter' => 'review'], 'briefcase'],
            ['Register a member', 'Add an account and assign chapter access.', 'members.create', [], 'userPlus'],
            ['Financial overview', 'See budgets and spending across projects.', 'finance', [], 'chart'],
        ],
        'treasurer' => [
            ['Treasurer ledger', 'Record and review chapter transactions.', 'ledger', [], 'wallet'],
            ['Manage member dues', 'Open dues and record payments.', 'dues.manage', [], 'receipt'],
            ['Financial overview', 'See budgets and spending by project.', 'finance', [], 'chart'],
        ],
        'bod' => [
            ['Review projects', 'See concepts and proposals awaiting review.', 'projects', ['filter' => 'review'], 'briefcase'],
            ['Reports', 'Read project and financial reports.', 'records', ['kind' => 'reports'], 'report'],
            ['Calendar', 'See upcoming chapter activities.', 'calendar', [], 'calendar'],
        ],
        default => [
            ['My projects', 'Find projects you lead or contribute to.', 'projects', ['filter' => 'mine'], 'briefcase'],
            ['My tasks', 'See what needs your attention next.', 'tasks', ['mine' => 1], 'checklist'],
            ['My member dues', 'Review your balance and payment records.', 'dues', [], 'receipt'],
        ],
    };
@endphp
<section class="quick-start" aria-labelledby="quick-start-title">
    <div class="quick-start-heading">
        <div><span class="eyebrow">START HERE</span><h2 id="quick-start-title">What would you like to do?</h2></div>
        <span>Common actions for your role</span>
    </div>
    <div class="quick-start-grid">
        @foreach($quickActions as [$label, $description, $route, $params, $icon])
            <a class="quick-action" href="{{ route($route, $params) }}">
                <span class="quick-action-icon"><x-icon :name="$icon"/></span>
                <span><strong>{{ $label }}</strong><small>{{ $description }}</small></span>
                <span class="quick-action-arrow" aria-hidden="true">→</span>
            </a>
        @endforeach
    </div>
</section>
