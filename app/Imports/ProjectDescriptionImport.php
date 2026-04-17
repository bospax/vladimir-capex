<?php

namespace App\Imports;

use App\Models\ProjectDescription;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;
use Carbon\Carbon;

class ProjectDescriptionImport implements 
    ToModel,
    WithHeadingRow,
    WithValidation,
    SkipsOnFailure
{
    use SkipsFailures;

    public function model(array $row)
    {
        return new ProjectDescription([
            'id_number' => $row['id_number'] ?? null,
            'description' => $row['description'],
            'type_of_expenditure' => $row['type_of_expenditure'] ?? null,
            'years' => $row['years'] ?? null,
            'quantity' => $row['quantity'] ?? null,
            'unit_cost' => $row['unit_cost'] ?? null,
            'enrolled_budget_amount' => $row['enrolled_budget_amount'] ?? 0,
            'remarks' => $row['remarks'] ?? null,
            'date_applied' => $this->parseDate($row['date_applied'] ?? null),
            'cost_applied' => $row['cost_applied'] ?? null,
        ]);
    }

    private function parseDate($value)
    {
        try {
            return $value ? Carbon::parse($value) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    // 🔥 VALIDATION RULES
    public function rules(): array
    {
        return [
            '*.id_number' => 'nullable|max:255',
            '*.description' => 'required|string|max:255',
            '*.type_of_expenditure' => 'nullable|string|max:255',
            '*.years' => 'nullable|numeric|min:0',
            '*.quantity' => 'nullable|numeric|min:0',
            '*.unit_cost' => 'nullable|numeric|min:0',
            '*.enrolled_budget_amount' => 'nullable|numeric|min:0',
            '*.remarks' => 'nullable|string|max:500',
            '*.date_applied' => 'nullable',
            '*.cost_applied' => 'nullable|string|max:255',
        ];
    }

    // 🔥 CUSTOM ERROR MESSAGES
    public function customValidationMessages()
    {
        return [
            '*.description.required' => 'Description is required',
            '*.years.numeric' => 'Years must be a number',
            '*.quantity.numeric' => 'Quantity must be a number',
            '*.unit_cost.numeric' => 'Unit cost must be a number',
            '*.enrolled_budget_amount.numeric' => 'Budget must be a number',
            '*.date_applied.date' => 'Invalid date format',
        ];
    }
}
