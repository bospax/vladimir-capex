<?php 

namespace App\Services;

use App\Models\MainCapex;
use App\Models\MainCapexHistory;

class MainCapexHistoryService
{
	public function getHistory($capexId)
    {
        $histories = MainCapexHistory::with(['approver', 'requestor'])
            ->where('main_capex_id', $capexId)
            ->orderBy('created_at', 'asc')
            ->get();

        return $histories->map(function ($item) {
            return [
                'id' => $item->id,
                'date' => $item->created_at->toDateTimeString(),

                'status' => $item->status,
                'phase' => $item->phase,

                'remarks' => $item->remarks,

				'approver_id' => $item->approver_id,

                'approver' => $item->approver
                    ? $item->approver->firstname
                    : null,

                'requestor' => $item->requestor
                    ? $item->requestor->firstname
                    : null,

                'change_log' => $item->change_log,

                'snapshot' => [
                    'capex_number' => $item->capex_number,
                    'project_description' => $item->project_description,
                    'budget' => [
                        'enrolled' => $item->enrolled_budget_amount,
                        'applied' => $item->total_applied_amount,
                        'difference' => $item->total_difference_amount,
                        'variance' => $item->total_variance_amount,
                    ]
                ]
            ];
        });
    }

    public function log(MainCapex $capex, ?int $approverId = null, ?string $approverSetName = null, ?string $changeLog = null)
    {
        MainCapexHistory::create([
            'main_capex_id' => $capex->id,

            'capex_number' => $capex->capex_number,
            'one_charging_id' => $capex->one_charging_id,
            'expenditure_type' => $capex->expenditure_type,
            'budget_type' => $capex->budget_type,
            'project_description' => $capex->project_description,
            'enrolled_budget_amount' => $capex->enrolled_budget_amount,
            'total_applied_amount' => $capex->total_applied_amount,
            'total_difference_amount' => $capex->total_difference_amount,
            'total_variance_amount' => $capex->total_variance_amount,
            'requestor_id' => $capex->requestor_id,
            'form_type' => $capex->form_type,
            'first_phase_level' => $capex->first_phase_level,
            'estimation_level' => $capex->estimation_level,
            'estimation_approving_level' => $capex->estimation_approving_level,
            'major_level' => $capex->major_level,
            'status' => $capex->status,
            'phase' => $capex->phase,
            'remarks' => $capex->remarks,

            'approver_id' => $approverId,
            'approver_set_name' => $approverSetName,
            'change_log' => $changeLog,
            'revision_no' => $capex->revision_no,
        ]);
    }
}