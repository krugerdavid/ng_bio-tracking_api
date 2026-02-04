<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'user_id' => (string) $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role?->value,
            'created_by' => null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'member_id' => $this->when($this->relationLoaded('member') && $this->member, $this->member?->id),
        ];
    }
}
