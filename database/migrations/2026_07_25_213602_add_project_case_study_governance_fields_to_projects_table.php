<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->json('ibrahim_role')->nullable();
            $table->string('delivery_entity', 40)->nullable()->index();
            $table->json('delivery_period')->nullable();
            $table->string('disclosure_level', 40)->nullable()->index();
            $table->string('evidence_level', 40)->nullable()->index();
            $table->string('permission_status', 40)->default('unreviewed')->index();
            $table->text('permission_reference')->nullable();
            $table->json('confidentiality_note')->nullable();
            $table->json('case_study_sections')->nullable();
            $table->timestamp('case_study_reviewed_at')->nullable()->index();
            $table->boolean('is_detailed_case_study')->default(false)->index();
            $table->string('image_permission_status', 40)->default('unreviewed')->index();
            $table->text('image_permission_reference')->nullable();
            $table->string('logo_permission_status', 40)->default('unreviewed')->index();
            $table->text('logo_permission_reference')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn([
                'ibrahim_role',
                'delivery_entity',
                'delivery_period',
                'disclosure_level',
                'evidence_level',
                'permission_status',
                'permission_reference',
                'confidentiality_note',
                'case_study_sections',
                'case_study_reviewed_at',
                'is_detailed_case_study',
                'image_permission_status',
                'image_permission_reference',
                'logo_permission_status',
                'logo_permission_reference',
            ]);
        });
    }
};
