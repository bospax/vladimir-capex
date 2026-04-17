<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticularResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type_of_subcapex_id' => $this->type_of_subcapex_id,
            'name' => $this->name,

            'typeOfSubCapex' => new TypeOfSubCapexResource(
                $this->whenLoaded('typeOfSubCapex')
            ),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString()
        ];
    }
}