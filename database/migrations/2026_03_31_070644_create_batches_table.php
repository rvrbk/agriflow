<?php

use App\Enums\QualityEnum;
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
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->foreignId('corporation_id')->references('id')->on('corporations');
            $table->timestamp('harvested_on');
            $table->timestamp('expires_on')->nullable();
            $table->enum('quality', array_column(QualityEnum::cases(), 'value'))->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
