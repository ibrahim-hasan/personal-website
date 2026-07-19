<?php

namespace App\Actions\Readers;

use App\Enums\CommentStatus;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class DeleteReaderAccount
{
    /**
     * @throws AuthorizationException
     */
    public function handle(User $reader): void
    {
        if (! $reader->isReaderAccount() || $reader->canAccessPanel(filament()->getPanel('admin'))) {
            throw new AuthorizationException;
        }

        $reader->getConnection()->transaction(function () use ($reader): void {
            $this->deleteNotifications($reader);
            $this->deleteSessions($reader);
            $this->deletePasswordResetTokens($reader);
            $this->deleteAiConversationData($reader);
            $this->deleteNonPublicContributions($reader);

            if (! $reader->delete()) {
                throw new RuntimeException('The reader account could not be deleted.');
            }
        });
    }

    private function deleteNotifications(User $reader): void
    {
        $reader->notifications()->delete();
    }

    private function deleteSessions(User $reader): void
    {
        $connectionName = $this->configuredConnection('session.connection');
        $table = (string) config('session.table', 'sessions');

        $this->whenTableExists($connectionName, $table, function (Builder $query) use ($reader): void {
            $query->where('user_id', $reader->getKey())->delete();
        });
    }

    private function deletePasswordResetTokens(User $reader): void
    {
        $broker = (string) config('auth.defaults.passwords', 'users');
        $table = (string) config("auth.passwords.{$broker}.table", 'password_reset_tokens');
        $connectionName = $reader->getConnectionName();

        $this->whenTableExists($connectionName, $table, function (Builder $query) use ($reader): void {
            $query->where('email', $reader->email)->delete();
        });
    }

    private function deleteAiConversationData(User $reader): void
    {
        $connectionName = $this->configuredConnection('ai.conversations.connection');
        $conversationsTable = (string) config(
            'ai.conversations.tables.conversations',
            'agent_conversations',
        );
        $messagesTable = (string) config(
            'ai.conversations.tables.messages',
            'agent_conversation_messages',
        );

        if (! Schema::connection($connectionName)->hasTable($conversationsTable)) {
            return;
        }

        $connection = DB::connection($connectionName);
        $conversationIds = $connection
            ->table($conversationsTable)
            ->where('user_id', $reader->getKey())
            ->pluck('id')
            ->all();

        if (Schema::connection($connectionName)->hasTable($messagesTable)) {
            $connection
                ->table($messagesTable)
                ->where(function (Builder $query) use ($conversationIds, $reader): void {
                    $query->where('user_id', $reader->getKey());

                    if ($conversationIds !== []) {
                        $query->orWhereIn('conversation_id', $conversationIds);
                    }
                })
                ->delete();
        }

        $connection
            ->table($conversationsTable)
            ->where('user_id', $reader->getKey())
            ->delete();
    }

    private function deleteNonPublicContributions(User $reader): void
    {
        $reader->comments()
            ->withTrashed()
            ->where(function (\Illuminate\Database\Eloquent\Builder $query): void {
                $query
                    ->where('status', '!=', CommentStatus::Approved->value)
                    ->orWhereNotNull('deleted_at');
            })
            ->forceDelete();
    }

    /** @param callable(Builder): void $callback */
    private function whenTableExists(?string $connectionName, string $table, callable $callback): void
    {
        if (! Schema::connection($connectionName)->hasTable($table)) {
            return;
        }

        $callback(DB::connection($connectionName)->table($table));
    }

    private function configuredConnection(string $key): ?string
    {
        $connection = config($key);

        return is_string($connection) && $connection !== '' ? $connection : null;
    }
}
