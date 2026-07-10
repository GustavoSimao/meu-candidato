<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leadership_positions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('politician_id')->constrained('politicians');
            $table->string('position', 300);
            $table->string('party_acronym', 50)->nullable();
            $table->string('house', 50)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index('politician_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leadership_positions');
    }
};
