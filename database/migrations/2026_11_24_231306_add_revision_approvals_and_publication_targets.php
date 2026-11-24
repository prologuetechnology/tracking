<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // Adding inline references keeps SQLite from rebuilding parent tables that own archived proofs.
            DB::statement('ALTER TABLE design_draft_revisions ADD COLUMN approved_at DATETIME NULL');
            DB::statement('ALTER TABLE design_draft_revisions ADD COLUMN approved_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL');
            DB::statement('ALTER TABLE tracking_publications ADD COLUMN published_revision_id INTEGER NULL REFERENCES design_draft_revisions(id) ON DELETE CASCADE');
        } else {
            Schema::table('design_draft_revisions', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            });
            Schema::table('tracking_publications', function (Blueprint $table) {
                $table->foreignId('published_revision_id')->nullable()->constrained('design_draft_revisions')->cascadeOnDelete();
            });
        }

        $approval = DB::table('design_proofs')
            ->whereColumn('design_proofs.design_draft_revision_id', 'design_draft_revisions.id')
            ->whereNotNull('approved_at')->where('status', 'ready')
            ->orderBy('approved_at')->orderBy('id')->limit(1);
        DB::table('design_draft_revisions')->whereExists(clone $approval)->update([
            'approved_at' => (clone $approval)->select('approved_at'),
            'approved_by' => (clone $approval)->select('approved_by'),
        ]);
        DB::table('tracking_publications')->whereNotNull('design_proof_id')->update([
            'published_revision_id' => DB::table('design_proofs')->select('design_draft_revision_id')
                ->whereColumn('design_proofs.id', 'tracking_publications.design_proof_id'),
        ]);
    }

    public function down(): void
    {
        if (DB::table('tracking_publications')->whereNotNull('published_revision_id')->whereNull('design_proof_id')->exists()
            || DB::table('design_draft_revisions')->whereNotNull('approved_at')->whereNotExists(function ($proofs) {
                $proofs->selectRaw('1')->from('design_proofs')->whereColumn('design_draft_revision_id', 'design_draft_revisions.id')
                    ->where('status', 'ready')->whereNotNull('design_proofs.approved_at');
            })->exists()) {
            throw new RuntimeException('The previous schema cannot retain approvals or publications made without a proof.');
        }
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('ALTER TABLE tracking_publications DROP COLUMN published_revision_id');
            DB::statement('ALTER TABLE design_draft_revisions DROP COLUMN approved_by');
            DB::statement('ALTER TABLE design_draft_revisions DROP COLUMN approved_at');

            return;
        }

        Schema::table('tracking_publications', fn (Blueprint $table) => $table->dropConstrainedForeignId('published_revision_id'));
        Schema::table('design_draft_revisions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn('approved_at');
        });
    }
};
