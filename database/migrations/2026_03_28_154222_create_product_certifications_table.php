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
        Schema::create('product_certifications', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->integer('scale_from')->default(0);
            $table->integer('scale_to')->default(5);
            $table->json('visual');
            $table->json('link');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_certifications');
    }
};
