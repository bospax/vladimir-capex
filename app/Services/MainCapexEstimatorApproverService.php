<?php

namespace App\Services;

use App\Models\MainCapex;
use App\Models\ApproverSet;
use App\Models\SubSubCapex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MainCapexEstimatorApproverService
{
    protected $historyService;

    public function __construct(MainCapexHistoryService $historyService)
    {
        $this->historyService = $historyService;
    }

    /*
    |----------------------------------------------------------------------
    | APPROVE ESTIMATION
    |----------------------------------------------------------------------
    */

    public function approve($id)
    {
        return DB::transaction(function () use ($id) {

            // $userId = Auth::id();
			$userId = 28; // HARDCODED for testing - IGNORE

            $capex = MainCapex::findOrFail($id);

            /*
            |----------------------------------------------------------------------
            | VALIDATION
            |----------------------------------------------------------------------
            */

            if ($capex->phase !== 'for_estimate_approval') {
                throw new \Exception('Not in approval phase.');
            }

            $currentLevel = $capex->estimation_approving_level;

            /*
            |----------------------------------------------------------------------
            | CHECK APPROVER
            |----------------------------------------------------------------------
            */

            $approver = ApproverSet::where('main_capex_id', $id)
                ->where('approver_set_name', 'ESTIMATOR APPROVER')
                ->where('level', $currentLevel)
                ->where('user_id', $userId)
                ->first();

            if (!$approver) {
                throw new \Exception('You are not allowed to approve.');
            }

            /*
            |----------------------------------------------------------------------
            | MARK ALL ITEMS AS APPROVED
            |----------------------------------------------------------------------
            */

            SubSubCapex::whereHas('subCapex', function ($q) use ($id) {
                    $q->where('main_capex_id', $id);
                })
                ->where('estimation_level', $currentLevel)
                ->update([
                    'estimation_approver_id' => $userId,
                    'estimation_approving_status' => 'approved',
                ]);

            /*
            |----------------------------------------------------------------------
            | CHECK IF LAST LEVEL
            |----------------------------------------------------------------------
            */

            $maxLevel = ApproverSet::where('main_capex_id', $id)
                ->where('approver_set_name', 'ESTIMATOR APPROVER')
                ->max('level');

            if ($currentLevel >= $maxLevel) {

                // FINAL APPROVAL
                $capex->update([
                    'status' => 'approved',
                    'phase' => 'estimate_completed',
                ]);

            } else {

                // MOVE TO NEXT ESTIMATOR LEVEL
                $capex->update([
                    'estimation_approving_level' => $currentLevel + 1,
                    'status' => 'approved',
                    'phase' => 'for_estimate',
                ]);
            }

            /*
            |----------------------------------------------------------------------
            | HISTORY
            |----------------------------------------------------------------------
            */

            $this->historyService->log(
                $capex->fresh(),
                $userId,
                'ESTIMATOR APPROVER',
                "APPROVED ESTIMATION LEVEL {$currentLevel}"
            );

            return $capex->fresh();
        });
    }

    /*
    |----------------------------------------------------------------------
    | REJECT ESTIMATION
    |----------------------------------------------------------------------
    */

    public function reject($id, $remarks = null)
    {
        return DB::transaction(function () use ($id, $remarks) {

            // $userId = Auth::id();
			$userId = 28; // HARDCODED for testing - IGNORE

            $capex = MainCapex::findOrFail($id);

            if ($capex->phase !== 'for_estimate_approval') {
                throw new \Exception('Not in approval phase.');
            }

            $currentLevel = $capex->estimation_approving_level;

            /*
            |----------------------------------------------------------------------
            | CHECK APPROVER
            |----------------------------------------------------------------------
            */

            $approver = ApproverSet::where('main_capex_id', $id)
                ->where('approver_set_name', 'ESTIMATOR APPROVER')
                ->where('level', $currentLevel)
                ->where('user_id', $userId)
                ->first();

            if (!$approver) {
                throw new \Exception('You are not allowed to reject.');
            }

            /*
            |----------------------------------------------------------------------
            | MARK AS REJECTED
            |----------------------------------------------------------------------
            */

            SubSubCapex::whereHas('subCapex', function ($q) use ($id) {
                    $q->where('main_capex_id', $id);
                })
                ->where('estimation_level', $currentLevel)
                ->update([
                    'estimation_approver_id' => $userId,
                    'estimation_approving_status' => 'rejected',
                ]);

            /*
            |----------------------------------------------------------------------
            | RESET BACK TO ESTIMATOR
            |----------------------------------------------------------------------
            */

            $capex->update([
                'estimation_level' => $currentLevel,
                'estimation_approving_level' => $currentLevel,
                'status' => 'rejected',
                'phase' => 'for_estimate',
                'remarks' => $remarks,
            ]);

            /*
            |----------------------------------------------------------------------
            | HISTORY
            |----------------------------------------------------------------------
            */

            $this->historyService->log(
                $capex->fresh(),
                $userId,
                'ESTIMATOR APPROVER',
                "REJECTED ESTIMATION LEVEL {$currentLevel}"
            );

            return $capex->fresh();
        });
    }

	public function getEstimatorApproverList()
	{
		// $userId = Auth::id();
		$userId = 28; // HARDCODED for testing - IGNORE

		return MainCapex::where('phase', 'for_estimate_approval')
			->whereHas('approverSets', function ($query) use ($userId) {

				$query->where('user_id', $userId)
					->where('approver_set_name', 'LIKE', '%ESTIMATOR APPROVER%')

					//  MATCH LEVEL
					->whereColumn(
						'approver_set.level',
						'main_capex.estimation_approving_level'
					);
			})
			->latest()
			->get();
	}

	public function getCapexDetails($id)
    {
        // $userId = Auth::id();
		$userId = 28; // HARDCODED for testing - IGNORE

        $capex = MainCapex::with([
            'subCapex.subSubCapex'
        ])
        ->where('id', $id)

        ->whereHas('approverSets', function ($query) use ($userId) {

			$query->where('user_id', $userId)
				->where('approver_set_name', 'LIKE', '%ESTIMATOR APPROVER%')

				//  MATCH LEVEL
				->whereColumn(
					'approver_set.level',
					'main_capex.estimation_approving_level'
				);
		})
        ->firstOrFail();

        return $capex;
    }
}