<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('speeches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('politician_id')->constrained('politicians');
            $table->string('source', 20);
            $table->string('external_id', 100)->nullable();
            $table->string('title', 500)->nullable();
            $table->text('content')->nullable();
            $table->text('resume')->nullable();
            $table->dateTime('date');
            $table->string('session_name', 300)->nullable();
            $table->string('uri', 500)->nullable();
            $table->timestamps();

            $table->index(['politician_id', 'date']);
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speeches');
    }
};
