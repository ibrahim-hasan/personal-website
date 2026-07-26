<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->json('body')->nullable()->after('closing');
            $table->json('image_alt')->nullable()->after('image');
            $table->json('image_caption')->nullable()->after('image_alt');

            $table->json('lead')->nullable()->change();
            $table->json('sections')->nullable()->change();
            $table->json('closing')->nullable()->change();
            $table->json('read_minutes')->nullable()->change();
            $table->boolean('is_published')->default(false)->change();
        });
    }

    public function down(): void
    {
        throw new LogicException(
            'The rich article content schema is intentionally irreversible because body-only articles cannot be reconstructed in the legacy fields. Recovery requires a forward migration.',
        );
    }
};
