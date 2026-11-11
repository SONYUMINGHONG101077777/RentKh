<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email ?? '',
            'since' => (int) $this->created_at?->year,
            'prefs' => $this->prefs ?? ['messages' => true, 'tips' => false],
        ];
    }
}
