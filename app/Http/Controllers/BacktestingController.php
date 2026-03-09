<?php

namespace App\Http\Controllers;

use App\Models\BacktestingResult;
use App\Models\PairUse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BacktestingController extends Controller
{
    public function index(Request $request): View
    {
        $sortBy = $request->get('sort', 'profit_percent');
        $sortDirection = $request->get('direction', 'desc');

        $allowedSorts = [
            'profit_percent',
            'profit_percent_against_wbw',
            'ma_length',
            'thresh',
            'trade_count',
            'score'
        ];

        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'profit_percent';
        }

        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $query = BacktestingResult::with(['pair', 'pair.scoreResults']);

        // Filter for unique pairs if checkbox is checked
        if ($request->get('unique_pairs')) {
            $query->where('ma_length', 20)
                  ->where('thresh', 2);
        }

        // Handle sorting by score (from related score_results)
        if ($sortBy === 'score') {
            $query->leftJoin('score_results', function($join) {
                $join->on('backtesting_results.pair_id', '=', 'score_results.pair_id')
                     ->whereRaw('score_results.id = (SELECT MAX(id) FROM score_results sr WHERE sr.pair_id = backtesting_results.pair_id)');
            });
            $query->orderBy('score_results.score', $sortDirection);
            $query->select('backtesting_results.*', 'score_results.score');
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        $results = $query->paginate(50)->appends($request->query());

        $watchingPairs = PairUse::where('status', 'watching')
            ->with('pair')
            ->get();

        $watchingPairIds = $watchingPairs->pluck('pair_id')->flip()->all();

        $livePairs = PairUse::where('status', 'live')
            ->with('pair')
            ->get();

        $pairStatuses = PairUse::whereIn('status', ['watching', 'live', 'archived'])
            ->get()
            ->pluck('status', 'pair_id')
            ->all();

        return view('backtesting.index', compact('results', 'sortBy', 'sortDirection', 'watchingPairIds', 'watchingPairs', 'livePairs', 'pairStatuses'));
    }
}
