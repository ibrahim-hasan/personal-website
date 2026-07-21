<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_inquiries', function (Blueprint $table): void {
            $table->index(['status', 'received_at'], 'contact_inquiries_status_received_at_index');
        });

        Schema::table('comment_reports', function (Blueprint $table): void {
            $table->index(['status', 'reviewed_at'], 'comment_reports_status_reviewed_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('contact_inquiries', function (Blueprint $table): void {
            $table->dropIndex('contact_inquiries_status_received_at_index');
        });

        Schema::table('comment_reports', function (Blueprint $table): void {
            $table->dropIndex('comment_reports_status_reviewed_at_index');
        });
    }
};
