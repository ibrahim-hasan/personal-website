<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommentReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Comment $reply)
    {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable->locale_preference ?: config('app.locale');
        $article = $this->reply->article;
        $slug = $article->slugs[$locale] ?? $article->slugs['ar'];
        $url = localized_route('writing.show', ['article' => $slug], true, $locale).'#comment-'.$this->reply->getKey();

        return (new MailMessage)
            ->subject(__('community_notifications.reply_subject', locale: $locale))
            ->greeting(__('community_notifications.greeting', ['name' => $notifiable->name], $locale))
            ->line(__('community_notifications.reply_line', ['name' => $this->reply->user?->name], $locale))
            ->action(__('community_notifications.read_conversation', locale: $locale), $url);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'comment_reply',
            'comment_id' => $this->reply->getKey(),
            'article_id' => $this->reply->article_id,
        ];
    }
}
