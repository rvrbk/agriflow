<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        $products = Product::query()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Product $product): array {
                return [
                    'uuid' => $product->uuid,
                    'name' => $product->getTranslation('name', 'en', false) ?: '',
                    'code' => $product->code,
                    'code_type' => $product->code_type,
                    'unit' => $product->unit,
                ];
            })
            ->values();

        return response()->json($products);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function post(Request $request): JsonResponse
    {
        app()->make(ProductService::class)->store($request->all());

        return response()->json([
            $request->all()
        ], 201);
    }

    /**
     * @param string $uuid
     * @return JsonResponse
     */
    public function delete(string $uuid): JsonResponse
    {
        $deleted = app()->make(ProductService::class)->deleteByUuid($uuid);

        if (!$deleted) {
            return response()->json([
                'message' => 'Product not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Product deleted.',
        ]);
    }
}
