<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommentApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Comment $comment)
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
        $url = $this->articleUrl($locale);

        return (new MailMessage)
            ->subject(__('community_notifications.approved_subject', locale: $locale))
            ->greeting(__('community_notifications.greeting', ['name' => $notifiable->name], $locale))
            ->line(__('community_notifications.approved_line', locale: $locale))
            ->action(__('community_notifications.read_conversation', locale: $locale), $url);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'comment_approved',
            'comment_id' => $this->comment->getKey(),
            'article_id' => $this->comment->article_id,
        ];
    }

    private function articleUrl(string $locale): string
    {
        $article = $this->comment->article;
        $slug = $article->slugs[$locale] ?? $article->slugs['ar'];

        return localized_route('writing.show', ['article' => $slug], true, $locale).'#comment-'.$this->comment->getKey();
    }
}
