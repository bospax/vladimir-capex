<?php 

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ApproverUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unit_id' => 'required|integer',
            'subunit_id' => 'nullable|integer',
            'one_charging_id' => 'required|integer',
            'approver_set_name' => 'required|string|max:255',

            'approver_id' => 'required|array|min:1',
            'approver_id.*' => 'required|integer|distinct',
        ];
    }
}