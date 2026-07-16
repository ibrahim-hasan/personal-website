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
        Schema::create('contact_inquiries', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('email');
            $table->string('company', 120)->nullable();
            $table->string('service_key', 80);
            $table->string('service_label');
            $table->text('challenge');
            $table->string('locale', 5)->default('ar');
            $table->string('status', 20)->default('new')->index();
            $table->timestamp('received_at')->useCurrent()->index();
            $table->timestamp('replied_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['email', 'received_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_inquiries');
    }
};
