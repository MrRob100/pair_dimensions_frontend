<?php

namespace App\Http\Controllers;

use App\Models\Pair;
use App\Models\PairUse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PairUseController extends Controller
{
    public function setStatus(Request $request, int $pairId): JsonResponse
    {
        $existing = PairUse::where('pair_id', $pairId)->latest('id')->first();

        if (! $existing) {
            PairUse::create([
                'pair_id' => $pairId,
                'status' => 'watching',
                'date_started' => now(),
            ]);

            return response()->json(['status' => 'watching']);
        }

        $transitions = ['watching' => 'live', 'live' => 'archived', 'archived' => 'watching'];
        $newStatus = $transitions[$existing->status] ?? 'watching';
        $existing->status = $newStatus;
        $existing->date_finished = $newStatus === 'archived' ? now() : null;
        $existing->save();

        return response()->json(['status' => $newStatus]);
    }

    public function watch(Request $request, int $pairId): JsonResponse
    {
        // If pairId is 0, find or create the pair from symbols
        if ($pairId === 0) {
            $s1 = strtoupper($request->input('symbol1', ''));
            $s2 = strtoupper($request->input('symbol2', ''));
            if (!$s1 || !$s2) {
                return response()->json(['error' => 'symbol1 and symbol2 required for new pairs'], 400);
            }
            $pair = Pair::firstOrCreate(
                ['symbol_1' => $s1, 'symbol_2' => $s2],
                ['type' => 'spot']
            );
            $pairId = $pair->id;
        }

        $existing = PairUse::where('pair_id', $pairId)
            ->where('status', 'watching')
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['message' => 'Removed from watching', 'watching' => false, 'pair_id' => $pairId]);
        }

        PairUse::create([
            'pair_id' => $pairId,
            'status' => 'watching',
            'date_started' => now(),
        ]);

        return response()->json(['message' => 'Saved as watching', 'watching' => true, 'pair_id' => $pairId]);
    }
}
