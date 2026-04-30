<?php

namespace App\Services;

use App\Models\ApproverSet;
use App\Models\MainCapex;
use App\Models\SubCapex;
use App\Models\SubSubCapex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

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

			$userId = 17; // HARDCODED for testing - IGNORE

			$capex = MainCapex::with('subCapex')->findOrFail($mainCapexId);

			/*
			|--------------------------------------------------------------------------
			| GET CURRENT APPROVER (MATCH LEVEL FROM STRING)
			|--------------------------------------------------------------------------
			*/

			$currentApprover = ApproverSet::where('main_capex_id', $mainCapexId)
				->where('user_id', $userId)
				->where('approver_set_name', 'LIKE', 'ESTIMATOR LEVEL%')

				// MATCH LEVEL FROM STRING
				->whereRaw("
					CAST(
						REPLACE(approver_set_name, 'ESTIMATOR LEVEL ', '')
					AS UNSIGNED
					) = ?
				", [$capex->estimation_level])

				->first();

			if (!$currentApprover) {
				throw new \Exception('You are not allowed to estimate this.');
			}

			/*
			|--------------------------------------------------------------------------
			| VALIDATION
			|--------------------------------------------------------------------------
			*/

			// if (
			// 	$capex->status !== 'approved' ||
			// 	$capex->phase !== 'for_estimate' 
			// ) {
			// 	throw new \Exception('Invalid capex state.');
			// }

			// CURRENT LEVEL
			$currentLevel = $capex->estimation_level;

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

			foreach ($data['sub_capex'] as $subIndex => $sub) {

				$subCapex = SubCapex::where('id', $sub['sub_capex_id'])
					->where('main_capex_id', $mainCapexId)
					->firstOrFail();

				// DELETE ONLY SAME USER + SAME LEVEL (EDIT SAFE)
				SubSubCapex::where('sub_capex_id', $subCapex->id)
					->where('estimation_level', $currentLevel)
					->where('estimator_id', $userId)
					->delete();

				/*
				|--------------------------------------------------------------------------
				| INSERT NEW ITEMS
				|--------------------------------------------------------------------------
				*/

				foreach ($sub['items'] as $itemIndex => $item) {

					$filePath = null;

					// HANDLE ATTACHMENT SAFELY
					if (
						isset($item['attachment']) &&
						$item['attachment'] instanceof UploadedFile
					) {
						$filePath = $item['attachment']->store('capex-estimations', 'public');
					}

					SubSubCapex::create([
						'sub_capex_id' => $subCapex->id,
						'particulars' => $item['particulars'],
						'estimated_cost' => $item['estimated_cost'],
						'attachments' => $filePath, // ✅ ATTACHMENT SAVED HERE
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
			| UPDATE MAIN TOTALS
			|--------------------------------------------------------------------------
			*/

			$totalApplied = $capex->subCapex()->sum('applied_amount');
			$totalEstimated = $capex->subCapex()->sum('estimate_amount');

			$capex->update([
				'total_applied_amount' => $totalApplied,
				'total_variance_amount' => $totalApplied - $totalEstimated,
				'estimation_level' => $currentLevel + 1,
				'status' => 'for_approval',
				'phase' => 'for_estimate_approval',
			]);

			/*
			|--------------------------------------------------------------------------
			| HISTORY LOG
			|--------------------------------------------------------------------------
			*/

			$this->historyService->log(
				$capex->fresh(),
				$userId,
				$currentApprover->approver_set_name,
				"ESTIMATOR LEVEL {$currentLevel} SUBMITTED ESTIMATION"
			);

			return $capex->fresh();
		});
	}

	public function return($mainCapexId, $remarks = null)
	{
		return DB::transaction(function () use ($mainCapexId, $remarks) {

			// $userId = Auth::id();
			$userId = 20; // HARDCODED for testing - IGNORE

			$capex = MainCapex::with('subCapex')->findOrFail($mainCapexId);

			/*
			|--------------------------------------------------------------------------
			| GET CURRENT APPROVER (MATCH LEVEL FROM STRING)
			|--------------------------------------------------------------------------
			*/

			$currentApprover = ApproverSet::where('main_capex_id', $mainCapexId)
				->where('user_id', $userId)
				->where('approver_set_name', 'LIKE', 'ESTIMATOR LEVEL%')

				// MATCH LEVEL FROM STRING
				->whereRaw("
					CAST(
						REPLACE(approver_set_name, 'ESTIMATOR LEVEL ', '')
					AS UNSIGNED
					) = ?
				", [$capex->estimation_level])

				->first();

			if (!$currentApprover) {
				throw new \Exception('You are not allowed to return this.');
			}

			/*
			|--------------------------------------------------------------------------
			| RESET FLOW
			|--------------------------------------------------------------------------
			*/

			if ($capex->estimation_level == 1) {
				$capex->update([
					'first_phase_level' => 1,
					'status' => 'returned',
					'phase' => 'for_first_phase_approval',
					'remarks' => $remarks,
				]);
			}

			if ($capex->estimation_level > 1) {
				$capex->update([
					'estimation_level' => $capex->estimation_level - 1,
					'estimation_approving_level' => $capex->estimation_approving_level - 1,
					'status' => 'returned',
					'remarks' => $remarks,
				]);
			}

			/*
			|--------------------------------------------------------------------------
			| HISTORY
			|--------------------------------------------------------------------------
			*/

			$this->historyService->log(
				$capex->fresh(),
				$userId,
				$currentApprover->approver_set_name,
				"RETURNED BY AN ESTIMATOR → RESET TO LEVEL 1"
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
		$userId = 20; // HARDCODED for testing - IGNORE

		return MainCapex::where('phase', 'for_estimate')
			->whereHas('approverSets', function ($query) use ($userId) {

				$query->where('user_id', $userId)
					->where('approver_set_name', 'LIKE', 'ESTIMATOR LEVEL%')

					// EXTRACT NUMBER FROM STRING AND MATCH
					->whereRaw("
						CAST(
							REPLACE(approver_set_name, 'ESTIMATOR LEVEL ', '')
						AS UNSIGNED
					) = main_capex.estimation_level
					");
			})
			->latest()
			->get();
	}

    /**
     * Get Full Capex Details (Main + Sub + SubSub)
     */
    public function getCapexDetails($id)
    {
        // $userId = Auth::id();
		$userId = 17; // HARDCODED for testing - IGNORE

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

	public function updateEstimation($mainCapexId, array $data)
	{
		return DB::transaction(function () use ($mainCapexId, $data) {

			// $userId = Auth::id();
			$userId = 17; // HARDCODED for testing - IGNORE

			$capex = MainCapex::with('subCapex')->findOrFail($mainCapexId);

			$approvers = ApproverSet::where('main_capex_id', $mainCapexId)
				->where('approver_set_name', 'LIKE', '%ESTIMATOR LEVEL%')
				->get();

			if ($approvers->isEmpty()) {
				throw new \Exception('No approvers configured.');
			}

			$currentApprover = $approvers
				->where('user_id', $userId)
				->first();

			/*
			|--------------------------------------------------------------------------
			| VALIDATION
			|--------------------------------------------------------------------------
			*/

			if (!in_array($capex->phase, ['for_estimate', 'for_estimate_approval'])) {
				throw new \Exception('Editing not allowed at this stage.');
			}

			// IMPORTANT: use PREVIOUS LEVEL
			$currentLevel = $capex->estimation_level - 1;

			if ($capex->status === 'rejected' && $capex->phase === 'for_estimate') {
				$currentLevel = $capex->estimation_level;
			}

			if ($currentLevel < 1) {
				throw new \Exception('No estimation to edit.');
			}

			/*
			|--------------------------------------------------------------------------
			| CHECK OWNERSHIP (ONLY EDIT YOUR OWN ESTIMATE)
			|--------------------------------------------------------------------------
			*/

			$isOwner = SubSubCapex::whereHas('subCapex', function ($q) use ($mainCapexId) {
					$q->where('main_capex_id', $mainCapexId);
				})
				->where('estimation_level', $currentLevel)
				->where('estimator_id', $userId)
				->exists();

			if (!$isOwner) {
				throw new \Exception('You are not allowed to edit this estimation.');
			}

			/*
			|--------------------------------------------------------------------------
			| BLOCK IF ALREADY APPROVED
			|--------------------------------------------------------------------------
			*/

			$alreadyApproved = SubSubCapex::whereHas('subCapex', function ($q) use ($mainCapexId) {
					$q->where('main_capex_id', $mainCapexId);
				})
				->where('estimation_level', $currentLevel)
				->where('estimation_approving_status', '!=', 'rejected')
				->whereNotNull('estimation_approver_id')
				->exists();

			if ($alreadyApproved) {
				throw new \Exception('Cannot edit. Already approved.');
			}

			/*
			|--------------------------------------------------------------------------
			| UPDATE (DELETE + REINSERT SAFE)
			|--------------------------------------------------------------------------
			*/

			foreach ($data['sub_capex'] as $sub) {

				$subCapex = SubCapex::where('id', $sub['sub_capex_id'])
					->where('main_capex_id', $mainCapexId)
					->firstOrFail();

				// DELETE ONLY YOUR RECORD
				SubSubCapex::where('sub_capex_id', $subCapex->id)
					->where('estimation_level', $currentLevel)
					->where('estimator_id', $userId)
					->delete();

				foreach ($sub['items'] as $item) {

					$filePath = null;

					if (isset($item['attachment']) && $item['attachment']) {
						$filePath = $item['attachment']->store('capex-estimations', 'public');
					}

					SubSubCapex::create([
						'sub_capex_id' => $subCapex->id,
						'particulars' => $item['particulars'],
						'estimated_cost' => $item['estimated_cost'],
						'attachments' => $filePath,
						'estimator_id' => $userId,
						'estimation_level' => $currentLevel,
						'estimation_status' => 'submitted',
						'remarks' => $item['remarks'] ?? null,
					]);
				}

				/*
				|--------------------------------------------------------------------------
				| RECALCULATE
				|--------------------------------------------------------------------------
				*/

				$totalEstimate = SubSubCapex::where('sub_capex_id', $subCapex->id)
					->where('estimation_level', $currentLevel)
					->sum('estimated_cost');

				$subCapex->update([
					'estimate_amount' => $totalEstimate,
					'variance_amount' => $subCapex->applied_amount - $totalEstimate,
				]);
			}

			/*
			|--------------------------------------------------------------------------
			| UPDATE MAIN TOTALS
			|--------------------------------------------------------------------------
			*/

			$totalApplied = $capex->subCapex()->sum('applied_amount');
			$totalEstimated = $capex->subCapex()->sum('estimate_amount');

			if ($capex->status === 'rejected' && $capex->phase === 'for_estimate') {
				$capex->update([
					'estimation_level' => $currentLevel + 1,
					'status' => 'for_approval',
					'phase' => 'for_estimate_approval',
				]);
			}

			$capex->update([
				'total_applied_amount' => $totalApplied,
				'total_variance_amount' => $totalApplied - $totalEstimated,
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
				"ESTIMATOR LEVEL {$currentLevel} UPDATED ESTIMATION"
			);

			return $capex->fresh();
		});
	}
}