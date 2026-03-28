<?php

use App\Enums\CodeTypeEnum;
use App\Enums\UnitEnum;
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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->json('name');
            $table->string('code')->nullable();
            $table->enum('code_type', array_column(CodeTypeEnum::cases(), 'value'))->nullable();
            $table->enum('unit', array_column(UnitEnum::cases(), 'value'))->default(UnitEnum::PCS->value);
            $table->foreignId('created_by')->references('id')->on('users');
            $table->foreignId('updated_by')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
