<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('politician_id')->constrained('politicians');
            $table->string('source', 20);
            $table->string('external_id', 100)->nullable();
            $table->string('name', 500);
            $table->string('acronym', 50)->nullable();
            $table->string('role', 50)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index(['politician_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_memberships');
    }
};
