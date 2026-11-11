<?php

namespace App\Http\Controllers;

use App\Http\Resources\ListingResource;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ListingController extends Controller
{
    /** Public list. The frontend filters locally, so this returns the newest 500. */
    public function index(Request $request)
    {
        $q = Listing::with('owner')->latest();

        if ($request->filled('province')) {
            $q->where('province', $request->string('province'));
        }
        if ($request->filled('district')) {
            $q->where('district', $request->string('district'));
        }
        if ($request->filled('type')) {
            $q->where('type', $request->string('type'));
        }
        if ($request->boolean('available')) {
            $q->where('status', 'available');
        }
        if ($request->filled('min')) {
            $q->where('price', '>=', (float) $request->input('min'));
        }
        if ($request->filled('max')) {
            $q->where('price', '<=', (float) $request->input('max'));
        }

        return ListingResource::collection($q->limit(500)->get());
    }

    public function show(Listing $listing): ListingResource
    {
        $listing->increment('views');

        return new ListingResource($listing->load('owner'));
    }

    public function mine(Request $request)
    {
        return ListingResource::collection(
            $request->user()->listings()->with('owner')->latest()->get()
        );
    }

    private function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['house', 'room', 'apartment'])],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:4000'],
            'price' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'province' => ['required', 'string', 'max:40'],
            'district' => ['required', 'string', 'max:60'],
            'commune' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'lengthM' => ['required', 'numeric', 'gt:0', 'max:1000'],
            'widthM' => ['required', 'numeric', 'gt:0', 'max:1000'],
            'floors' => ['required', 'integer', 'min:1', 'max:200'],
            'rooms' => ['nullable', 'integer', 'min:1', 'max:100'],
            'bathrooms' => ['required', 'integer', 'min:1', 'max:100'],
            'amenities' => ['array'],
            'amenities.*' => [Rule::in(Listing::AMENITIES)],
            'photos' => ['required', 'array', 'min:1', 'max:8'],
            'photos.*' => ['string', 'max:500', 'starts_with:/storage/,http'],
            'videoUrl' => ['nullable', 'string', 'max:500'],
            'mapsUrl' => ['nullable', 'string', 'max:500'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'phone' => ['required', 'string', 'min:8', 'max:32'],
            'telegram' => ['nullable', 'string', 'max:32'],
        ];
    }

    private function attributes(array $d): array
    {
        return [
            'type' => $d['type'],
            'title' => trim($d['title']),
            'description' => $d['description'] ?? null,
            'price' => $d['price'],
            'province' => $d['province'],
            'district' => $d['district'],
            'commune' => $d['commune'] ?? null,
            'address' => $d['address'] ?? null,
            'length_m' => $d['lengthM'],
            'width_m' => $d['widthM'],
            'floors' => $d['floors'],
            'rooms' => $d['type'] === 'room' ? 1 : ($d['rooms'] ?? 1),
            'bathrooms' => $d['bathrooms'],
            'amenities' => array_values(array_unique($d['amenities'] ?? [])),
            'photos' => array_values($d['photos']),
            'video_url' => $d['videoUrl'] ?? null,
            'maps_url' => $d['mapsUrl'] ?? null,
            'lat' => $d['lat'] ?? null,
            'lng' => $d['lng'] ?? null,
            'phone' => trim($d['phone']),
            'telegram' => isset($d['telegram']) && $d['telegram'] !== '' ? trim($d['telegram']) : trim($d['phone']),
        ];
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $listing = $request->user()->listings()->create(
            $this->attributes($data) + ['tone' => random_int(0, 5), 'amenities' => $data['amenities'] ?? []]
        );
        $listing->update(['code' => 'RK'.str_pad((string) $listing->id, 4, '0', STR_PAD_LEFT)]);

        return (new ListingResource($listing->load('owner')))->response()->setStatusCode(201);
    }

    public function update(Request $request, Listing $listing): ListingResource
    {
        abort_unless($listing->user_id === $request->user()->id, 403);
        $data = $request->validate($this->rules());
        $listing->update($this->attributes($data));

        return new ListingResource($listing->load('owner'));
    }

    public function setStatus(Request $request, Listing $listing): ListingResource
    {
        abort_unless($listing->user_id === $request->user()->id, 403);
        $data = $request->validate(['status' => ['required', Rule::in(['available', 'rented'])]]);
        $listing->update($data);

        return new ListingResource($listing->load('owner'));
    }

    public function destroy(Request $request, Listing $listing): JsonResponse
    {
        abort_unless($listing->user_id === $request->user()->id, 403);
        $listing->delete();

        return response()->json(['ok' => true]);
    }

    /** Called when a visitor taps Call / Telegram. */
    public function inquiry(Listing $listing): JsonResponse
    {
        $listing->increment('inquiries');

        return response()->json(['ok' => true]);
    }
}
