<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parliamentary_fronts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('politician_id')->constrained('politicians');
            $table->string('external_id', 50);
            $table->string('title', 500);
            $table->integer('legislature')->nullable();
            $table->timestamps();

            $table->unique(['politician_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parliamentary_fronts');
    }
};
