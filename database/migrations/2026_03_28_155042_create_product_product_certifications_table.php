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
        Schema::create('product_product_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->references('id')->on('products');
            $table->foreignId('product_certification_id')->references('id')->on('product_certifications');
            $table->integer('scale')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_product_certifications');
    }
};
