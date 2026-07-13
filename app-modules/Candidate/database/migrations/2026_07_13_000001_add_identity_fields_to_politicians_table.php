<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('politicians', function (Blueprint $table) {
            $table->string('nome_urna', 150)->nullable()->after('name');
            $table->string('email', 255)->nullable()->after('nome_urna');
            $table->string('phone', 50)->nullable()->after('email');
            $table->string('office', 100)->nullable()->after('phone');
            $table->json('social_media')->nullable()->after('office');
            $table->string('uf_birth', 2)->nullable()->after('birth_date');
            $table->string('municipality_birth', 100)->nullable()->after('uf_birth');
        });
    }

    public function down(): void
    {
        Schema::table('politicians', function (Blueprint $table) {
            $table->dropColumn([
                'nome_urna',
                'email',
                'phone',
                'office',
                'social_media',
                'uf_birth',
                'municipality_birth',
            ]);
        });
    }
};
