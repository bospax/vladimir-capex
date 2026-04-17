<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMainCapexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'one_charging_id' => 'integer',
            'expenditure_type' => 'required|string|max:255',
            'budget_type' => 'required|string|max:255',
            'project_description' => 'required|string',
            'enrolled_budget_amount' => 'nullable|numeric|min:0',
            'total_applied_amount' => 'nullable|numeric|min:0',
            'total_difference_amount' => 'nullable|numeric|min:0',
            'total_variance_amount' => 'nullable|numeric|min:0',
            // 'requestor_id' => 'required|integer',
            'remarks' => 'nullable|string',

			// SUB CAPEX DATA
            'sub_capex' => 'required|array|min:1',
            'sub_capex.*.index' => 'required|string',
            'sub_capex.*.type_of_subcapex' => 'required|string',
			'sub_capex.*.remarks' => 'nullable|string',
			'sub_capex.*.building_number' => 'nullable|string',
            'sub_capex.*.approved_amount' => 'nullable|numeric|min:0',
            'sub_capex.*.applied_amount' => 'nullable|numeric|min:0',
            'sub_capex.*.estimate_amount' => 'nullable|numeric|min:0',
            'sub_capex.*.variance_amount' => 'nullable|numeric|min:0',
        ];
    }
}
