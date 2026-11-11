<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Same shape as the frontend `Listing` type (camelCase). */
class ListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $owner = $this->owner;

        return [
            'id' => (string) $this->id,
            'code' => $this->code,
            'ownerId' => (string) $this->user_id,
            'ownerName' => $owner?->name ?? '',
            'ownerVerified' => (bool) $owner?->phone_verified_at,
            'ownerSince' => (int) ($owner?->created_at?->year ?? now()->year),
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description ?? '',
            'price' => $this->price,
            'province' => $this->province,
            'district' => $this->district,
            'commune' => $this->commune ?? '',
            'address' => $this->address ?? '',
            'lengthM' => $this->length_m,
            'widthM' => $this->width_m,
            'floors' => $this->floors,
            'rooms' => $this->rooms,
            'bathrooms' => $this->bathrooms,
            'amenities' => $this->amenities ?? [],
            'photos' => $this->photos ?? [],
            'videoUrl' => $this->video_url,
            'tone' => $this->tone,
            'mapsUrl' => $this->maps_url ?? '',
            'lat' => $this->lat,
            'lng' => $this->lng,
            'phone' => $this->phone,
            'telegram' => $this->telegram ?? '',
            'status' => $this->status,
            'views' => $this->views,
            'inquiries' => $this->inquiries,
            'createdAt' => $this->created_at?->getTimestampMs(),
        ];
    }
}
