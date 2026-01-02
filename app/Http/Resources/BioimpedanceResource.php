<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BioimpedanceResource extends JsonResource
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
            'member_id' => $this->member_id,
            'date' => $this->date ? $this->date->format('Y-m-d') : null,
            'height' => $this->height,
            'weight' => $this->weight,
            'imc' => $this->imc,
            'body_fat_percentage' => $this->body_fat_percentage,
            'muscle_mass_percentage' => $this->muscle_mass_percentage,
            'kcal' => $this->kcal,
            'metabolic_age' => $this->metabolic_age,
            'visceral_fat_percentage' => $this->visceral_fat_percentage,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
