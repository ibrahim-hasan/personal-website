<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('athar_invitations', function (Blueprint $table): void {
            $table->string('relationship', 40)->nullable()->change();
        });

        Schema::table('athar_publication_versions', function (Blueprint $table): void {
            $table->string('display_name')->nullable()->after('identity_display');
        });

        DB::table('athar_publication_versions')
            ->join('athar_contributions', 'athar_contributions.id', '=', 'athar_publication_versions.contribution_id')
            ->join('athar_invitations', 'athar_invitations.id', '=', 'athar_contributions.invitation_id')
            ->orderBy('athar_publication_versions.id')
            ->select([
                'athar_publication_versions.id',
                'athar_publication_versions.identity_display',
                'athar_invitations.recipient_name',
            ])
            ->each(function (object $version): void {
                $recipientName = trim((string) $version->recipient_name);
                $displayName = match ($version->identity_display) {
                    'full_name' => $recipientName,
                    'first_name' => (string) preg_split('/\s+/u', $recipientName)[0],
                    default => '',
                };

                DB::table('athar_publication_versions')
                    ->where('id', $version->id)
                    ->update(['display_name' => $displayName !== '' ? $displayName : null]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('athar_publication_versions', function (Blueprint $table): void {
            $table->dropColumn('display_name');
        });

        DB::table('athar_invitations')
            ->whereNull('relationship')
            ->update(['relationship' => 'personal_connection']);

        Schema::table('athar_invitations', function (Blueprint $table): void {
            $table->string('relationship', 40)->nullable(false)->change();
        });
    }
};
