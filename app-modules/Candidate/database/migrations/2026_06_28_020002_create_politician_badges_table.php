<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('politician_badges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('politician_id')->constrained('politicians')->cascadeOnDelete();
            $table->foreignUuid('badge_definition_id')->constrained('badge_definitions')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->unique(['politician_id', 'badge_definition_id']);
            $table->index('badge_definition_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('politician_badges');
    }
};
