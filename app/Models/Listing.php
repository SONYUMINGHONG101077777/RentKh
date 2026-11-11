<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Listing extends Model
{
    public const AMENITIES = ['ac', 'wifi', 'parking', 'furnished', 'privateBath', 'kitchen', 'balcony', 'security'];

    protected $fillable = [
        'code', 'user_id', 'type', 'title', 'description', 'price', 'province', 'district', 'commune',
        'address', 'length_m', 'width_m', 'floors', 'rooms', 'bathrooms', 'amenities', 'photos',
        'video_url', 'tone', 'maps_url', 'lat', 'lng', 'phone', 'telegram', 'status', 'views', 'inquiries',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'length_m' => 'float',
            'width_m' => 'float',
            'lat' => 'float',
            'lng' => 'float',
            'amenities' => 'array',
            'photos' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
