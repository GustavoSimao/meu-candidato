<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('politician_id')->constrained('politicians');
            $table->integer('year');
            $table->string('type', 500);
            $table->string('description', 500)->nullable();
            $table->decimal('value', 14, 2);
            $table->string('supplier_cnpj_cpf', 20)->nullable();
            $table->string('document_number', 100)->nullable();
            $table->date('document_date')->nullable();
            $table->timestamps();

            $table->index(['politician_id', 'year']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
