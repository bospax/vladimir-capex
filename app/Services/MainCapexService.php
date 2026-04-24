<?php

namespace App\Services;

use App\Models\ApproverSet;
use App\Models\ApproverUnit;
use App\Models\MainCapex;
use App\Models\SubCapex;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MainCapexService
{
	protected $historyService;

	public function __construct(MainCapexHistoryService $historyService)
	{
		$this->historyService = $historyService;
	}

    public function list($request)
    {
        return MainCapex::latest()
            ->paginate(10);
    }

    public function find($id)
    {
        return MainCapex::with(['requestor', 'oneCharging', 'subCapex'])->findOrFail($id);
    }

    public function create(array $data)
    {
        $capex = MainCapex::create($data);

		$this->historyService->log(
			$capex,
			Auth::id(),
			'Created CAPEX'
		);

		return $capex;
    }

    public function update($id, array $data)
    {
        $mainCapex = MainCapex::findOrFail($id);
        $mainCapex->update($data);

        return $mainCapex;
    }

    public function delete($id)
    {
        $mainCapex = MainCapex::findOrFail($id);
		$mainCapex->subCapex()->delete();
        $mainCapex->delete();
		
        return true;
    }

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

	private function applyDefaultWorkflow(array $data): array
	{
		$data['requestor_id'] = Auth::id();
		$data['form_type'] = 'main_capex';
		$data['first_phase_level'] = 1;
		$data['estimation_level'] = 1;
		$data['estimation_approving_level'] = 1;
		$data['major_level'] = 1;
		$data['status'] = 'pending';
		$data['phase'] = 'for_first_phase_approval';
		$data['revision_no'] = 1;

		return $data;
	}

	public function createWithChildren(array $data)
    {
        return DB::transaction(function () use ($data) {

            // 1. Extract children
            $subCapexData = $data['sub_capex'];
            unset($data['sub_capex']);

			// 1.5 Apply default workflow values
			$data = $this->applyDefaultWorkflow($data);

            // 2. Create main
            $main = MainCapex::create($data);

			// ✅ 2.5 Attach approvers (IMPORTANT PART)
            $this->attachApprovers($main);

            // 3. Prepare children
            $children = collect($subCapexData)->map(function ($item) use ($main) {
                return [
                    'main_capex_id' => $main->id,
                    'index' => $item['index'],
                    'type_of_subcapex' => $item['type_of_subcapex'],
                    'remarks' => $item['remarks'] ?? null,
                    'building_number' => $item['building_number'] ?? null,
                    'approved_amount' => $item['approved_amount'] ?? 0,
                    'applied_amount' => $item['applied_amount'] ?? 0,
                    'estimate_amount' => $item['estimate_amount'] ?? 0,
                    'variance_amount' => $item['variance_amount'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            // 4. Bulk insert
            SubCapex::insert($children);

			$this->historyService->log(
				$main,
				Auth::id(),
				'CREATED NEW TRANSACTION WITH ' . count($children) . ' SUB CAPEX ITEMS'
			);

            // 5. Return with relations
            return MainCapex::with('subCapex')->find($main->id);
        });
    }

	private function attachApprovers(MainCapex $main)
    {
        $approvers = ApproverUnit::where('one_charging_id', $main->one_charging_id)->get();

        if ($approvers->isEmpty()) {
            throw new \Exception('No approvers found for this One Charging ID');
        }

        $approverSetData = $approvers->map(function ($item) use ($main) {
            return [
                'main_capex_id' => $main->id,
                'user_id' => $item->approver_id,
                'level' => $item->level,
                'approver_set_name' => $item->approver_set_name,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        ApproverSet::insert($approverSetData);
    }

	public function updateWithChildren($id, array $data)
	{
		return DB::transaction(function () use ($id, $data) {

			// 1. Find main
			$main = MainCapex::findOrFail($id);

			// 2. Capture OLD values BEFORE update
			$old = $main->getOriginal();

			// 3. Extract children
			$subCapexData = $data['sub_capex'];
			unset($data['sub_capex']);

			if (empty($subCapexData)) {
				throw new \Exception('Sub Capex cannot be empty');
			}

			if ((($main->status === 'returned') || ($main->status === 'rejected')) && $main->phase === 'for_first_phase_approval') {
				$data['first_phase_level'] = 1;
				$data['status'] = 'pending';
				$data['phase'] = 'for_first_phase_approval';
				$data['revision_no'] = $main->revision_no + 1;
			}

			// 4. Update main
			$main->update($data);

			// 5. Detect CHANGES (main only)
			$changes = array_diff_assoc($main->getAttributes(), $old);

			$oldChildren = $main->subCapex()->get()->toArray();

			// 6. DELETE OLD CHILDREN
			$main->subCapex()->delete();

			// 7. Prepare new children
			$children = collect($subCapexData)->map(function ($item) use ($main) {
				return [
					'main_capex_id' => $main->id,
					'index' => $item['index'],
					'type_of_subcapex' => $item['type_of_subcapex'],
					'remarks' => $item['remarks'] ?? null,
					'building_number' => $item['building_number'] ?? null,
					'approved_amount' => $item['approved_amount'] ?? 0,
					'applied_amount' => $item['applied_amount'] ?? 0,
					'estimate_amount' => $item['estimate_amount'] ?? 0,
					'variance_amount' => $item['variance_amount'] ?? 0,
					'created_at' => now(),
					'updated_at' => now(),
				];
			})->toArray();

			// 8. Bulk insert again
			SubCapex::insert($children);

			// 9. LOG HISTORY HERE (after everything is done)
			$this->historyService->log(
				$main,
				Auth::id(),
				'Updated CAPEX: ' . json_encode([
					'main_changes' => $changes,
					'sub_capex' => [
						'old' => $oldChildren,
						'new' => $children
					],
					'sub_capex_count' => count($children)
				])
			);

			// 10. Return fresh data
			return MainCapex::with('subCapex')->find($main->id);
		});
	}
}

