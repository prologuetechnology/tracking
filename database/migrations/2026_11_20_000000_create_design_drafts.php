<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_drafts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->timestamps();
        });

        Schema::create('design_draft_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_draft_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->unique(['design_draft_id', 'version']);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            foreach (['logo', 'banner', 'footer'] as $asset) {
                $table->foreignId($asset.'_image_id')->nullable()->constrained('images')->restrictOnDelete();
            }
            $table->json('design');
            $table->string('search_option', 20);
            $table->string('tracking_number', 128)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_draft_revisions');
        Schema::dropIfExists('design_drafts');
    }
};
