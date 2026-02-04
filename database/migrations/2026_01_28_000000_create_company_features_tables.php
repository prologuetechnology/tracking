<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_features', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('default_enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('company_has_feature', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_feature_id')
                ->constrained('company_features')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'company_feature_id']);
        });

        $now = now();
        $features = [
            [
                'name' => 'Enable Map',
                'slug' => 'enable_map',
                'description' => 'Enable tracking map in the portal.',
                'default_enabled' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Enable Documents',
                'slug' => 'enable_documents',
                'description' => 'Enable shipment documents in the portal.',
                'default_enabled' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Requires Brand',
                'slug' => 'requires_brand',
                'description' => 'Require a brand query parameter for tracking.',
                'default_enabled' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('company_features')->insert($features);

        $featureIds = DB::table('company_features')->pluck('id', 'slug');
        $companies = DB::table('companies')
            ->select('id', 'enable_map', 'enable_documents', 'requires_brand')
            ->get();

        $pivotRows = [];
        foreach ($companies as $company) {
            $featureMap = [
                'enable_map' => 'enable_map',
                'enable_documents' => 'enable_documents',
                'requires_brand' => 'requires_brand',
            ];

            foreach ($featureMap as $field => $slug) {
                if (! empty($company->{$field}) && isset($featureIds[$slug])) {
                    $pivotRows[] = [
                        'company_id' => $company->id,
                        'company_feature_id' => $featureIds[$slug],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        if (! empty($pivotRows)) {
            DB::table('company_has_feature')->insert($pivotRows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_has_feature');
        Schema::dropIfExists('company_features');
    }
};
