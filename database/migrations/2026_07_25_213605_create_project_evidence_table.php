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
        Schema::create('project_evidence', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('kind', 20);
            $table->json('label');
            $table->json('result_text')->nullable();
            $table->decimal('baseline_value', 20, 6)->nullable();
            $table->decimal('result_value', 20, 6)->nullable();
            $table->decimal('range_min', 20, 6)->nullable();
            $table->decimal('range_max', 20, 6)->nullable();
            $table->decimal('threshold_value', 20, 6)->nullable();
            $table->string('unit', 40)->nullable();
            $table->string('direction', 20)->nullable();
            $table->json('baseline_period')->nullable();
            $table->json('result_period')->nullable();
            $table->json('method')->nullable();
            $table->json('scope')->nullable();
            $table->text('source_owner')->nullable();
            $table->text('source_reference')->nullable();
            $table->text('permission_reference')->nullable();
            $table->string('state', 20)->default('draft');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->index(['project_id', 'state', 'is_public', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_evidence');
    }
};
