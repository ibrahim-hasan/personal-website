<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Profile extends EditProfile
{
    protected static bool $isDiscovered = false;

    public static function getLabel(): string
    {
        return __('admin.auth.profile');
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label(__('admin.fields.name'))
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('admin.fields.email_address'))
            ->email()
            ->required()
            ->maxLength(255)
            ->unique(ignoreRecord: true)
            ->live(debounce: 500);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('admin.auth.new_password'))
            ->password()
            ->revealable(Filament::arePasswordsRevealable())
            ->rule(Password::default())
            ->showAllValidationMessages()
            ->autocomplete('new-password')
            ->dehydrated(fn (mixed $state): bool => filled($state))
            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
            ->live(debounce: 500)
            ->same('password_confirmation');
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('password_confirmation')
            ->label(__('admin.auth.confirm_new_password'))
            ->password()
            ->autocomplete('new-password')
            ->revealable(Filament::arePasswordsRevealable())
            ->required()
            ->visible(fn (Get $get): bool => filled($get('password')))
            ->dehydrated(false);
    }

    protected function getCurrentPasswordFormComponent(): Component
    {
        return TextInput::make('current_password')
            ->label(__('admin.auth.current_password'))
            ->password()
            ->autocomplete('current-password')
            ->currentPassword(guard: Filament::getAuthGuard())
            ->revealable(Filament::arePasswordsRevealable())
            ->required()
            ->visible(fn (Get $get): bool => filled($get('password')) || ($get('email') !== $this->getUser()->getAttributeValue('email')))
            ->dehydrated(false);
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label(__('admin.auth.save_profile'))
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return __('admin.auth.profile_updated');
    }

    /**
     * Preserve the existing account-verification behavior while delegating
     * the profile workflow and MFA management to Filament.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (
            ! Filament::hasEmailChangeVerification()
            && array_key_exists('email', $data)
            && $record->getAttributeValue('email') !== $data['email']
        ) {
            $record->setAttribute('email_verified_at', null);
        }

        return parent::handleRecordUpdate($record, $data);
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::End;
    }

    protected function afterSave(): void
    {
        $this->data['current_password'] = null;
        $this->data['password_confirmation'] = null;
    }
}
