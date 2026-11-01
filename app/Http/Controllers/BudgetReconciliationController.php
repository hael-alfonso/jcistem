<?php

namespace App\Http\Controllers;

use App\Models\{BudgetAllocation, LedgerEntry, Project};
use App\Services\ChapterService;
use App\Support\{Money, Permissions, ProjectBudget};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\{Rule, ValidationException};

class BudgetReconciliationController extends Controller
{
    private function authorizeTreasurer(Request $request): void
    {
        abort_unless(Permissions::allows($request->user(), Permissions::FINANCE_MANAGE), 403);
    }

    private function active(Project $project): void
    {
        abort_unless(in_array($project->status, ['Approved', 'Plan Locked', 'Ongoing'], true), 422, 'Closed or reporting projects cannot be reclassified.');
    }

    public function plan(Request $request, Project $project)
    {
        $this->authorizeTreasurer($request);
        $data = $request->validate([
            'budget_items' => 'required|array|min:1|max:50',
            'budget_items.*.category' => ['required', Rule::in(array_keys(ProjectBudget::CATEGORIES))],
            'budget_items.*.description' => 'required|string|max:255',
            'budget_items.*.quantity' => 'required|numeric|decimal:0,2|min:0.01|max:999999.99',
            'budget_items.*.unit_cost' => 'required|numeric|decimal:0,2|min:0|max:999999999.99',
        ]);
        DB::transaction(function () use ($project, $request, $data) {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            $this->active($project);
            abort_unless(empty($project->budget_items), 422, 'This project already has an itemized plan.');
            abort_unless($project->allocations()->whereNull('budget_item_id')->where('amount', '>', 0)->exists()
                || $project->transactions()->where('status', 'Posted')->where('direction', 'debit')->whereNull('budget_item_id')->exists(),
                422, 'Historical reconciliation is available only for projects with older unlinked finance records.');
            $items = ProjectBudget::normalize($data['budget_items']);
            if (Money::cents(ProjectBudget::total($items)) !== Money::cents($project->proposed_budget)) {
                throw ValidationException::withMessages(['budget_items' => 'The historical breakdown must exactly equal the approved total budget.']);
            }
            $before = $project->toArray();
            $project->budget_items = $items;
            $project->save();
            ChapterService::audit('legacy_budget_breakdown_recorded', $project, $before, 'Historical classification only; approved budget total was not changed.');
        });
        return back()->with('success', 'Historical budget lines recorded. Match earlier allocations and expenses to these lines next.');
    }

