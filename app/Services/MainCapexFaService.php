<?php

namespace App\Services;

use App\Models\ApproverSet;
use App\Models\MainCapex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MainCapexFaService
{
    protected $historyService;

    public function __construct(MainCapexHistoryService $historyService)
    {
        $this->historyService = $historyService;
    }

    /**
     * Get FA Pending List
     */
    public function getFaPendingList()
    {
        return MainCapex::where('estimation_level', 1)
            ->where('estimation_approving_level', 1)
            ->where('first_phase_level', 1)
            ->where('status', 'pending')
            ->where('phase', 'for_first_phase_approval')
            ->latest()
            ->get();
    }

    /**
     * Get FA Transactions by Conditions
     */
    public function getByConditions(array $conditions)
	{
		$query = MainCapex::query();

		if (!empty($conditions['status'])) {
			$query->whereIn('status', $conditions['status']);
		}

		if (!empty($conditions['phase'])) {
			$query->where('phase', $conditions['phase']);
		}

		return $query->latest()->paginate(10);
	}

    /**
     * RETURN TRANSACTION
     */
	public function return($id, $remarks = null)
	{
		return DB::transaction(function () use ($id, $remarks) {

			// $userId = Auth::id();
			$userId = 26; // HARDCODED for testing - IGNORE

			$capex = MainCapex::findOrFail($id);

			/*
			|--------------------------------------------------------------------------
			| GET APPROVERS
			|--------------------------------------------------------------------------
			*/

			$approvers = ApproverSet::where('main_capex_id', $id)
				->where('approver_set_name', 'FIRST PHASE APPROVER')
				->get();

			$currentApprover = $approvers
				->where('user_id', $userId)
				->where('level', $capex->first_phase_level)
				->first();

			if (!$currentApprover) {
				throw new \Exception('You are not allowed to return this.');
			}

			/*
			|--------------------------------------------------------------------------
			| RESET FLOW
			|--------------------------------------------------------------------------
			*/

			$capex->update([
				'first_phase_level' => 1,
				'status' => 'returned',
				'phase' => 'for_first_phase_approval',
				'remarks' => $remarks,
			]);

			/*
			|--------------------------------------------------------------------------
			| HISTORY
			|--------------------------------------------------------------------------
			*/

			$this->historyService->log(
				$capex->fresh(),
				$userId,
				$currentApprover->approver_set_name,
				"RETURNED AT LEVEL {$currentApprover->level} → RESET TO LEVEL 1"
			);

			return $capex->fresh();
		});
	}

	public function reject($id, $remarks = null)
	{
		return DB::transaction(function () use ($id, $remarks) {

			// $userId = Auth::id();
			$userId = 19; // HARDCODED for testing - IGNORE

			$capex = MainCapex::findOrFail($id);

			/*
			|--------------------------------------------------------------------------
			| GET APPROVERS
			|--------------------------------------------------------------------------
			*/

			$approvers = ApproverSet::where('main_capex_id', $id)
				->where('approver_set_name', 'FIRST PHASE APPROVER')
				->get();

			$currentApprover = $approvers
				->where('user_id', $userId)
				->where('level', $capex->first_phase_level)
				->first();

			if (!$currentApprover) {
				throw new \Exception('You are not allowed to reject this.');
			}

			/*
			|--------------------------------------------------------------------------
			| RESET FLOW
			|--------------------------------------------------------------------------
			*/

			$capex->update([
				'first_phase_level' => 1,
				'status' => 'rejected',
				'phase' => 'for_first_phase_approval',
				'remarks' => $remarks,
			]);

			/*
			|--------------------------------------------------------------------------
			| HISTORY
			|--------------------------------------------------------------------------
			*/

			$this->historyService->log(
				$capex->fresh(),
				$userId,
				$currentApprover->approver_set_name,
				"REJECTED AT LEVEL {$currentApprover->level} → RESET TO LEVEL 1"
			);

			return $capex->fresh();
		});
	}

    /**
     * SUBMIT TRANSACTION
     */
    public function submit($id)
	{
		return DB::transaction(function () use ($id) {

			// $userId = Auth::id();
			$userId = 39; // HARDCODED for testing - IGNORE

			$capex = MainCapex::findOrFail($id);

			/*
			|--------------------------------------------------------------------------
			| GET APPROVER SET (FIRST PHASE)
			|--------------------------------------------------------------------------
			*/

			$approvers = ApproverSet::where('main_capex_id', $id)
				->where('approver_set_name', 'FIRST PHASE APPROVER')
				->orderBy('level')
				->get();

			if ($approvers->isEmpty()) {
				throw new \Exception('No approvers configured.');
			}

			/*
			|--------------------------------------------------------------------------
			| GET CURRENT APPROVER LEVEL
			|--------------------------------------------------------------------------
			*/

			$currentApprover = $approvers
				->where('user_id', $userId)
				->where('level', $capex->first_phase_level)
				->first();

			if (!$currentApprover) {
				throw new \Exception('You are not authorized to approve this.');
			}

			$currentLevel = $capex->first_phase_level;
			$maxLevel = $approvers->max('level');

			/*
			|--------------------------------------------------------------------------
			| CHECK IF FINAL APPROVER
			|--------------------------------------------------------------------------
			*/

			if ($currentLevel == $maxLevel) {

				// ✅ FINAL APPROVER
				$capex->update([
					'first_phase_level' => $maxLevel + 1,
					'status' => 'confirmed',
					'phase' => 'for_estimate',
				]);

				$this->historyService->log(
					$capex->fresh(),
					$userId,
					$currentApprover->approver_set_name,
					"FINAL APPROVER (LEVEL {$currentLevel}) → CONFIRMED & MOVED TO ESTIMATION"
				);

			} else {

				// 🔁 MOVE TO NEXT APPROVER
				$nextLevel = $currentLevel + 1;

				$capex->update([
					'first_phase_level' => $nextLevel,
					'status' => 'for_approval',
					'phase' => 'for_first_phase_approval',
				]);

				$this->historyService->log(
					$capex->fresh(),
					$userId,
					$currentApprover->approver_set_name,
					"APPROVED LEVEL {$currentLevel} → MOVED TO LEVEL {$nextLevel}"
				);
			}

			return $capex->fresh();
		});
	}
}