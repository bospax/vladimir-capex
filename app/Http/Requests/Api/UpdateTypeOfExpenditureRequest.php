<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTypeOfExpenditureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
		$id = $this->route('type_of_expenditure')->id ?? null;

        return [
            'name' => 'required|string|max:255',
            'value' => "required|string|max:255|unique:type_of_expenditures,value,$id"
        ];
    }
}
