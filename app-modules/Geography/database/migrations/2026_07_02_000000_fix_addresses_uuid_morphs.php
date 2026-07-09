<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE addresses DROP CONSTRAINT IF EXISTS addresses_addressable_type_addressable_id_index');
        DB::statement('ALTER TABLE addresses DROP COLUMN IF EXISTS addressable_id');
        DB::statement('ALTER TABLE addresses ADD COLUMN addressable_id uuid NOT NULL');
        DB::statement('CREATE INDEX addresses_addressable_type_addressable_id_index ON addresses (addressable_type, addressable_id)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE addresses DROP INDEX IF EXISTS addresses_addressable_type_addressable_id_index');
        DB::statement('ALTER TABLE addresses DROP COLUMN IF EXISTS addressable_id');
        DB::statement('ALTER TABLE addresses ADD COLUMN addressable_id bigint NOT NULL');
        DB::statement('CREATE INDEX addresses_addressable_type_addressable_id_index ON addresses (addressable_type, addressable_id)');
    }
};
