<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'fiscal_year_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropConstrainedForeignId('fiscal_year_id');
            });
        }

        Schema::dropIfExists('fiscal_years');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('fiscal_years')) {
            Schema::create('fiscal_years', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('corporation_id')->constrained('corporations')->cascadeOnDelete();
                $table->string('name');
                $table->date('start_date');
                $table->date('end_date');
                $table->boolean('is_active')->default(false);
                $table->boolean('is_closed')->default(false);
                $table->date('closed_at')->nullable();
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->decimal('total_revenue', 15, 2)->default(0);
                $table->decimal('total_expenses', 15, 2)->default(0);
                $table->decimal('net_profit_loss', 15, 2)->default(0);
                $table->json('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('sales') && !Schema::hasColumn('sales', 'fiscal_year_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->foreignId('fiscal_year_id')->nullable()->constrained('fiscal_years')->nullOnDelete();
            });
        }
    }
};
