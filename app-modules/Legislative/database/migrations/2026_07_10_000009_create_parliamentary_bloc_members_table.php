<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parliamentary_bloc_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bloc_id')->constrained('parliamentary_blocs');
            $table->foreignUuid('politician_id')->constrained('politicians');
            $table->timestamps();

            $table->unique(['bloc_id', 'politician_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parliamentary_bloc_members');
    }
};
