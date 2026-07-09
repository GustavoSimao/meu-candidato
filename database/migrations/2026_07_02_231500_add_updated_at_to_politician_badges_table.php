<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('politician_badges', function (Blueprint $table) {
            if (! Schema::hasColumn('politician_badges', 'updated_at')) {
                $table->timestamp('updated_at')->useCurrent();
            }
        });
    }

    public function down(): void
    {
        Schema::table('politician_badges', function (Blueprint $table) {
            $table->dropColumn('updated_at');
        });
    }
};
