<?php

namespace App\Models;

use App\Notifications\Auth\AdminResetPasswordNotification;
use App\Notifications\Auth\ReaderResetPasswordNotification;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasLocalePreference, MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'locale_preference',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin' || ! $this->is_active) {
            return false;
        }

        return $this->hasRole('super_admin')
            || $this->can('view_any articles')
            || $this->can('view_any comments')
            || $this->can('view_any services')
            || $this->can('view_any projects')
            || $this->can('view_any contact_inquiries')
            || $this->can('view_any settings')
            || $this->can('view_any users')
            || $this->can('view_any roles');
    }

    public function preferredLocale(): string
    {
        $locale = $this->locale_preference;

        return is_string($locale) && array_key_exists($locale, supported_locales())
            ? $locale
            : default_locale();
    }

    public function sendPasswordResetNotification($token): void
    {
        $notification = request()->is('admin/*')
            ? new AdminResetPasswordNotification($token)
            : new ReaderResetPasswordNotification($token);

        $this->notify($notification);
    }

    /** @return HasMany<Comment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /** @return HasMany<ArticleAppreciation, $this> */
    public function articleAppreciations(): HasMany
    {
        return $this->hasMany(ArticleAppreciation::class);
    }

    /** @return HasMany<ArticleBookmark, $this> */
    public function articleBookmarks(): HasMany
    {
        return $this->hasMany(ArticleBookmark::class);
    }
}
