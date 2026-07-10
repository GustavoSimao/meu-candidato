<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('party_orientations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('voting_session_id')->constrained('voting_sessions');
            $table->string('party_acronym', 50);
            $table->string('orientation', 50);
            $table->timestamps();

            $table->unique(['voting_session_id', 'party_acronym']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_orientations');
    }
};
