<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table): void {
            $table->foreignId('corporation_id')->nullable()->after('id')->constrained('corporations')->nullOnDelete();
            $table->index('corporation_id');
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::table('inventories')
                ->whereNull('corporation_id')
                ->orderBy('id')
                ->select(['id', 'batch_id'])
                ->chunkById(200, function ($rows): void {
                    foreach ($rows as $row) {
                        $corporationId = DB::table('batches')->where('id', $row->batch_id)->value('corporation_id');

                        if ($corporationId !== null) {
                            DB::table('inventories')->where('id', $row->id)->update(['corporation_id' => $corporationId]);
                        }
                    }
                });
        } else {
            DB::statement('UPDATE inventories i INNER JOIN batches b ON b.id = i.batch_id SET i.corporation_id = b.corporation_id WHERE i.corporation_id IS NULL');
        }

        Schema::table('sales', function (Blueprint $table): void {
            $table->foreignId('corporation_id')->nullable()->after('uuid')->constrained('corporations')->nullOnDelete();
            $table->index('corporation_id');
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::table('sales')
                ->whereNull('corporation_id')
                ->orderBy('id')
                ->select(['id', 'batch_id'])
                ->chunkById(200, function ($rows): void {
                    foreach ($rows as $row) {
                        $corporationId = DB::table('batches')->where('id', $row->batch_id)->value('corporation_id');

                        if ($corporationId !== null) {
                            DB::table('sales')->where('id', $row->id)->update(['corporation_id' => $corporationId]);
                        }
                    }
                });
        } else {
            DB::statement('UPDATE sales s INNER JOIN batches b ON b.id = s.batch_id SET s.corporation_id = b.corporation_id WHERE s.corporation_id IS NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table): void {
            $table->dropForeign(['corporation_id']);
            $table->dropIndex(['corporation_id']);
            $table->dropColumn('corporation_id');
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->dropForeign(['corporation_id']);
            $table->dropIndex(['corporation_id']);
            $table->dropColumn('corporation_id');
        });
    }
};
