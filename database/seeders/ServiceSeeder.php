<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Support\SiteContent;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (SiteContent::serviceDefaults() as $index => $service) {
            if (Service::withTrashed()->where('key', $service['id'])->exists()) {
                continue;
            }

            Service::query()->create([
                'key' => $service['id'],
                'name' => $service['name'],
                'summary' => $service['summary'],
                'problem' => $service['problem'],
                'approach' => $service['approach'],
                'deliverables' => $service['deliverables'],
                'result' => $service['result'],
                'fit_signals' => $service['fit_signals'],
                'engagement_note' => $service['engagement_note'],
                'order' => $index + 1,
                'is_draft' => false,
                'is_active' => true,
            ]);
        }
    }
}
