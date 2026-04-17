<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TypeOfSubCapexResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type_of_expenditure_id' => $this->type_of_expenditure_id,
            'name' => $this->name,
            'with_remarks' => $this->with_remarks,

            'type_of_expenditure' => new TypeOfExpenditureResource(
                $this->whenLoaded('typeOfExpenditure')
            ),

			'particulars' => ParticularResource::collection(
				$this->whenLoaded('particulars')
			),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString()
        ];
    }
}