<?php

namespace App\Actions\Services;

use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class SetServicePublication
{
    public function __construct(private readonly ServicePublicationValidator $validator) {}

    public function publish(User $actor, Service $service): Service
    {
        Gate::forUser($actor)->authorize('publish', $service);

        return DB::transaction(function () use ($service): Service {
            $lockedService = $this->lockedService($service);

            $lockedService->forceFill([
                'is_draft' => false,
                'is_active' => true,
            ]);

            $this->validator->assertPublishable($lockedService);

            $lockedService->save();

            return $lockedService->refresh();
        });
    }

    public function unpublish(User $actor, Service $service): Service
    {
        Gate::forUser($actor)->authorize('publish', $service);

        return DB::transaction(function () use ($service): Service {
            $lockedService = $this->lockedService($service);

            $lockedService->forceFill([
                'is_draft' => true,
                'is_active' => false,
            ])->save();

            return $lockedService->refresh();
        });
    }

    private function lockedService(Service $service): Service
    {
        return Service::withTrashed()
            ->with(['projects.evidence', 'projects.services', 'projects.articles', 'articles'])
            ->lockForUpdate()
            ->findOrFail($service->getKey());
    }
}
