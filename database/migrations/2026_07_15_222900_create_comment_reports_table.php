<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('comment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reporter_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 40);
            $table->text('details')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['comment_id', 'reporter_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_reports');
    }
};
