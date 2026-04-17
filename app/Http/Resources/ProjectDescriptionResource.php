<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectDescriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
			'id' => $this->id,
			'id_number' => $this->id_number,
			'description' => $this->description,
			'type_of_expenditure' => $this->type_of_expenditure,	
			'years' => $this->years,
			'quantity' => $this->quantity,
			'unit_cost' => $this->unit_cost,
			'enrolled_budget_amount' => $this->enrolled_budget_amount,
			'remarks' => $this->remarks,
			'date_applied' => $this->date_applied,
			'cost_applied' => $this->cost_applied,
			'created_at' => $this->created_at?->toDateTimeString(),
			'updated_at' => $this->updated_at?->toDateTimeString(),
		];
    }
}
