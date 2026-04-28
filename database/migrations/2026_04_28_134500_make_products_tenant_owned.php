<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('corporation_id')->nullable()->after('uuid')->constrained('corporations')->nullOnDelete();
            $table->index('corporation_id');
        });

        // First pass: assign ownership from inventory/sales usage when unambiguous.
        DB::statement('UPDATE products p INNER JOIN inventories i ON i.product_id = p.id SET p.corporation_id = i.corporation_id WHERE p.corporation_id IS NULL AND i.corporation_id IS NOT NULL');
        DB::statement('UPDATE products p INNER JOIN sales s ON s.product_id = p.id SET p.corporation_id = s.corporation_id WHERE p.corporation_id IS NULL AND s.corporation_id IS NOT NULL');

        $sharedFromInventory = DB::table('inventories')
            ->select('product_id')
            ->whereNotNull('corporation_id')
            ->groupBy('product_id')
            ->havingRaw('COUNT(DISTINCT corporation_id) > 1')
            ->pluck('product_id')
            ->all();

        $sharedFromSales = DB::table('sales')
            ->select('product_id')
            ->whereNotNull('corporation_id')
            ->groupBy('product_id')
            ->havingRaw('COUNT(DISTINCT corporation_id) > 1')
            ->pluck('product_id')
            ->all();

        $sharedProductIds = array_values(array_unique(array_merge($sharedFromInventory, $sharedFromSales)));

        foreach ($sharedProductIds as $productId) {
            $product = DB::table('products')->where('id', $productId)->first();

            if (! $product) {
                continue;
            }

            $tenantIds = DB::table('inventories')
                ->where('product_id', $productId)
                ->whereNotNull('corporation_id')
                ->pluck('corporation_id')
                ->merge(
                    DB::table('sales')
                        ->where('product_id', $productId)
                        ->whereNotNull('corporation_id')
                        ->pluck('corporation_id')
                )
                ->unique()
                ->values();

            if ($tenantIds->count() <= 1) {
                continue;
            }

            $primaryTenantId = (int) $tenantIds->first();

            DB::table('products')
                ->where('id', $productId)
                ->update(['corporation_id' => $primaryTenantId]);

            $properties = DB::table('product_properties')
                ->where('product_id', $productId)
                ->get();

            foreach ($tenantIds->slice(1) as $tenantIdValue) {
                $tenantId = (int) $tenantIdValue;

                $tenantProductId = DB::table('products')
                    ->where('corporation_id', $tenantId)
                    ->where('name', $product->name)
                    ->where('unit', $product->unit)
                    ->value('id');

                if (! $tenantProductId) {
                    $tenantProductId = DB::table('products')->insertGetId([
                        'uuid' => (string) Str::uuid(),
                        'corporation_id' => $tenantId,
                        'name' => $product->name,
                        'code' => $product->code,
                        'code_type' => $product->code_type,
                        'unit' => $product->unit,
                        'created_at' => $product->created_at,
                        'updated_at' => $product->updated_at,
                    ]);

                    foreach ($properties as $property) {
                        DB::table('product_properties')->insert([
                            'product_id' => $tenantProductId,
                            'type' => $property->type,
                            'system' => $property->system,
                            'key' => $property->key,
                            'value' => $property->value,
                            'sequence' => $property->sequence,
                            'created_at' => $property->created_at,
                            'updated_at' => $property->updated_at,
                        ]);
                    }
                }

                DB::table('inventories')
                    ->where('product_id', $productId)
                    ->where('corporation_id', $tenantId)
                    ->update(['product_id' => $tenantProductId]);

                DB::table('sales')
                    ->where('product_id', $productId)
                    ->where('corporation_id', $tenantId)
                    ->update(['product_id' => $tenantProductId]);
            }
        }

        // Any orphan/global products are assigned to the first tenant to avoid leaking globally.
        $fallbackTenantId = DB::table('corporations')->orderBy('id')->value('id');

        if ($fallbackTenantId) {
            DB::table('products')
                ->whereNull('corporation_id')
                ->update(['corporation_id' => $fallbackTenantId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropForeign(['corporation_id']);
            $table->dropIndex(['corporation_id']);
            $table->dropColumn('corporation_id');
        });
    }
};
