<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class Profile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = null;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.profile';

    public ?array $data = [];

    public static function getLabel(): string
    {
        return __('admin.auth.profile');
    }

    public function getTitle(): string
    {
        return __('admin.auth.profile');
    }

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user, 403);

        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->schema([
                Section::make(__('admin.sections.main_details'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('admin.fields.email_address'))
                            ->email()
                            ->required()
                            ->unique(
                                table: User::class,
                                column: 'email',
                                ignorable: fn (): ?User => Auth::user(),
                            )
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make(__('admin.auth.change_password'))
                    ->schema([
                        TextInput::make('current_password')
                            ->label(__('admin.auth.current_password'))
                            ->password()
                            ->revealable()
                            ->required(),
                        TextInput::make('password')
                            ->label(__('admin.auth.new_password'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->confirmed()
                            ->rule(Password::defaults()),
                        TextInput::make('password_confirmation')
                            ->label(__('admin.auth.confirm_new_password'))
                            ->password()
                            ->revealable()
                            ->required(),
                    ])
                    ->columns(3),
            ]);
    }

    public function save(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user, 403);

        $data = $this->form->getState();

        if (! Hash::check($data['current_password'] ?? '', $user->password)) {
            throw ValidationException::withMessages([
                'data.current_password' => [__('admin.auth.password_mismatch')],
            ]);
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'current_password' => null,
            'password' => null,
            'password_confirmation' => null,
        ]);

        $this->resetErrorBag();

        Notification::make()
            ->title(__('admin.auth.profile_updated'))
            ->success()
            ->send();
    }
}
