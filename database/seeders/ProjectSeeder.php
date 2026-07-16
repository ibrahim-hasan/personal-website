<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Support\PortfolioAtlas;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (PortfolioAtlas::projectDefaults() as $index => $project) {
            if (Project::withTrashed()->where('slug', $project['id'])->exists()) {
                continue;
            }

            Project::query()->create([
                'slug' => $project['id'],
                'title' => $project['title'],
                'sector' => $project['sector'],
                'summary' => $project['summary'],
                'challenge' => $project['challenge'],
                'response' => $project['response'],
                'outcome' => $project['outcome'],
                'lens' => $project['lens'],
                'image' => $project['image'],
                'image_alt' => $project['alt'],
                'logo' => $project['logo'] ?: null,
                'logo_alt' => $project['logo_alt'],
                'tags' => $project['tags'],
                'sort_order' => $index + 1,
                'featured' => $index < 5,
                'is_active' => true,
            ]);
        }
    }
}
