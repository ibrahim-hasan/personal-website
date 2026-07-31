<?php

use App\Enums\ProjectAssetPermissionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, array{image: string, logo: string}> */
    private const array CURATED_PROJECT_MEDIA = [
        'digi-pedia' => [
            'image' => 'images/projects/atlas/digi-pedia-ai-learning.webp',
            'logo' => 'images/brands/projects/digi-pedia.webp',
        ],
        'wafaa' => [
            'image' => 'images/projects/atlas/wafaa-education-transformation.webp',
            'logo' => 'images/brands/projects/wafaa.webp',
        ],
        'rannan' => [
            'image' => 'images/projects/atlas/rannan-caller-trust.webp',
            'logo' => 'images/brands/projects/rannan.webp',
        ],
        'maazim' => [
            'image' => 'images/projects/atlas/maazim-gifting-operations.webp',
            'logo' => 'images/brands/projects/maazim.webp',
        ],
        'rafid-360' => [
            'image' => 'images/projects/atlas/rafid-humanitarian-collaboration.webp',
            'logo' => 'images/brands/projects/rafid-360.webp',
        ],
        'taifk' => [
            'image' => 'images/projects/atlas/taifk-service-operations.webp',
            'logo' => 'images/brands/projects/taifk.webp',
        ],
        'bosalty' => [
            'image' => 'images/projects/atlas/bosalty-tourism-journeys.webp',
            'logo' => 'images/brands/projects/bosalty.webp',
        ],
        '2060-investments' => [
            'image' => 'images/projects/atlas/investments-2060-shareholder-services.webp',
            'logo' => 'images/brands/projects/2060.webp',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::CURATED_PROJECT_MEDIA as $key => $media) {
            DB::table('projects')
                ->where('key', $key)
                ->where('image', $media['image'])
                ->where('image_permission_status', ProjectAssetPermissionStatus::Unreviewed->value)
                ->whereNull('image_permission_reference')
                ->update([
                    'image_permission_status' => ProjectAssetPermissionStatus::Approved->value,
                    'image_permission_reference' => Crypt::encryptString('Owner-approved existing public portfolio image.'),
                    'updated_at' => now(),
                ]);

            DB::table('projects')
                ->where('key', $key)
                ->where('logo', $media['logo'])
                ->where('logo_permission_status', ProjectAssetPermissionStatus::Unreviewed->value)
                ->whereNull('logo_permission_reference')
                ->update([
                    'logo_permission_status' => ProjectAssetPermissionStatus::Approved->value,
                    'logo_permission_reference' => Crypt::encryptString('Owner-approved existing public portfolio logo.'),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Owner approval is operational data and must not be withdrawn by a schema rollback.
    }
};
