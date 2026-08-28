<?php

use App\Enums\ProjectAssetPermissionStatus;
use App\Enums\ProjectDisclosureLevel;
use App\Enums\ProjectPermissionStatus;
use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const string AUTOMATIC_PERMISSION_REFERENCE = 'Automatically approved under the public project-media policy.';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->approveUnreviewedAsset('image', Project::IMAGE_COLLECTION);
        $this->approveUnreviewedAsset('logo', Project::LOGO_COLLECTION);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Public approval is operational data and must not be withdrawn by a schema rollback.
    }

    private function approveUnreviewedAsset(string $asset, string $collection): void
    {
        $statusAttribute = "{$asset}_permission_status";
        $referenceAttribute = "{$asset}_permission_reference";

        DB::table('projects')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->where($statusAttribute, ProjectAssetPermissionStatus::Unreviewed->value)
            ->whereNull($referenceAttribute)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('disclosure_level')
                    ->orWhere('disclosure_level', '!=', ProjectDisclosureLevel::Anonymized->value);
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('permission_status')
                    ->orWhereNotIn('permission_status', [
                        ProjectPermissionStatus::InternalOnly->value,
                        ProjectPermissionStatus::ApprovedAnonymized->value,
                        ProjectPermissionStatus::Revoked->value,
                    ]);
            })
            ->where(function (Builder $query) use ($asset, $collection): void {
                $query
                    ->where(function (Builder $query) use ($asset): void {
                        $query
                            ->whereNotNull($asset)
                            ->where($asset, '!=', '');
                    })
                    ->orWhereExists(function (Builder $query) use ($collection): void {
                        $query
                            ->selectRaw('1')
                            ->from('media')
                            ->whereColumn('media.model_id', 'projects.id')
                            ->where('media.model_type', Project::class)
                            ->where('media.collection_name', $collection);
                    });
            })
            ->update([
                $statusAttribute => ProjectAssetPermissionStatus::Approved->value,
                $referenceAttribute => Crypt::encryptString(self::AUTOMATIC_PERMISSION_REFERENCE),
                'updated_at' => now(),
            ]);
    }
};
