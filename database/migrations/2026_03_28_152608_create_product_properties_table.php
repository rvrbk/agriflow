<?php

use App\Enums\ProductPropertyTypeEnum;
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
        Schema::create('product_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->references('id')->on('products');
            $table->enum('type', array_column(ProductPropertyTypeEnum::cases(), 'value'))->default(ProductPropertyTypeEnum::STRING->value);
            $table->boolean('system')->default(false);
            $table->json('key');
            $table->json('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_properties');
    }
};
