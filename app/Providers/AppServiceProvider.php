<?php

namespace App\Providers;

use App\Contracts\ArticleAudio\NarrationEditor;
use App\Models\Article;
use App\Models\Comment;
use App\Models\ContactInquiry;
use App\Models\Project;
use App\Models\Role;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Policies\ArticlePolicy;
use App\Policies\CommentPolicy;
use App\Policies\ContactInquiryPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\RolePolicy;
use App\Policies\ServicePolicy;
use App\Policies\SettingPolicy;
use App\Policies\UserPolicy;
use App\Services\OpenAI\OpenAiNarrationEditor;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Passport\Passport;
use Livewire\Livewire;
use Mcamara\LaravelLocalization\Traits\LoadsTranslatedCachedRoutes;

class AppServiceProvider extends ServiceProvider
{
    use LoadsTranslatedCachedRoutes;

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
        RouteServiceProvider::loadCachedRoutesUsing(
            fn () => $this->loadCachedRoutes(),
        );

        $this->configureDefaults();
        $this->configurePassport();
        $this->configureLivewireUpdateRoute();
        $this->registerSuperAdminAccess();
        $this->registerPolicies();
        $this->registerReaderVerificationUrls();
    }

    /**
     * Keep Livewire updates on a stable URL across cached route refreshes.
     */
    protected function configureLivewireUpdateRoute(): void
    {
        Livewire::setUpdateRoute(
            fn ($handle) => Route::post('/livewire/update', $handle),
        );
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

        RateLimiter::for('editorial-api-read', function (Request $request): Limit {
            return Limit::perMinute(120)->by($this->rateLimitKey($request));
        });

        RateLimiter::for('editorial-api-write', function (Request $request): Limit {
            return Limit::perMinute(30)->by($this->rateLimitKey($request));
        });

        RateLimiter::for('editorial-api-upload', function (Request $request): Limit {
            return Limit::perHour(10)->by($this->rateLimitKey($request));
        });
    }

    protected function configurePassport(): void
    {
        Passport::tokensCan([
            'articles:read' => 'Read editorial articles.',
            'articles:write' => 'Create and update editorial drafts.',
            'articles:publish' => 'Publish or unpublish editorial articles.',
            'articles:archive' => 'Archive or restore editorial articles.',
            'media:write' => 'Upload or remove editorial media.',
        ]);
        Passport::tokensExpireIn(now()->addMinutes(15));
        Passport::clientCredentialsTokensExpireIn(now()->addMinutes(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::$deviceCodeGrantEnabled = false;
        Passport::loadKeysFrom(storage_path());
        Passport::authorizationView(fn (array $parameters) => view('mcp.authorize', $parameters));
    }

    protected function rateLimitKey(Request $request): string
    {
        $clientId = (string) optional($request->attributes->get('editorial_api_client'))->getKey();

        return hash('sha256', $clientId.'|'.$request->ip());
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(Article::class, ArticlePolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(ContactInquiry::class, ContactInquiryPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Setting::class, SettingPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
    }

    protected function registerSuperAdminAccess(): void
    {
        Gate::before(function (User $user, string $ability, array $arguments): ?bool {
            $subject = $arguments[0] ?? null;

            $requiresPolicyDecision = $subject instanceof User
                || ($subject instanceof Role && $subject->name === 'super_admin');

            if ($requiresPolicyDecision && in_array($ability, ['update', 'delete', 'restore', 'forceDelete'], true)) {
                return null;
            }

            return $user->hasRole('super_admin') ? true : null;
        });
    }

    protected function registerReaderVerificationUrls(): void
    {
        VerifyEmail::createUrlUsing(function (User $reader): string {
            $locale = $reader->preferredLocale();
            $routeName = $locale === default_locale()
                ? 'verification.verify'
                : $locale.'.verification.verify';

            return URL::temporarySignedRoute(
                $routeName,
                now()->addMinutes((int) config('auth.verification.expire', 60)),
                [
                    'id' => $reader->getKey(),
                    'hash' => sha1($reader->getEmailForVerification()),
                ],
            );
        });
    }
}
