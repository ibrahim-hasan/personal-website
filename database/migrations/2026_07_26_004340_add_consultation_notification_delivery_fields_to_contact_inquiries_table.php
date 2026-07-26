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
        Schema::table('contact_inquiries', function (Blueprint $table): void {
            $table->string('notification_status', 24)->default('not_requested');
            $table->unsignedSmallInteger('notification_attempts')->default(0);
            $table->timestamp('notification_last_attempted_at')->nullable();
            $table->timestamp('notification_sent_at')->nullable();
            $table->timestamp('notification_failed_at')->nullable();
            $table->timestamp('notification_next_retry_at')->nullable();
            $table->index(
                ['notification_status', 'notification_next_retry_at'],
                'contact_inquiries_notification_retry_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_inquiries', function (Blueprint $table): void {
            $table->dropIndex('contact_inquiries_notification_retry_index');
            $table->dropColumn([
                'notification_status',
                'notification_attempts',
                'notification_last_attempted_at',
                'notification_sent_at',
                'notification_failed_at',
                'notification_next_retry_at',
            ]);
        });
    }
};
