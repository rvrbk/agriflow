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

        DB::statement('UPDATE inventories i INNER JOIN batches b ON b.id = i.batch_id SET i.corporation_id = b.corporation_id WHERE i.corporation_id IS NULL');

        Schema::table('sales', function (Blueprint $table): void {
            $table->foreignId('corporation_id')->nullable()->after('uuid')->constrained('corporations')->nullOnDelete();
            $table->index('corporation_id');
        });

        DB::statement('UPDATE sales s INNER JOIN batches b ON b.id = s.batch_id SET s.corporation_id = b.corporation_id WHERE s.corporation_id IS NULL');
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
