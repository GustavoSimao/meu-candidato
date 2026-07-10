<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapporteurships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('politician_id')->constrained('politicians');
            $table->string('bill_external_id', 100)->nullable();
            $table->string('bill_description', 500)->nullable();
            $table->text('bill_ementa')->nullable();
            $table->string('commission_name', 300)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('removal_reason', 300)->nullable();
            $table->timestamps();

            $table->index('politician_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapporteurships');
    }
};
