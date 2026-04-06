<?php

namespace App\Http\Controllers;

use App\Services\CountryService;
use Illuminate\Http\JsonResponse;

class CountryController extends Controller
{
    /**
     * @param CountryService $countryService
     * @return JsonResponse
     */
    public function list(CountryService $countryService): JsonResponse
    {
        return response()->json($countryService->list());
    }
}
