<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function image(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:10240']]);
        // Needs `php artisan storage:link` so /storage/... is public.
        $path = $request->file('file')->store('listings', 'public');

        return response()->json(['url' => '/storage/'.$path], 201);
    }

    public function video(Request $request): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:mp4', 'max:102400']]);
        $path = $request->file('file')->store('videos', 'public');

        return response()->json(['url' => '/storage/'.$path, 'name' => $request->file('file')->getClientOriginalName()], 201);
    }
}
