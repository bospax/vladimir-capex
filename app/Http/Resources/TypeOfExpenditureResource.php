<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TypeOfExpenditureResource extends JsonResource
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
            'name' => $this->name,
            'value' => $this->value,

			'type_of_subcapex' => TypeOfSubCapexResource::collection(
                $this->whenLoaded('typeOfSubCapex')
            ),

			'created_at' => $this->created_at?->toDateTimeString(),
			'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