    public function allocation(Request $request, Project $project, BudgetAllocation $allocation)
    {
        $this->authorizeTreasurer($request);
        $data = $request->validate(['lines' => 'required|array|min:1', 'lines.*' => 'required|numeric|decimal:0,2|min:0|max:999999999.99']);
        DB::transaction(function () use ($project, $allocation, $request, $data) {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            $this->active($project);
            $allocation = BudgetAllocation::lockForUpdate()->findOrFail($allocation->id);
            abort_unless($allocation->project_id === $project->id && !$allocation->budget_item_id && Money::cents($allocation->amount) > 0, 404);
            $items = collect($project->budget_items ?? [])->keyBy('id');
            if ($items->isEmpty()) throw ValidationException::withMessages(['lines' => 'Record the approved expense breakdown first.']);
            $parts = collect($data['lines'])->filter(fn ($amount) => Money::cents($amount) > 0);
            if ($parts->isEmpty() || $parts->sum(fn ($amount) => Money::cents($amount)) !== Money::cents($allocation->amount)) {
                throw ValidationException::withMessages(['lines' => 'The split must equal the complete original allocation, down to the cent.']);
            }
            foreach ($parts as $id => $amount) if (!$items->has($id)) throw ValidationException::withMessages(['lines' => 'Choose only expense lines in this project.']);
            $before = $allocation->toArray();
            $originalAmount = $allocation->amount;
            $allocation->update(['amount' => 0, 'legacy_original_amount' => $originalAmount, 'reconciled_at' => now(), 'reconciled_by' => $request->user()->id]);
            $reuseOriginal = true;
            foreach ($parts as $id => $amount) {
                $item = $items[$id];
                $existing = BudgetAllocation::where('project_id', $project->id)->where('budget_item_id', $id)->lockForUpdate()->first();
                $newTotal = Money::cents($existing?->amount ?? 0) + Money::cents($amount);
                if ($newTotal > Money::cents($item['amount'])) throw ValidationException::withMessages(['lines' => 'A mapped allocation exceeds its approved expense line.']);
                if ($existing) {
                    $prior = $existing->toArray();
                    $existing->update(['amount' => Money::decimal($newTotal)]);
                    ChapterService::audit('legacy_allocation_merged', $existing, $prior, 'From legacy allocation #'.$allocation->id);
                } elseif ($reuseOriginal) {
                    $allocation->update(['budget_item_id' => $id, 'budget_item_description' => $item['description'], 'category' => ProjectBudget::CATEGORIES[$item['category']] ?? 'Other expense', 'amount' => $amount]);
                    $reuseOriginal = false;
                } else {
                    $created = BudgetAllocation::create([
                        'project_id' => $project->id, 'budget_item_id' => $id,
                        'budget_item_description' => $item['description'],
                        'category' => ProjectBudget::CATEGORIES[$item['category']] ?? 'Other expense',
                        'amount' => $amount, 'approved_by' => $allocation->approved_by,
                        'approved_on' => $allocation->approved_on, 'remarks' => $allocation->remarks,
                        'recorded_by' => $request->user()->id, 'legacy_source_id' => $allocation->id,
                        'reconciled_at' => now(), 'reconciled_by' => $request->user()->id,
                    ]);
                    ChapterService::audit('legacy_allocation_part_created', $created, [], 'From legacy allocation #'.$allocation->id);
                }
            }
            ChapterService::audit('legacy_allocation_reconciled', $allocation, $before, 'Original total PHP '.$originalAmount.' split across approved lines.');
        });
        return back()->with('success', 'Earlier allocation matched to specific expense lines without changing the project total.');
    }

    public function entry(Request $request, Project $project, LedgerEntry $entry)
    {
        $this->authorizeTreasurer($request);
        $data = $request->validate(['budget_item_id' => 'required|uuid']);
        DB::transaction(function () use ($project, $entry, $request, $data) {
            $project = Project::lockForUpdate()->findOrFail($project->id);
            $this->active($project);
            $entry = LedgerEntry::lockForUpdate()->findOrFail($entry->id);
            abort_unless($entry->project_id === $project->id && $entry->status === 'Posted' && $entry->direction === 'debit' && !$entry->budget_item_id, 404);
            $item = collect($project->budget_items ?? [])->firstWhere('id', $data['budget_item_id']);
            if (!$item) throw ValidationException::withMessages(['budget_item_id' => 'Choose an expense line from this project.']);
            $allocation = $project->allocations()->where('budget_item_id', $data['budget_item_id'])->first();
            if (!$allocation) throw ValidationException::withMessages(['budget_item_id' => 'Map the earlier allocation before mapping this expense.']);
            $spent = Money::cents($project->transactions()->where('budget_item_id', $data['budget_item_id'])->where('status', 'Posted')->where('direction', 'debit')->sum('amount'));
            if ($spent + Money::cents($entry->amount) > Money::cents($allocation->amount)) throw ValidationException::withMessages(['budget_item_id' => 'This line does not have enough allocated funds for the historical expense.']);
            $before = $entry->toArray();
            $entry->update(['budget_item_id' => $data['budget_item_id'], 'category' => ProjectBudget::CATEGORIES[$item['category']] ?? 'Other expense']);
            ChapterService::audit('legacy_expense_reconciled', $entry, $before, 'Linked to approved expense: '.$item['description']);
        });
        return back()->with('success', 'Earlier posted expense linked to its approved budget line.');
    }
}
