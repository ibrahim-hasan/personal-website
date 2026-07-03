<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    /** @var list<int> */
    private array $permissionIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->permissionIds = $this->extractPermissionIds($data);
        $data['guard_name'] = 'web';

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->permissionIds !== []) {
            $this->record->syncPermissions($this->permissionIds);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<int>
     */
    private function extractPermissionIds(array &$data): array
    {
        $permissionIds = [];

        foreach ($data as $key => $value) {
            if (! str_starts_with($key, 'group_permissions_')) {
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $permissionId) {
                    if (is_numeric($permissionId)) {
                        $permissionIds[] = (int) $permissionId;
                    }
                }
            }

            unset($data[$key]);
        }

        return array_values(array_unique($permissionIds));
    }
}
