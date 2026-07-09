<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->morphs('addressable');
            $table->string('uf', 2)->nullable();
            $table->string('cidade', 100)->nullable();
            $table->string('municipio', 100)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('logradouro', 255)->nullable();
            $table->string('cep', 8)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
