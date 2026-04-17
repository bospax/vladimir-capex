<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreTypeOfSubCapexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_of_expenditure_id' => 'required|integer|exists:type_of_expenditures,id',
            'name' => 'required|string|max:255',
            'with_remarks' => 'nullable|integer|in:0,1'
        ];
    }
}