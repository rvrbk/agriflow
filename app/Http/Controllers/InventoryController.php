<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function post(Request $request)
    {
        return response()->json([
            'message' => 'Inventory item added successfully',
            'data' => $request->all()
        ], 201);
    }
}
