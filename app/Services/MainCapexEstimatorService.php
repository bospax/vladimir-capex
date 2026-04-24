<?php

namespace App\Services;

use App\Models\MainCapex;
use App\Models\SubCapex;
use App\Models\SubSubCapex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MainCapexEstimatorService
{

	protected $historyService;

    public function __construct(MainCapexHistoryService $historyService)
    {
        $this->historyService = $historyService;
    }

    public function saveEstimation($mainCapexId, array $data)
    {
        return DB::transaction(function () use ($mainCapexId, $data) {

            $userId = Auth::id();

            $capex = MainCapex::with('subCapex')->findOrFail($mainCapexId);

            /*
            |--------------------------------------------------------------------------
            | VALIDATION
            |--------------------------------------------------------------------------
            */

            if (
                $capex->first_phase_level != 2 ||
                $capex->status !== 'confirmed' ||
                $capex->phase !== 'for_estimate'
            ) {
                throw new \Exception('Invalid capex state.');
            }

            // 🔥 CURRENT LEVEL (1 → 4)
            $currentLevel = $capex->estimation_level;

            if ($currentLevel < 1 || $currentLevel > 4) {
                throw new \Exception('Invalid estimation level.');
            }

            /*
            |--------------------------------------------------------------------------
            | LOCK PER LEVEL (ONLY 1 ESTIMATOR PER LEVEL)
            |--------------------------------------------------------------------------
            */

            $levelTaken = SubSubCapex::whereHas('subCapex', function ($q) use ($mainCapexId) {
                $q->where('main_capex_id', $mainCapexId);
            })
            ->where('estimation_level', $currentLevel)
            ->where('estimator_id', '!=', $userId)
            ->exists();

            if ($levelTaken) {
                throw new \Exception("Level {$currentLevel} already taken by another estimator.");
            }

            /*
            |--------------------------------------------------------------------------
            | SAVE ESTIMATION PER SUB CAPEX
            |--------------------------------------------------------------------------
            */

            foreach ($data['sub_capex'] as $sub) {

                $subCapex = SubCapex::where('id', $sub['sub_capex_id'])
                    ->where('main_capex_id', $mainCapexId)
                    ->firstOrFail();

                // DELETE ONLY SAME USER + SAME LEVEL (EDIT MODE)
                SubSubCapex::where('sub_capex_id', $subCapex->id)
                    ->where('estimation_level', $currentLevel)
                    ->where('estimator_id', $userId)
                    ->delete();

                /*
                |--------------------------------------------------------------------------
                | INSERT NEW ITEMS
                |--------------------------------------------------------------------------
                */

                foreach ($sub['items'] as $item) {

                    SubSubCapex::create([
                        'sub_capex_id' => $subCapex->id,
                        'particulars' => $item['particulars'],
                        'estimated_cost' => $item['estimated_cost'],
                        'estimator_id' => $userId,
                        'estimation_level' => $currentLevel,
                        'estimation_status' => 'submitted',
                        'estimation_approver_id' => null,
                        'estimation_approving_status' => null,
                        'is_applicable' => 1,
                        'remarks' => $item['remarks'] ?? null,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | COMPUTE TOTAL PER SUB CAPEX (CURRENT LEVEL ONLY)
                |--------------------------------------------------------------------------
                */

                $totalEstimate = SubSubCapex::where('sub_capex_id', $subCapex->id)
                    ->where('estimation_level', $currentLevel)
                    ->where('is_applicable', 1)
                    ->sum('estimated_cost');

                $subCapex->update([
                    'estimate_amount' => $totalEstimate,
                    'variance_amount' => $subCapex->applied_amount - $totalEstimate,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE MAIN TOTALS (BASED ON CURRENT LEVEL)
            |--------------------------------------------------------------------------
            */

            $totalApplied = $capex->subCapex()->sum('applied_amount');
            $totalEstimated = $capex->subCapex()->sum('estimate_amount');

            $capex->update([
                'total_applied_amount' => $totalApplied,
                'total_variance_amount' => $totalApplied - $totalEstimated,
            ]);

            /*
            |--------------------------------------------------------------------------
            | HISTORY LOG
            |--------------------------------------------------------------------------
            */

            $this->historyService->log(
                $capex->fresh(),
                $userId,
                "ESTIMATOR LEVEL {$currentLevel} SUBMITTED ESTIMATION"
            );

            return $capex->fresh();
        });
    }

    /**
     * Get Estimation List (for logged-in estimator only)
     */
    public function getEstimatorList()
    {
        // $userId = Auth::id();
		$userId = 9; // HARDCODED for testing - IGNORE

        return MainCapex::where('status', 'confirmed')
            ->where('phase', 'for_estimate')
            // IMPORTANT: Filter by approver_set
            ->whereHas('approverSets', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
			->whereDoesntHave('subCapex.subSubCapex')
            ->latest()
            ->get();
    }

    /**
     * Get Full Capex Details (Main + Sub + SubSub)
     */
    public function getCapexDetails($id)
    {
        // $userId = Auth::id();
		$userId = 9; // HARDCODED for testing - IGNORE

        $capex = MainCapex::with([
            'subCapex.subSubCapex'
        ])
        ->where('id', $id)

        // SECURITY: ensure user is allowed
        ->whereHas('approverSets', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->firstOrFail();

        return $capex;
    }
}