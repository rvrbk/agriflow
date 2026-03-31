<?php

namespace App\Http\Controllers;


use App\Services\HarvestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HarvestController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function post(Request $request): JsonResponse
    {
        app()->make(HarvestService::class)->store($request->all());

        return response()->json([
            $request->all()
        ], 201);
    }
}
