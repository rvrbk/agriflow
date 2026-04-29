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
    public function list(): JsonResponse
    {
        $currentTenant = Corporation::current();

        if (! $currentTenant) {
            return response()->json([]);
        }

        $corporations = Corporation::query()
            ->when($currentTenant, fn ($query) => $query->whereKey($currentTenant->id))
            ->orderBy('name')
            ->get(['uuid', 'name'])
            ->values();

        return response()->json($corporations);
    }

    /**
     * @return JsonResponse
     */
    public function get(): JsonResponse
    {
        $currentTenant = Corporation::current();

        if (! $currentTenant) {
            return response()->json(null);
        }

        $corporation = Corporation::query()
            ->when($currentTenant, fn ($query) => $query->whereKey($currentTenant->id))
            ->latest('id')
            ->first();

        return response()->json($corporation);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function post(Request $request): JsonResponse
    {
        $corporation = app()->make(CorporationService::class)->store($request->all());

        $user = $request->user();
        if ($corporation && $user && $user->corporation_id !== $corporation->id) {
            $user->forceFill(['corporation_id' => $corporation->id])->save();
        }

        if ($corporation) {
            $corporation->makeCurrent();
        }

        return response()->json([
            $request->all()
        ], 201);
    }
}
