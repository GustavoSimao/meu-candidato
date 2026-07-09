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

        $percentileCache = DB::table('expenses')->sum('value');
        $expenseSums = DB::table('expenses')
            ->select('politician_id', DB::raw('sum(value) as total'))
            ->groupBy('politician_id')
            ->pluck('total', 'politician_id');
        $billCounts = DB::table('bills')
            ->select('author_id as politician_id', DB::raw('count(*) as total'))
            ->groupBy('author_id')
            ->pluck('total', 'politician_id');

        Politician::with(['mandates'])
            ->chunk(100, function ($politicians) use ($badges, $percentileCache, $expenseSums, $billCounts) {
                foreach ($politicians as $politician) {
                    $this->evaluatePolitician($politician, $badges, $percentileCache, $expenseSums, $billCounts);
                }
            });
    }

    public function evaluatePolitician(
        Politician $politician,
        ?iterable $badges = null,
        ?float $percentileCache = null,
        ?Collection $expenseSums = null,
        ?Collection $billCounts = null,
    ): array {
        $badges = $badges ?? BadgeDefinition::where('is_active', true)->get();
        $assigned = [];

        $politician->loadMissing(['mandates']);

        $totalExpensesAll = $percentileCache ?? DB::table('expenses')->sum('value');
        $myExpenseTotal = ($expenseSums ?? DB::table('expenses')
            ->select(DB::raw('sum(value) as total'))
            ->where('politician_id', $politician->id)
            ->pluck('total'))->get($politician->id, 0);
        $myBillCount = ($billCounts ?? DB::table('bills')
            ->select(DB::raw('count(*) as total'))
            ->where('author_id', $politician->id)
            ->pluck('total'))->get($politician->id, 0);

        foreach ($badges as $badge) {
            if ($this->matchesRules($politician, $badge, $totalExpensesAll, $myExpenseTotal, $myBillCount)) {
                $assigned[] = $badge->id;
            }
        }

        $politician->badges()->sync($assigned);

        return $assigned;
    }

    private function matchesRules(
        Politician $politician,
        BadgeDefinition $badge,
        float $totalExpensesAll,
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
            $percentile = $this->calculateExpensePercentile($myExpenseTotal, $totalExpensesAll);
            if ($percentile < $rules['min_total_expenses_percentile']) {
                return false;
            }
        }

        if (isset($rules['max_total_expenses_percentile'])) {
            $percentile = $this->calculateExpensePercentile($myExpenseTotal, $totalExpensesAll);
            if ($percentile > $rules['max_total_expenses_percentile']) {
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

    private function calculateExpensePercentile(float $value, float $total): float
    {
        if ($total <= 0) {
            return 0;
        }

        $politicianTotal = DB::table('expenses')
            ->where('value', '<', $value)
            ->sum('value');

        return round(($politicianTotal / $total) * 100, 2);
    }
}
