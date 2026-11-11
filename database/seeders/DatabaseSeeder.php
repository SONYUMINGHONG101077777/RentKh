<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Demo data. Log in with phone 077 246 810 (or minghong@example.com) and password "password123".
     * Do NOT run this in production.
     */
    public function run(): void
    {
        $rows = json_decode(file_get_contents(__DIR__.'/data/listings.json'), true, flags: JSON_THROW_ON_ERROR);

        $owners = [];
        foreach ($rows as $r) {
            $key = $r['ownerId'];
            if (! isset($owners[$key])) {
                $isDemo = $key === 'me';
                $owners[$key] = User::firstOrCreate(
                    ['phone_digits' => User::digits($r['phone'])],
                    [
                        'name' => $r['ownerName'],
                        'phone' => $r['phone'],
                        'email' => $isDemo ? 'minghong@example.com' : null,
                        'password' => 'password123',
                        'phone_verified_at' => now(),
                    ]
                );
            }
        }

        foreach ($rows as $r) {
            $created = Carbon::createFromTimestampMs($r['createdAt']);
            $listing = Listing::create([
                'code' => $r['code'],
                'user_id' => $owners[$r['ownerId']]->id,
                'type' => $r['type'],
                'title' => $r['title'],
                'description' => $r['description'],
                'price' => $r['price'],
                'province' => $r['province'],
                'district' => $r['district'],
                'commune' => $r['commune'] ?: null,
                'address' => $r['address'] ?: null,
                'length_m' => $r['lengthM'],
                'width_m' => $r['widthM'],
                'floors' => $r['floors'],
                'rooms' => $r['rooms'],
                'bathrooms' => $r['bathrooms'],
                'amenities' => $r['amenities'],
                'photos' => [], // empty -> the frontend shows the placeholder artwork
                'tone' => $r['tone'],
                'phone' => $r['phone'],
                'telegram' => $r['telegram'],
                'status' => $r['status'],
                'views' => $r['views'],
                'inquiries' => $r['inquiries'],
            ]);
            $listing->forceFill(['created_at' => $created, 'updated_at' => $created])->save();
        }

        // A couple of favourites for the demo user.
        $demo = $owners['me'];
        $demo->favorites()->syncWithoutDetaching(
            Listing::whereIn('code', ['RK1001', 'RK1004'])->pluck('id')->all()
        );
    }
}
