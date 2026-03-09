<?php

namespace App\Http\Controllers;

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
        $existing = PairUse::where('pair_id', $pairId)
            ->where('status', 'watching')
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['message' => 'Removed from watching', 'watching' => false]);
        }

        PairUse::create([
            'pair_id' => $pairId,
            'status' => 'watching',
            'date_started' => now(),
        ]);

        return response()->json(['message' => 'Saved as watching', 'watching' => true]);
    }
}
