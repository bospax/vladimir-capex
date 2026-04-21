<?php

namespace App\Services;

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
    public function return($id)
    {
        return DB::transaction(function () use ($id) {

            $capex = MainCapex::findOrFail($id);

            $this->validateFaState($capex);

            $capex->update([
                'status' => 'returned',
            ]);

            $this->historyService->log(
                $capex->fresh(),
                Auth::id(),
                'FA RETURNED TRANSACTION'
            );

            return $capex;
        });
    }

    /**
     * SUBMIT TRANSACTION
     */
    public function submit($id)
    {
        return DB::transaction(function () use ($id) {

            $capex = MainCapex::findOrFail($id);

            $this->validateFaState($capex);

            $capex->update([
                'first_phase_level' => 2,
                'status' => 'confirmed',
                'phase' => 'for_estimate',
            ]);

            $this->historyService->log(
                $capex->fresh(),
                Auth::id(),
                'FA CONFIRMED → MOVED TO ESTIMATION PHASE'
            );

            return $capex;
        });
    }

    /**
     * STATE VALIDATION
     */
    private function validateFaState($capex)
    {
        if (
            $capex->estimation_level != 1 ||
            $capex->estimation_approving_level != 1 ||
            $capex->first_phase_level != 1 ||
            ($capex->status !== 'pending' &&
             $capex->status !== 'returned') ||
            $capex->phase !== 'for_first_phase_approval'
        ) {
            throw new \Exception('Invalid FA transaction state.');
        }
    }
}