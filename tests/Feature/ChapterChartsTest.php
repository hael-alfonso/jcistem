<?php

namespace Tests\Feature;

use App\Models\{BudgetAllocation, LedgerEntry, Project, User};
use App\Support\ChapterCharts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChapterChartsTest extends TestCase
{
    use RefreshDatabase;

    public function test_charts_count_visible_projects_and_only_posted_expenses_for_approved_projects(): void
    {
        $owner = User::factory()->create();
        $approved = Project::create([
            'created_by' => $owner->id,
            'title' => 'Approved project',
            'area' => 'Community Impact',
            'status' => 'Approved',
        ]);
        $draft = Project::create([
            'created_by' => $owner->id,
            'title' => 'Draft project',
            'area' => 'Community Impact',
            'status' => 'Draft Concept',
        ]);

        BudgetAllocation::create(['project_id' => $approved->id, 'category' => 'Supplies', 'amount' => 1000]);
        BudgetAllocation::create(['project_id' => $draft->id, 'category' => 'Supplies', 'amount' => 9000]);

        $previousMonth = today()->startOfMonth()->subMonth()->addDays(4)->toDateString();
        LedgerEntry::create(['reference' => 'CHART-1', 'project_id' => $approved->id, 'direction' => 'debit', 'status' => 'Posted', 'amount' => 250, 'transaction_date' => today()]);
        LedgerEntry::create(['reference' => 'CHART-2', 'project_id' => $approved->id, 'direction' => 'debit', 'status' => 'Posted', 'amount' => 100, 'transaction_date' => $previousMonth]);
        LedgerEntry::create(['reference' => 'CHART-3', 'project_id' => $approved->id, 'direction' => 'debit', 'status' => 'Void', 'amount' => 400, 'transaction_date' => today()]);
        LedgerEntry::create(['reference' => 'CHART-4', 'project_id' => $approved->id, 'direction' => 'credit', 'status' => 'Posted', 'amount' => 600, 'transaction_date' => today()]);
        LedgerEntry::create(['reference' => 'CHART-5', 'project_id' => $draft->id, 'direction' => 'debit', 'status' => 'Posted', 'amount' => 500, 'transaction_date' => today()]);

        $mix = ChapterCharts::projectMix(collect([$approved, $draft]));
        $this->assertSame(2, $mix['total']);
        $this->assertSame(1, $mix['statuses']['Approved']);
        $this->assertSame(1, $mix['statuses']['Draft Concept']);
        $this->assertSame(2, $mix['areas']['Community Impact']);

        $finance = ChapterCharts::finances(Project::whereIn('status', Project::APPROVED)->get());
        $this->assertSame(1000.0, $finance['allocated']);
        $this->assertSame(350.0, $finance['spent']);
        $this->assertSame(650.0, $finance['rows']->first()['remaining']);
        $this->assertSame(35.0, $finance['rows']->first()['utilization']);
        $this->assertCount(6, $finance['months']);
        $this->assertSame(250.0, $finance['months']->last()['amount']);
        $this->assertSame(100.0, $finance['months']->get(4)['amount']);
    }
}
