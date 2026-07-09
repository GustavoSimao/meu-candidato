<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_data_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ingestion_job_id')->constrained('ingestion_jobs');
            $table->string('source', 50);
            $table->string('external_id', 50)->nullable();
            $table->jsonb('raw_data');
            $table->boolean('processed')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_data_records');
    }
};
