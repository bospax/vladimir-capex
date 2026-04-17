<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubCapexResource extends JsonResource
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
            'index' => $this->index,
            'type_of_subcapex' => $this->type_of_subcapex,
            'approved_amount' => $this->approved_amount,
            'applied_amount' => $this->applied_amount,
            'estimate_amount' => $this->estimate_amount,
            'variance_amount' => $this->variance_amount,
        ];
    }
}
