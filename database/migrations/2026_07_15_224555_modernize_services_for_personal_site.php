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
        Schema::table('services', function (Blueprint $table): void {
            $table->string('slug', 80)->nullable()->unique()->after('id');
            $table->json('summary')->nullable()->after('name');
            $table->json('problem')->nullable()->after('summary');
            $table->json('approach')->nullable()->after('problem');
            $table->json('deliverables')->nullable()->after('approach');
            $table->json('result')->nullable()->after('deliverables');
        });

        DB::table('services')
            ->orderBy('id')
            ->get()
            ->each(function (object $service): void {
                DB::table('services')
                    ->where('id', $service->id)
                    ->update([
                        'slug' => 'legacy-service-'.$service->id,
                        'summary' => $service->how_can_we_help,
                        'problem' => $service->problems_you_are_facing,
                        'approach' => $service->type_of_intervention,
                        'deliverables' => json_encode([], JSON_THROW_ON_ERROR),
                        'result' => $service->results,
                        'is_active' => false,
                        'deleted_at' => now(),
                    ]);
            });

        Schema::table('services', function (Blueprint $table): void {
            $table->dropColumn([
                'problems_you_are_facing',
                'how_can_we_help',
                'type_of_intervention',
                'results',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->json('problems_you_are_facing')->nullable()->after('name');
            $table->json('how_can_we_help')->nullable()->after('problems_you_are_facing');
            $table->json('type_of_intervention')->nullable()->after('how_can_we_help');
            $table->json('results')->nullable()->after('type_of_intervention');
        });

        DB::table('services')
            ->orderBy('id')
            ->get()
            ->each(function (object $service): void {
                DB::table('services')
                    ->where('id', $service->id)
                    ->update([
                        'problems_you_are_facing' => $service->problem,
                        'how_can_we_help' => $service->summary,
                        'type_of_intervention' => $service->approach,
                        'results' => $service->result,
                        'deleted_at' => str_starts_with((string) $service->slug, 'legacy-service-') ? null : $service->deleted_at,
                    ]);
            });

        Schema::table('services', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn([
                'slug',
                'summary',
                'problem',
                'approach',
                'deliverables',
                'result',
            ]);
        });
    }
};
