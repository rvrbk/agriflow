<?php

namespace App\Http\Controllers;

use App\Models\Corporation;
use App\Services\CorporationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CorporationController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function get(): JsonResponse
    {
        $corporation = Corporation::query()->latest('id')->first();

        return response()->json($corporation);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function post(Request $request): JsonResponse
    {
        app()->make(CorporationService::class)->store($request->all());

        return response()->json([
            $request->all()
        ], 201);
    }
}
