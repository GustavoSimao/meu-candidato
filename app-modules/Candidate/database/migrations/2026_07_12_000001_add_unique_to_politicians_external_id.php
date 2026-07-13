<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('politicians', function (Blueprint $table) {
            $table->dropIndex('politicians_external_id_index');
            $table->unique('external_id');
        });
    }

    public function down(): void
    {
        Schema::table('politicians', function (Blueprint $table) {
            $table->dropUnique('politicians_external_id_unique');
            $table->index('external_id');
        });
    }
};
