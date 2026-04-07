<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
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
        $linkedProductIds = Inventory::query()
            ->whereNotNull('batch_id')
            ->pluck('product_id')
            ->flip();

        $products = Product::query()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Product $product) use ($linkedProductIds): array {
                return [
                    'uuid' => $product->uuid,
                    'name' => $product->getTranslation('name', 'en', false) ?: '',
                    'code' => $product->code,
                    'code_type' => $product->code_type,
                    'unit' => $product->unit,
                    'is_linked_to_harvest' => isset($linkedProductIds[$product->id]),
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
        $result = app()->make(ProductService::class)->deleteByUuid($uuid);

        if ($result === ProductService::DELETE_NOT_FOUND) {
            return response()->json([
                'message' => 'Product not found.',
            ], 404);
        }

        if ($result === ProductService::DELETE_LINKED_TO_HARVEST) {
            return response()->json([
                'message' => 'Product is linked to harvest records and cannot be deleted.',
            ], 409);
        }

        return response()->json([
            'message' => 'Product deleted.',
        ]);
    }
}
