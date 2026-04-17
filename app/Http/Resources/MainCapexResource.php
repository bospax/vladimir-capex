<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MainCapexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            // 'one_charging_id' => $this->one_charging_id,
			'one_charging' => [
                'id' => $this->oneCharging?->id,
                'code' => $this->oneCharging?->code,
				'name' => $this->oneCharging?->name,
				'company_name' => $this->oneCharging?->company_name,
				'business_unit_name' => $this->oneCharging?->business_unit_name,
				'department_name' => $this->oneCharging?->department_name,
				'unit_name' => $this->oneCharging?->unit_name,
				'subunit_name' => $this->oneCharging?->subunit_name,
				'location_name' => $this->oneCharging?->location_name,
            ],

            'expenditure_type' => $this->expenditure_type,
            'budget_type' => $this->budget_type,
            'project_description' => $this->project_description,

            'enrolled_budget_amount' => $this->enrolled_budget_amount,
            'total_applied_amount' => $this->total_applied_amount,
            'total_difference_amount' => $this->total_difference_amount,
            'total_variance_amount' => $this->total_variance_amount,

            // 'requestor_id' => $this->requestor_id,
			'requestor' => [
                'id' => $this->requestor?->id,
				'emloyee_id' => $this->requestor?->employee_id,
                'firstname' => $this->requestor?->firstname,
                'lastname' => $this->requestor?->lastname,
            ],

			'sub_capex' => SubCapexResource::collection(
                $this->whenLoaded('subCapex')
            ),

            'estimation_level' => $this->estimation_level,
            'level' => $this->level,
            'status' => $this->status,
            'phase' => $this->phase,
            'remarks' => $this->remarks,

            'created_at' => $this->created_at,
        ];
    }
}
