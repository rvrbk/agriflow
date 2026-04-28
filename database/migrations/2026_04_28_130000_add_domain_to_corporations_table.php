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
        Schema::table('corporations', function (Blueprint $table): void {
            $table->string('domain')->nullable()->after('uuid')->unique();
        });

        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (is_string($host) && $host !== '') {
            DB::table('corporations')
                ->whereNull('domain')
                ->orderBy('id')
                ->limit(1)
                ->update(['domain' => $host]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('corporations', function (Blueprint $table): void {
            $table->dropUnique(['domain']);
            $table->dropColumn('domain');
        });
    }
};
