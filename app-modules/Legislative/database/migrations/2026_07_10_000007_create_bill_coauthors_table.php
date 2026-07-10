<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_coauthors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('bill_id')->constrained('bills');
            $table->foreignUuid('politician_id')->nullable()->constrained('politicians');
            $table->string('author_name', 300);
            $table->string('author_external_id', 50)->nullable();
            $table->timestamps();

            $table->index('bill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_coauthors');
    }
};
