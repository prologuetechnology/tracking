<?php

use App\Actions\Themes\BackfillThemeVersions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('themes', 'style')) {
            Schema::table('themes', function (Blueprint $table) {
                $table->json('style')->nullable();
            });
        }
        if (! Schema::hasColumn('themes', 'deleted_at')) {
            Schema::table('themes', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
        if (! Schema::hasTable('theme_versions')) {
            Schema::create('theme_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('theme_id')->constrained()->restrictOnDelete();
                $table->unsignedInteger('version');
                $table->unique(['theme_id', 'version']);
                $table->string('name');
                $table->json('colors');
                $table->string('radius')->nullable();
                $table->json('style')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
        foreach (['companies', 'design_draft_revisions'] as $name) {
            if (! Schema::hasColumn($name, 'theme_version_id')) {
                Schema::table($name, function (Blueprint $table) {
                    $table->foreignId('theme_version_id')->nullable()->constrained()->restrictOnDelete();
                });
            }
        }

        (new BackfillThemeVersions)->execute();
    }

    public function down(): void
    {
        foreach (['design_draft_revisions', 'companies'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('theme_version_id');
            });
        }
        Schema::dropIfExists('theme_versions');
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn(['style', 'deleted_at']);
        });
    }
};
