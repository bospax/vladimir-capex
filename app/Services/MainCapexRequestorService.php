<?php

namespace App\Services;

use App\Models\MainCapex;
use Illuminate\Support\Facades\DB;

class MainCapexRequestorService
{
	public function getByConditions(array $conditions)
	{
		$query = MainCapex::query();

		if (!empty($conditions['status'])) {
			$query->whereIn('status', $conditions['status']);
		}

		if (!empty($conditions['phase'])) {
			$query->where('phase', $conditions['phase']);
		}

		if (!empty($conditions['user_id'])) {
			$query->where('requestor_id', $conditions['user_id']);
		}

		return $query->latest()->paginate(10);
	}

	public function confirmEstimate($id, $userId)
	{
		return DB::transaction(function () use ($id, $userId) {

			$capex = MainCapex::findOrFail($id);

			/*
			|------------------------------------------------------------------
			| VALIDATION
			|------------------------------------------------------------------
			*/

			if ($capex->phase !== 'estimate_completed') {
				throw new \Exception('Estimation is not yet completed.');
			}

			/*
			|------------------------------------------------------------------
			| UPDATE CAPEX
			|------------------------------------------------------------------
			*/

			$capex->update([
				'major_level' => 1,
				'status' => 'for_approval',
				'phase' => 'for_major_approval',
			]);

			/*
			|------------------------------------------------------------------
			| HISTORY LOG (IMPORTANT)
			|------------------------------------------------------------------
			*/

			app(MainCapexHistoryService::class)->log(
				$capex->fresh(),
				$userId,
				'REQUESTOR',
				'REQUESTOR CONFIRMED ESTIMATION → FOR MAJOR APPROVAL'
			);

			return $capex->fresh();
		});
	}
}

