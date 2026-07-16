<?php

namespace App\Filament\Resources\Comments\Tables;

use App\Enums\CommentReportStatus;
use App\Enums\CommentStatus;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use App\Notifications\CommentApprovedNotification;
use App\Notifications\CommentReplyNotification;
use App\Support\AdminTableEmptyState;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CommentsTable
{
    public static function configure(Table $table): Table
    {
        return AdminTableEmptyState::apply($table, 'comments', 'heroicon-o-chat-bubble-left-right')
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('editorial_admin.fields.reader'))
                    ->searchable()
                    ->placeholder(__('editorial_admin.former_reader')),
                TextColumn::make('article.title')
                    ->label(__('editorial_admin.article'))
                    ->getStateUsing(fn (Comment $record): string => $record->article
                        ? (localized_model_attribute($record->article, 'title') ?? $record->article->key)
                        : '—')
                    ->wrap(),
                TextColumn::make('body')
                    ->label(__('editorial_admin.fields.comment_body'))
                    ->limit(100)
                    ->wrap()
                    ->tooltip(fn (Comment $record): string => $record->body),
                TextColumn::make('status')
                    ->label(__('editorial_admin.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (CommentStatus $state): string => __('editorial_admin.statuses.'.$state->value))
                    ->color(fn (CommentStatus $state): string => match ($state) {
                        CommentStatus::Pending => 'warning',
                        CommentStatus::Approved => 'success',
                        CommentStatus::Rejected => 'danger',
                    }),
                TextColumn::make('parent.user.name')
                    ->label(__('editorial_admin.fields.reply_to'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('pending_reports_count')
                    ->label(__('editorial_admin.fields.pending_reports'))
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray')
                    ->sortable(),
                TextColumn::make('report_reasons')
                    ->label(__('editorial_admin.fields.report_reasons'))
                    ->getStateUsing(fn (Comment $record): string => $record->reports
                        ->pluck('reason')
                        ->unique()
                        ->map(fn (string $reason): string => __('community.reasons.'.$reason))
                        ->implode(', '))
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('report_details')
                    ->label(__('editorial_admin.fields.report_details'))
                    ->getStateUsing(fn (Comment $record): string => $record->reports
                        ->pluck('details')
                        ->filter()
                        ->implode("\n"))
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('editorial_admin.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('editorial_admin.fields.status'))
                    ->options(collect(CommentStatus::cases())->mapWithKeys(
                        fn (CommentStatus $status): array => [$status->value => __('editorial_admin.statuses.'.$status->value)],
                    )),
                SelectFilter::make('article_id')
                    ->label(__('editorial_admin.article'))
                    ->options(fn (): array => Article::query()->get()->mapWithKeys(
                        fn (Article $article): array => [$article->getKey() => localized_model_attribute($article, 'title') ?? $article->key],
                    )->all())
                    ->searchable(),
                TernaryFilter::make('pending_reports')
                    ->label(__('editorial_admin.filters.pending_reports'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas(
                            'reports',
                            fn (Builder $reports): Builder => $reports->pending(),
                        ),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave(
                            'reports',
                            fn (Builder $reports): Builder => $reports->pending(),
                        ),
                    ),
            ])
            ->recordUrl(null)
            ->recordActions([
                Action::make('approve')
                    ->label(__('editorial_admin.actions.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->authorize('update')
                    ->visible(fn (Comment $record): bool => $record->status !== CommentStatus::Approved)
                    ->requiresConfirmation()
                    ->action(function (Comment $record): void {
                        self::approve($record);
                    }),
                Action::make('reject')
                    ->label(__('editorial_admin.actions.reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->authorize('update')
                    ->visible(fn (Comment $record): bool => $record->status !== CommentStatus::Rejected)
                    ->schema([
                        Textarea::make('note')
                            ->label(__('editorial_admin.fields.moderation_note'))
                            ->maxLength(1000),
                    ])
                    ->action(function (Comment $record, array $data): void {
                        self::reject($record, $data['note'] ?? null);
                    }),
                Action::make('dismiss_reports')
                    ->label(__('editorial_admin.actions.dismiss_reports'))
                    ->icon('heroicon-o-shield-check')
                    ->color('gray')
                    ->authorize('update')
                    ->visible(fn (Comment $record): bool => (int) $record->pending_reports_count > 0)
                    ->requiresConfirmation()
                    ->action(function (Comment $record): void {
                        self::dismissReports($record);
                    }),
                DeleteAction::make()
                    ->before(function (Comment $record): void {
                        self::resolveReportsBeforeDeletion($record);
                    }),
            ]);
    }

    private static function approve(Comment $comment): void
    {
        Gate::authorize('update', $comment);

        /** @var User $moderator */
        $moderator = auth()->user();

        DB::transaction(function () use ($comment, $moderator): void {
            $comment->approve($moderator);
            $comment->reports()->pending()->update([
                'status' => CommentReportStatus::Dismissed,
                'reviewed_by_user_id' => $moderator->getKey(),
                'reviewed_at' => now(),
            ]);

            $comment->user?->notify(new CommentApprovedNotification($comment));

            if ($comment->parent?->user && $comment->parent->user_id !== $comment->user_id) {
                $comment->parent->user->notify(new CommentReplyNotification($comment));
            }
        });

        Notification::make()
            ->title(__('editorial_admin.messages.approved'))
            ->success()
            ->send();
    }

    private static function reject(Comment $comment, ?string $note): void
    {
        Gate::authorize('update', $comment);

        /** @var User $moderator */
        $moderator = auth()->user();

        DB::transaction(function () use ($comment, $moderator, $note): void {
            $comment->reject($moderator, $note);
            $comment->reports()->pending()->update([
                'status' => CommentReportStatus::Resolved,
                'reviewed_by_user_id' => $moderator->getKey(),
                'reviewed_at' => now(),
            ]);
        });

        Notification::make()
            ->title(__('editorial_admin.messages.rejected'))
            ->success()
            ->send();
    }

    private static function dismissReports(Comment $comment): void
    {
        Gate::authorize('update', $comment);

        /** @var User $moderator */
        $moderator = auth()->user();

        $comment->reports()->pending()->update([
            'status' => CommentReportStatus::Dismissed,
            'reviewed_by_user_id' => $moderator->getKey(),
            'reviewed_at' => now(),
        ]);

        Notification::make()
            ->title(__('editorial_admin.messages.reports_dismissed'))
            ->success()
            ->send();
    }

    private static function resolveReportsBeforeDeletion(Comment $comment): void
    {
        Gate::authorize('delete', $comment);

        /** @var User $moderator */
        $moderator = auth()->user();

        $comment->reports()->pending()->update([
            'status' => CommentReportStatus::Resolved,
            'reviewed_by_user_id' => $moderator->getKey(),
            'reviewed_at' => now(),
        ]);
    }
}
