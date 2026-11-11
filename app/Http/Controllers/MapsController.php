<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Resolves short Google Maps links (maps.app.goo.gl/...) to coordinates.
 * Only Google hosts are followed, to avoid being used as an open proxy (SSRF).
 */
class MapsController extends Controller
{
    private const HOST = '/^(www\.)?(google\.[a-z.]+|maps\.google\.[a-z.]+|maps\.app\.goo\.gl|goo\.gl)$/i';

    private function allowed(string $url): bool
    {
        $p = parse_url($url);

        return ($p['scheme'] ?? '') === 'https'
            && isset($p['host'])
            && preg_match(self::HOST, $p['host']) === 1;
    }

    public function resolve(Request $request): JsonResponse
    {
        $data = $request->validate(['url' => ['required', 'string', 'max:500']]);
        $url = trim($data['url']);

        if (! $this->allowed($url)) {
            throw ValidationException::withMessages(['url' => 'This is not a Google Maps link.']);
        }

        for ($hop = 0; $hop < 5; $hop++) {
            if ($coords = $this->coords($url)) {
                return response()->json(['url' => $url] + $coords);
            }
            $res = Http::timeout(6)->withOptions(['allow_redirects' => false])
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 RentKh'])->get($url);
            $next = $res->header('Location');
            if (! $res->redirect() || ! $next) {
                break;
            }
            if (str_starts_with($next, '/')) {
                $p = parse_url($url);
                $next = $p['scheme'].'://'.$p['host'].$next;
            }
            if (! $this->allowed($next)) {
                break;
            }
            $url = $next;
        }

        return response()->json(['url' => $url, 'lat' => null, 'lng' => null]);
    }

    private function coords(string $url): ?array
    {
        $patterns = [
            '/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/',
            '/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/',
            '/[?&](?:q|ll|query|destination)=(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/',
        ];
        foreach ($patterns as $re) {
            if (preg_match($re, $url, $m)) {
                $lat = (float) $m[1];
                $lng = (float) $m[2];
                if (abs($lat) <= 90 && abs($lng) <= 180) {
                    return ['lat' => $lat, 'lng' => $lng];
                }
            }
        }

        return null;
    }
}
