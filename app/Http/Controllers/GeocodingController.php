<?php

namespace App\Http\Controllers;

use App\Services\GeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeocodingController extends Controller
{
    /**
     * @param Request $request
     * @param GeocodingService $geocodingService
     * @return JsonResponse
     */
    public function reverse(Request $request, GeocodingService $geocodingService): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        return response()->json(
            $geocodingService->reverse((float) $validated['lat'], (float) $validated['lng'])
        );
    }
}
