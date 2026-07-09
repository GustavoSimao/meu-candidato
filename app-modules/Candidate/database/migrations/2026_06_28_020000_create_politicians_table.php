<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('politicians', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 150);
            $table->string('cpf', 11)->unique()->nullable();
            $table->foreignUuid('party_id')->constrained('parties');
            $table->date('birth_date')->nullable();
            $table->string('education', 100)->nullable();
            $table->string('declared_profession', 150)->nullable();
            $table->string('external_id', 50)->nullable();
            $table->string('photo_url', 500)->nullable();
            $table->string('government_plan_url', 500)->nullable();
            $table->string('position', 50)->nullable();
            $table->string('defends', 500)->nullable();
            $table->text('trendings')->nullable();
            $table->integer('active_processes')->default(0);
            $table->timestamps();

            $table->index('external_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('politicians');
    }
};
