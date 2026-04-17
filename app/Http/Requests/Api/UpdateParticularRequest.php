<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParticularRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_of_subcapex_id' => 'required|exists:type_of_subcapex,id',
            'name' => 'required|string|max:255'
        ];
    }
}