<?php

namespace App\Http\Controllers;

use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function post(Request $request)
    {
        app()->make(InventoryService::class)->store($request->all());

        return response()->json([
            'message' => 'Inventory item added successfully',
            'data' => $request->all()
        ], 201);
    }
}
