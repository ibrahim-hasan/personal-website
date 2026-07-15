<?php

namespace App\Providers;

use App\Contracts\ArticleAudio\NarrationEditor;
use App\Models\Author;
use App\Models\Guide;
use App\Models\IntellectualLibrary;
use App\Models\Role;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Policies\AuthorPolicy;
use App\Policies\GuidePolicy;
use App\Policies\IntellectualLibraryPolicy;
use App\Policies\RolePolicy;
use App\Policies\ServicePolicy;
use App\Policies\SettingPolicy;
use App\Policies\TagPolicy;
use App\Policies\UserPolicy;
use App\Services\OpenAI\OpenAiNarrationEditor;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Tags\Tag;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('Support/helpers.php');

        $this->app->bind(NarrationEditor::class, OpenAiNarrationEditor::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerPolicies();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(Guide::class, GuidePolicy::class);
        Gate::policy(IntellectualLibrary::class, IntellectualLibraryPolicy::class);
        Gate::policy(Author::class, AuthorPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
    }
}
