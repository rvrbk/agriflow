<?php

namespace App\Http\Controllers;

use App\Services\CorporationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CorporationController extends Controller
{
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
