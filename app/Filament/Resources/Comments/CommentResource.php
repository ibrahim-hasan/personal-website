<?php

namespace App\Filament\Resources\Comments;

use App\Enums\CommentReportStatus;
use App\Enums\CommentStatus;
use App\Filament\Resources\Comments\Pages\ListComments;
use App\Filament\Resources\Comments\Tables\CommentsTable;
use App\Models\Comment;
use App\Models\CommentReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.engagement');
    }

    public static function getModelLabel(): string
    {
        return __('editorial_admin.comment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('editorial_admin.comments');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = self::pendingCommentsCount() + self::pendingReportsCount();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return self::pendingReportsCount() > 0 ? 'danger' : 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('editorial_admin.navigation.moderation_badge', [
            'comments' => self::pendingCommentsCount(),
            'reports' => self::pendingReportsCount(),
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return CommentsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'article',
                'user',
                'parent.user',
                'reports' => fn (HasMany $query): HasMany => $query->pending(),
            ])
            ->withCount([
                'reports as pending_reports_count' => fn (Builder $query): Builder => $query->pending(),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComments::route('/'),
        ];
    }

    private static function pendingCommentsCount(): int
    {
        return Comment::query()->where('status', CommentStatus::Pending)->count();
    }

    private static function pendingReportsCount(): int
    {
        return CommentReport::query()
            ->where('status', CommentReportStatus::Pending)
            ->whereHas('comment')
            ->count();
    }
}
