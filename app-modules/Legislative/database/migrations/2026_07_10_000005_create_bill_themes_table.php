<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_themes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bill_id')->constrained('bills');
            $table->string('external_id', 50)->nullable();
            $table->string('theme_name', 300);
            $table->timestamps();

            $table->unique(['bill_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_themes');
    }
};
