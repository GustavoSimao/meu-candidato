<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('politician_id')->constrained('politicians');
            $table->string('source', 20);
            $table->string('external_id', 100)->nullable();
            $table->string('title', 500);
            $table->string('type', 200)->nullable();
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->string('location', 300)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['politician_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
