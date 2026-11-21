<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_proofs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('design_draft_revision_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('snapshot');
            $table->string('shipment_reference', 128);
            $table->unsignedInteger('generation')->default(1);
            $table->string('status', 16)->default('pending');
            $table->string('directory')->nullable();
            $table->json('checksums')->nullable();
            $table->string('customer_approver', 120)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tracking_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->index(['company_id', 'id']);
            $table->foreignId('baseline_revision_id')->constrained('design_draft_revisions')->cascadeOnDelete();
            $table->foreignId('design_proof_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 12);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_publications');
        Schema::dropIfExists('design_proofs');
    }
};
