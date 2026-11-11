<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['name', 'phone', 'phone_digits', 'email', 'password', 'prefs', 'phone_verified_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'prefs' => 'array',
            'phone_verified_at' => 'datetime',
        ];
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class, 'favorites')->withPivot('created_at');
    }

    public static function digits(string $phone): string
    {
        $d = preg_replace('/\D/', '', $phone) ?? '';

        // Local Cambodian format 077 246 810 -> 85577246810, so both spellings log in the same.
        return str_starts_with($d, '0') ? '855'.substr($d, 1) : $d;
    }
}
