<?php

namespace App\Http\Controllers;

use App\Models\Corporation;
use App\Models\FiscalYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FiscalYearController extends Controller
{
    /**
     * Get fiscal years for the current corporation
     */
    public function index(Request $request): JsonResponse
    {
        $corporationId = $request->user()->corporation_id ?? null;
        
        if (!$corporationId) {
            // Return empty array - user needs to be assigned a corporation
            return response()->json([]);
        }

        $fiscalYears = FiscalYear::where('corporation_id', $corporationId)
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(function ($fy) {
                return [
                    'id' => $fy->id,
                    'uuid' => $fy->uuid,
                    'name' => $fy->name,
                    'start_date' => $fy->start_date,
                    'end_date' => $fy->end_date,
                    'is_active' => $fy->is_active,
                    'is_closed' => $fy->is_closed,
                    'closed_at' => $fy->closed_at,
                    'total_revenue' => $fy->total_revenue,
                    'net_profit_loss' => $fy->net_profit_loss,
                    'created_at' => $fy->created_at,
                ];
            });

        return response()->json($fiscalYears);
    }

    /**
     * Create a new fiscal year
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ]);

        $corporationId = $request->user()->corporation_id ?? null;
        
        if (!$corporationId) {
            return response()->json([
                'message' => 'Please assign a corporation to this user before creating fiscal years.',
            ], 403);
        }

        // Deactivate any existing active fiscal year
        FiscalYear::where('corporation_id', $corporationId)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $fiscalYear = FiscalYear::create([
            'corporation_id' => $corporationId,
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'is_active' => true,
            'is_closed' => false,
        ]);

        return response()->json([
            'message' => 'Fiscal year created.',
            'fiscal_year' => [
                'id' => $fiscalYear->id,
                'uuid' => $fiscalYear->uuid,
                'name' => $fiscalYear->name,
                'start_date' => $fiscalYear->start_date,
                'end_date' => $fiscalYear->end_date,
                'is_active' => $fiscalYear->is_active,
            ],
        ], 201);
    }

    /**
     * Close a fiscal year (soft delete approach - mark as closed)
     */
    public function close(string $uuid, Request $request): JsonResponse
    {
        $fiscalYear = FiscalYear::where('uuid', $uuid)->first();

        if (!$fiscalYear) {
            return response()->json([
                'message' => 'Fiscal year not found.',
            ], 404);
        }

        // Verify user belongs to the corporation
        if ($fiscalYear->corporation_id !== $request->user()->corporation_id) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        if ($fiscalYear->is_closed) {
            return response()->json([
                'message' => 'Fiscal year is already closed.',
            ], 400);
        }

        // Close the fiscal year
        DB::transaction(function () use ($fiscalYear, $request) {
            // Update fiscal year totals before closing
            $fiscalYear->updateTotals();

            $fiscalYear->update([
                'is_closed' => true,
                'closed_at' => now(),
                'closed_by' => $request->user()->id,
                'is_active' => false,
            ]);

            // Find or create a new fiscal year starting the day after this one ends
            $nextStartDate = $fiscalYear->end_date->copy()->addDay();
            $nextEndDate = $nextStartDate->copy()->addYear()->subDay();
            $nextName = 'FY ' . $nextStartDate->format('Y') . '/' . $nextEndDate->format('Y');

            // Check if a fiscal year already exists for this period
            $existing = FiscalYear::where('corporation_id', $fiscalYear->corporation_id)
                ->where('start_date', $nextStartDate)
                ->first();

            if (!$existing) {
                FiscalYear::create([
                    'corporation_id' => $fiscalYear->corporation_id,
                    'name' => $nextName,
                    'start_date' => $nextStartDate,
                    'end_date' => $nextEndDate,
                    'is_active' => true,
                    'is_closed' => false,
                ]);
            } else {
                // Activate existing fiscal year
                $existing->update(['is_active' => true]);
            }
        });

        return response()->json([
            'message' => 'Fiscal year closed successfully.',
            'fiscal_year' => [
                'uuid' => $fiscalYear->uuid,
                'name' => $fiscalYear->name,
                'is_closed' => true,
                'closed_at' => $fiscalYear->closed_at,
            ],
        ]);
    }

    /**
     * Get the current active fiscal year
     */
    public function current(Request $request): JsonResponse
    {
        $corporationId = $request->user()->corporation_id ?? null;
        
        if (!$corporationId) {
            return response()->json([
                'message' => 'No active fiscal year. Please assign a corporation to this user.',
            ], 404);
        }

        $fiscalYear = FiscalYear::where('corporation_id', $corporationId)
            ->where('is_active', true)
            ->where('is_closed', false)
            ->first();

        if (!$fiscalYear) {
            // Try to find any fiscal year, or return the most recent closed one
            $fiscalYear = FiscalYear::where('corporation_id', $corporationId)
                ->orderBy('start_date', 'desc')
                ->first();
        }

        if (!$fiscalYear) {
            return response()->json([
                'message' => 'No fiscal year found.',
            ], 404);
        }

        return response()->json([
            'uuid' => $fiscalYear->uuid,
            'name' => $fiscalYear->name,
            'start_date' => $fiscalYear->start_date,
            'end_date' => $fiscalYear->end_date,
            'is_active' => $fiscalYear->is_active,
            'is_closed' => $fiscalYear->is_closed,
        ]);
    }

    /**
     * Generate year-end report
     */
    public function report(string $uuid, Request $request): JsonResponse
    {
        $fiscalYear = FiscalYear::where('uuid', $uuid)
            ->with(['sales'])
            ->first();

        if (!$fiscalYear) {
            return response()->json([
                'message' => 'Fiscal year not found.',
            ], 404);
        }

        // Verify user belongs to the corporation
        if ($fiscalYear->corporation_id !== $request->user()->corporation_id) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $report = [
            'fiscal_year' => [
                'name' => $fiscalYear->name,
                'start_date' => $fiscalYear->start_date,
                'end_date' => $fiscalYear->end_date,
            ],
            'summary' => [
                'total_revenue' => $fiscalYear->total_revenue,
                'net_profit_loss' => $fiscalYear->net_profit_loss,
            ],
            'revenue_by_currency' => $this->getRevenueByCurrency($fiscalYear),
            'sales_count' => $fiscalYear->sales()->count(),
        ];

        return response()->json($report);
    }

    /**
     * Get sales for a fiscal year
     */
    public function sales(string $uuid, Request $request): JsonResponse
    {
        $fiscalYear = FiscalYear::where('uuid', $uuid)->first();

        if (!$fiscalYear) {
            return response()->json([
                'message' => 'Fiscal year not found.',
            ], 404);
        }

        // Verify user belongs to the corporation
        if ($fiscalYear->corporation_id !== $request->user()->corporation_id) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 403);
        }

        $sales = $fiscalYear->sales()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($sales);
    }

    protected function getRevenueByCurrency(FiscalYear $fiscalYear): array
    {
        return $fiscalYear->sales()
            ->selectRaw('currency, SUM(total_value) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->toArray();
    }

}
