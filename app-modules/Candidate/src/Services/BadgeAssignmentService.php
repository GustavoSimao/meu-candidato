<?php

namespace MeuCandidato\Candidate\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use MeuCandidato\Candidate\Models\BadgeDefinition;
use MeuCandidato\Candidate\Models\Politician;

class BadgeAssignmentService
{
    public function evaluateAll(): void
    {
        $badges = BadgeDefinition::where('is_active', true)->get();

        if ($badges->isEmpty()) {
            return;
        }

        $totalExpensesAll = DB::table('expenses')->sum('value');
        $expenseSums = DB::table('expenses')
            ->select('politician_id', DB::raw('sum(value) as total'))
            ->groupBy('politician_id')
            ->pluck('total', 'politician_id');
        $billCounts = DB::table('bills')
            ->select('author_id as politician_id', DB::raw('count(*) as total'))
            ->groupBy('author_id')
            ->pluck('total', 'politician_id');

        $expensePercentiles = collect();
        if ($totalExpensesAll > 0) {
            $ranked = DB::table('expenses')
                ->select('politician_id', DB::raw('sum(value) as total'))
                ->groupBy('politician_id')
                ->orderByDesc('total')
                ->get();
            $totalPoliticians = $ranked->count();
            foreach ($ranked as $i => $row) {
                $expensePercentiles[$row->politician_id] = round((($totalPoliticians - $i) / $totalPoliticians) * 100, 2);
            }
        }

        Politician::with(['mandates'])
            ->chunk(100, function ($politicians) use ($badges, $totalExpensesAll, $expenseSums, $billCounts, $expensePercentiles) {
                foreach ($politicians as $politician) {
                    $this->evaluatePolitician($politician, $badges, $totalExpensesAll, $expenseSums, $billCounts, $expensePercentiles);
                }
            });
    }

    public function evaluatePolitician(
        Politician $politician,
        ?iterable $badges = null,
        ?float $totalExpensesAll = null,
        ?Collection $expenseSums = null,
        ?Collection $billCounts = null,
        ?Collection $expensePercentiles = null,
    ): array {
        $badges = $badges ?? BadgeDefinition::where('is_active', true)->get();
        $assigned = [];

        $politician->loadMissing(['mandates']);

        $totalExpensesAll = $totalExpensesAll ?? DB::table('expenses')->sum('value');
        $myExpenseTotal = ($expenseSums ?? DB::table('expenses')
            ->select(DB::raw('sum(value) as total'))
            ->where('politician_id', $politician->id)
            ->pluck('total'))->get($politician->id, 0);
        $myBillCount = ($billCounts ?? DB::table('bills')
            ->select(DB::raw('count(*) as total'))
            ->where('author_id', $politician->id)
            ->pluck('total'))->get($politician->id, 0);
        $myPercentile = ($expensePercentiles ?? collect())->get($politician->id, 0) ?? 0;

        foreach ($badges as $badge) {
            if ($this->matchesRules($politician, $badge, $myPercentile, $myExpenseTotal, $myBillCount)) {
                $assigned[] = $badge->id;
            }
        }

        $politician->badges()->sync($assigned);

        return $assigned;
    }

    private function matchesRules(
        Politician $politician,
        BadgeDefinition $badge,
        float $myPercentile,
        float $myExpenseTotal,
        int $myBillCount,
    ): bool {
        $rules = $badge->rules;

        if (isset($rules['min_mandates'])) {
            if ($politician->mandates->count() < $rules['min_mandates']) {
                return false;
            }
        }

        if (isset($rules['max_mandates'])) {
            if ($politician->mandates->count() > $rules['max_mandates']) {
                return false;
            }
        }

        if (isset($rules['min_bills'])) {
            if ($myBillCount < $rules['min_bills']) {
                return false;
            }
        }

        if (isset($rules['position'])) {
            if ($politician->position !== $rules['position']) {
                return false;
            }
        }

        if (isset($rules['min_total_expenses_percentile'])) {
            if ($myPercentile < $rules['min_total_expenses_percentile']) {
                return false;
            }
        }

        if (isset($rules['max_total_expenses_percentile'])) {
            if ($myPercentile > $rules['max_total_expenses_percentile']) {
                return false;
            }
        }

        if (isset($rules['min_party_changes'])) {
            $partyChanges = $politician->mandates
                ->pluck('party_id')
                ->unique()
                ->count() - 1;
            if ($partyChanges < $rules['min_party_changes']) {
                return false;
            }
        }

        if (isset($rules['min_years_in_office'])) {
            $totalYears = $politician->mandates->sum(function ($m) {
                $start = $m->started_at;
                $end = $m->ended_at ?? now();

                return $start ? $start->diffInYears($end) : 0;
            });
            if ($totalYears < $rules['min_years_in_office']) {
                return false;
            }
        }

        if (isset($rules['education_level'])) {
            if (! in_array($politician->education, (array) $rules['education_level'])) {
                return false;
            }
        }

        return true;
    }
}
