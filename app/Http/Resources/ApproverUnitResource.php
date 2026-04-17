<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApproverUnitResource extends JsonResource
{
    public function toArray($request)
    {
        $first = $this->first();

        return [
            'one_charging' => $first->oneCharging,

            // 'unit' => [
            //     'id' => $first->unit?->id,
            //     'unit_name' => $first->unit?->unit_name,
            //     'unit_code' => $first->unit?->unit_code,
            // ],

            // 'subunit' => $first->subunit ? [
            //     'id' => $first->subunit->id,
            //     'subunit_code' => $first->subunit->subunit_code,
            //     'subunit_name' => $first->subunit->subunit_name,
            // ] : null,

			'approver_set_name' => $first->approver_set_name,

            'approvers' => $this->map(function ($item) {

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
}
