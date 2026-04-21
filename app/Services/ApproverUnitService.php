<?php

namespace App\Services;

use App\Models\ApproverUnit;
use Illuminate\Support\Facades\DB;

class ApproverUnitService
{
    /**
     * GET ALL (GROUPED)
     */
    public function getAll($perPage = 10)
	{
		$paginated = ApproverUnit::with([
				'oneCharging',
				// 'unit',
				// 'subunit',
				'approver.user'
			])
			->orderBy('one_charging_id')
			->orderBy('level')
			->paginate($perPage);

		$grouped = $paginated->getCollection()
			->groupBy('one_charging_id')
			->values();

		return [
			'current_page' => $paginated->currentPage(),
			'data' => $grouped,
		];
	}

	public function getByOneChargingId($oneChargingId)
	{
		$items = $this->getApproverUnitsOrFail($oneChargingId);

		$first = $items->first();

		return [
			'one_charging' => $first->oneCharging,
			'approver_set_name' => $first->approver_set_name,
			'approvers' => $items->map(function ($item) {

				$user = $item->approver?->user;

				return [
					'approver_id' => $item->approver?->id,
					'username' => $user?->username,
					'employee_id' => $user?->employee_id,
					'firstname' => $user?->firstname,
					'lastname' => $user?->lastname,
					'level' => (string) $item->level,
					'status' => $item->approver?->is_active ? 'active' : 'inactive',
				];
			})->values(),
			'created_at' => $first->created_at,
		];
	}

	private function getApproverUnitsOrFail($oneChargingId)
	{
		$items = ApproverUnit::with([
				'oneCharging',
				'approver.user'
			])
			->where('one_charging_id', $oneChargingId)
			->orderBy('level')
			->get();

		if ($items->isEmpty()) {
			abort(404, "Approver units not found for one_charging_id {$oneChargingId}");
		}

		return $items;
	}

    /**
     * STORE
     */
    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {

            $exists = ApproverUnit::where('one_charging_id', $data['one_charging_id'])
				->where('approver_set_name', $data['approver_set_name'])
				->exists();

            if ($exists) {
                throw new \Exception('Approver set already exists for this one charging');
            }

            $records = [];

            foreach ($data['approver_id'] as $index => $approverId) {
                $records[] = ApproverUnit::create([
                    'unit_id' => $data['unit_id'],
                    'subunit_id' => $data['subunit_id'] ?? null,
                    'one_charging_id' => $data['one_charging_id'],
                    'approver_id' => $approverId,
                    'level' => $index + 1,
                    'approver_set_name' => $data['approver_set_name'],
                ]);
            }

            return $records;
        });
    }

    /**
     * UPDATE (replace whole workflow)
     */
    public function updateByOneChargingId($oneChargingId, array $data)
	{
		return DB::transaction(function () use ($oneChargingId, $data) {

			// 🔥 CHECK IF WORKFLOW EXISTS
			$exists = ApproverUnit::where('one_charging_id', $oneChargingId)->exists();

			if (!$exists) {
				throw new \Exception("Approver set for one charging id {$oneChargingId} does not exist.");
			}

			// delete existing workflow
			ApproverUnit::where('one_charging_id', $oneChargingId)->delete();

			$records = [];

			foreach ($data['approver_id'] as $index => $approverId) {
				$records[] = ApproverUnit::create([
					'unit_id' => $data['unit_id'],
					'subunit_id' => $data['subunit_id'] ?? null,
					'one_charging_id' => $oneChargingId,
					'approver_id' => $approverId,
					'level' => $index + 1,
					'approver_set_name' => $data['approver_set_name'],
				]);
			}

			return $records;
		});
	}

    /**
     * DELETE
     */
    public function deleteByOneChargingId($oneChargingId)
	{
		return DB::transaction(function () use ($oneChargingId) {

			$query = ApproverUnit::where('one_charging_id', $oneChargingId);

			$count = $query->count();

			if ($count === 0) {
				abort(404, "No Approver set found for this one charging id");
			}

			$query->delete();

			return [
				'deleted_records' => $count,
				'one_charging_id' => $oneChargingId,
				'message' => "Data deleted successfully!",
			];
		});
	}
}