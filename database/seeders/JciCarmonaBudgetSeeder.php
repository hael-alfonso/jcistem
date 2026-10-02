<?php

namespace Database\Seeders;

use App\Models\BudgetAllocation;
use App\Models\LedgerEntry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class JciCarmonaBudgetSeeder extends Seeder
{
    public function run(): void
    {
        $recorder = User::where('role', 'treasurer')->orderBy('id')->first()
            ?? User::where('role', 'admin')->orderBy('id')->first();
        if (!$recorder) return;

        // Local demonstration figures: 70,000 planned across six projects, 40,000 used.
        $plans = [
            'JCI-2026-LAPIS' => ['Learning materials' => 18000, 'Volunteer support' => 12000, 'Learner support' => 9000, 'Transport and logistics' => 7000, 'Monitoring' => 4000],
            'JCI-2025-REDHEART' => ['Youth dialogue materials' => 3000],
            'JCI-2026-IVY' => ['Forum participation' => 2000],
            'JCI-2026-LAPIS-FAMILY' => ['Family learning sessions' => 5000],
            'JCI-2026-YOUTH-DIALOGUE' => ['Workshop materials' => 5000],
            'JCI-2026-VOLUNTEER-TOOLKIT' => ['Toolkit preparation' => 5000],
        ];
        $used = [
            'JCI-2026-LAPIS' => ['Learning materials' => 18000, 'Volunteer support' => 12000],
            'JCI-2025-REDHEART' => ['Youth dialogue materials' => 3000],
            'JCI-2026-IVY' => ['Forum participation' => 2000],
            'JCI-2026-VOLUNTEER-TOOLKIT' => ['Toolkit preparation' => 5000],
        ];

        foreach ($plans as $reference => $categories) {
            $project = Project::where('reference', $reference)->first();
            if (!$project) continue;
            if (in_array((float) $project->proposed_budget, [0.0, 15000.0, 20000.0, 25000.0, 50000.0], true)) {
                $project->update(['proposed_budget' => array_sum($categories)]);
            }
            BudgetAllocation::where('project_id', $project->id)
                ->where('approved_by', 'Sample planning allocation - verify')->delete();
            foreach ($categories as $category => $amount) {
                BudgetAllocation::firstOrCreate(
                    ['project_id' => $project->id, 'category' => $category],
                    ['amount' => $amount, 'approved_by' => 'Chapter budget plan', 'approved_on' => null,
                     'remarks' => 'Demonstration budget estimate.', 'recorded_by' => $recorder->id]
                );
            }
            foreach ($used[$reference] ?? [] as $category => $amount) {
                $ledgerReference = 'DEMO-'.$reference.'-'.substr(md5($category), 0, 8);
                LedgerEntry::firstOrCreate(
                    ['reference' => $ledgerReference],
                    ['project_id' => $project->id, 'recorded_by' => $recorder->id,
                     'transaction_date' => $project->starts_on?->toDateString() ?? '2026-09-01',
                     'type' => 'Expense', 'direction' => 'debit', 'category' => $category,
                     'account' => 'Chapter project funds', 'counterparty' => 'Project delivery',
                     'payment_method' => 'Other', 'description' => 'Demonstration project spending: '.$category,
                     'amount' => $amount, 'status' => 'Posted', 'liquidation_status' => 'Pending',
                     'remarks' => 'Demonstration figure; replace with verified chapter records.']
                );
            }
        }
    }
}