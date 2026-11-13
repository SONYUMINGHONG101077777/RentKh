<?php

  
namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function image(Request $request): JsonResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png',
                'max:10240',
            ],
        ]);

        return $this->uploadToSupabase(
            $request,
            'listings'
        );
    }

    public function video(Request $request): JsonResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:mp4',
                'max:102400',
            ],
        ]);

        return $this->uploadToSupabase(
            $request,
            'videos',
            true
        );
    }

    private function uploadToSupabase(
        Request $request,
        string $folder,
        bool $includeName = false
    ): JsonResponse {
        $file = $request->file('file');

        $baseUrl = rtrim(
            (string) config('services.supabase.url'),
            '/'
        );

        $key = config('services.supabase.secret_key');
        $bucket = config('services.supabase.bucket');

        if (!$baseUrl || !$key || !$bucket) {
            return response()->json([
                'message' => 'Supabase storage is not configured.',
            ], 500);
        }

        $path = $folder . '/' .
            Str::uuid() . '.' .
            $file->extension();

        $uploadUrl = $baseUrl .
            '/storage/v1/object/' .
            $bucket . '/' . $path;

        try {
            $stream = fopen(
                $file->getRealPath(),
                'rb'
            );

            if ($stream === false) {
                throw new \RuntimeException(
                    'Unable to read uploaded file.'
                );
            }

            try {
                $response = Http::withHeaders([
                    'apikey' => $key,
                    'Authorization' => 'Bearer ' . $key,
                    'Content-Type' => $file->getMimeType(),
                    'x-upsert' => 'false',
                ])
                    ->withBody(
                        $stream,
                        $file->getMimeType()
                    )
                    ->timeout(120)
                    ->post($uploadUrl);
            } finally {
                fclose($stream);
            }

            if ($response->failed()) {
                report(new \RuntimeException(
                    'Supabase upload failed: HTTP ' .
                    $response->status() . ' ' .
                    $response->body()
                ));

                return response()->json([
                    'message' => 'File upload failed.',
                ], 502);
            }

            $publicUrl = $baseUrl .
                '/storage/v1/object/public/' .
                $bucket . '/' . $path;

            $data = [
                'url' => $publicUrl,
            ];

            if ($includeName) {
                $data['name'] =
                    $file->getClientOriginalName();
            }

            return response()->json($data, 201);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Storage connection failed.',
            ], 502);
        }
    }
}
