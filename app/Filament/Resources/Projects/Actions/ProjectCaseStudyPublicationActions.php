<?php

namespace App\Filament\Resources\Projects\Actions;

use App\Actions\Projects\SetProjectCaseStudyPublication;
use App\Models\Project;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;

final class ProjectCaseStudyPublicationActions
{
    public static function publish(): Action
    {
        return Action::make('publish_case_study')
            ->label(__('project_admin.actions.publish_case_study'))
            ->icon('heroicon-o-document-check')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription(__('project_admin.actions.publish_case_study_description'))
            ->authorize('publish')
            ->visible(fn (Project $record): bool => ! $record->is_detailed_case_study)
            ->action(function (Project $record, SetProjectCaseStudyPublication $publication): void {
                $publication->publish(self::actor(), $record);

                Notification::make()
                    ->title(__('project_admin.messages.case_study_published'))
                    ->success()
                    ->send();
            });
    }

    public static function unpublish(): Action
    {
        return Action::make('unpublish_case_study')
            ->label(__('project_admin.actions.unpublish_case_study'))
            ->icon('heroicon-o-eye-slash')
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription(__('project_admin.actions.unpublish_case_study_description'))
            ->authorize('publish')
            ->visible(fn (Project $record): bool => $record->is_detailed_case_study)
            ->action(function (Project $record, SetProjectCaseStudyPublication $publication): void {
                $publication->unpublish(self::actor(), $record);

                Notification::make()
                    ->title(__('project_admin.messages.case_study_unpublished'))
                    ->success()
                    ->send();
            });
    }

    private static function actor(): User
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            throw new AuthorizationException;
        }

        return $user;
    }
}
