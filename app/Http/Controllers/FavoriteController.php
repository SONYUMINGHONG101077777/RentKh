<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /** Ids of the user's favourites, newest first. */
    public function index(Request $request): JsonResponse
    {
        $ids = $request->user()->favorites()
            ->orderByPivot('created_at', 'desc')
            ->pluck('listings.id')
            ->map(fn ($id) => (string) $id);

        return response()->json(['ids' => $ids]);
    }

    public function store(Request $request, Listing $listing): JsonResponse
    {
        $request->user()->favorites()->syncWithoutDetaching([$listing->id]);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, Listing $listing): JsonResponse
    {
        $request->user()->favorites()->detach($listing->id);

        return response()->json(['ok' => true]);
    }
}
