<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_features') || ! Schema::hasTable('company_has_feature')) {
            return;
        }

        $featureId = DB::table('company_features')
            ->where('slug', 'requires_brand')
            ->value('id');

        if (! $featureId) {
            return;
        }

        DB::table('company_has_feature')
            ->where('company_feature_id', $featureId)
            ->delete();

        DB::table('company_features')
            ->where('id', $featureId)
            ->delete();
    }

    public function down(): void
    {
        if (! Schema::hasTable('company_features')) {
            return;
        }

        $exists = DB::table('company_features')
            ->where('slug', 'requires_brand')
            ->exists();

        if ($exists) {
            return;
        }

        $now = now();

        DB::table('company_features')->insert([
            'name' => 'Requires Brand',
            'slug' => 'requires_brand',
            'description' => 'Require a brand query parameter for tracking.',
            'default_enabled' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
