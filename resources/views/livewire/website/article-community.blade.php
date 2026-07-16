<section
    class="relative overflow-clip border-y border-ink/15 bg-canvas py-16 sm:py-24"
    data-reader-secondary
    aria-labelledby="article-community-title"
    @auth
        x-data="{ lastSaved: 0, timer: null }"
        x-init="window.addEventListener('scroll', () => { clearTimeout(timer); timer = setTimeout(() => { const page = document.querySelector('[data-article-page]'); if (! page) return; const total = page.scrollHeight - window.innerHeight; const percent = total > 0 ? Math.min(100, Math.max(0, Math.round((window.scrollY - page.offsetTop) / total * 100))) : 100; if (percent >= lastSaved + 10 || percent >= 95) { lastSaved = percent; $wire.updateProgress(percent); } }, 500); }, { passive: true })"
    @endauth
>
    <div class="site-container">
        @if ($article)
            <div class="grid gap-10 lg:grid-cols-[minmax(0,0.72fr)_minmax(0,1.28fr)] lg:gap-16">
                <div class="lg:pt-3">
                    <p class="signal-label">{{ __('community.kicker') }}</p>
                    <h2 id="article-community-title" class="mt-5 max-w-lg font-display text-3xl font-black leading-[1.12] text-ink sm:text-5xl">{{ __('community.title') }}</h2>
                    <p class="mt-5 max-w-md text-base leading-8 text-ink-muted">{{ __('community.description') }}</p>

                    <div class="mt-8 flex flex-wrap gap-3" aria-label="{{ __('community.reader_actions') }}">
                        @guest
                            <a href="{{ $loginUrl }}" class="button-primary min-h-12">
                                <x-phosphor-hands-clapping class="size-5" />
                                {{ trans_choice('community.appreciations', $article->appreciations_count, ['count' => $article->appreciations_count]) }}
                            </a>
                            <a href="{{ $loginUrl }}" class="button-quiet min-h-12">
                                <x-phosphor-bookmark-simple class="size-5" />
                                {{ __('community.save') }}
                            </a>
                        @else
                            @if (auth()->user()->hasVerifiedEmail())
                                <button type="button" wire:click="toggleAppreciation" wire:loading.attr="disabled" wire:target="toggleAppreciation" @class([
                                    'inline-flex min-h-12 items-center gap-2 rounded-[0.3rem] border px-5 py-3 font-sans text-sm font-bold transition-colors disabled:cursor-wait disabled:opacity-60',
                                    'border-violet-700 bg-violet-700 text-violet-50' => $hasAppreciated,
                                    'border-ink bg-ink text-canvas-bright hover:border-violet-700 hover:bg-violet-700' => ! $hasAppreciated,
                                ]) aria-pressed="{{ $hasAppreciated ? 'true' : 'false' }}">
                                    <x-phosphor-hands-clapping class="size-5" />
                                    {{ trans_choice('community.appreciations', $article->appreciations_count, ['count' => $article->appreciations_count]) }}
                                </button>
                                <button type="button" wire:click="toggleBookmark" wire:loading.attr="disabled" wire:target="toggleBookmark" @class([
                                    'inline-flex min-h-12 items-center gap-2 rounded-[0.3rem] border px-5 py-3 font-sans text-sm font-bold transition-colors disabled:cursor-wait disabled:opacity-60',
                                    'border-violet-700 bg-violet-100 text-violet-900' => $hasBookmarked,
                                    'border-ink/25 bg-canvas-bright text-ink hover:border-violet-600 hover:text-violet-800' => ! $hasBookmarked,
                                ]) aria-pressed="{{ $hasBookmarked ? 'true' : 'false' }}">
                                    <x-phosphor-bookmark-simple class="size-5" />
                                    {{ $hasBookmarked ? __('community.saved') : __('community.save') }}
                                </button>
                                @if ($hasBookmarked)
                                    <a href="{{ $libraryUrl }}" class="text-link inline-flex min-h-12 px-3 py-3">
                                        {{ __('community.open_library') }}
                                    </a>
                                @endif
                            @else
                                <a href="{{ $verifyUrl }}" class="button-primary min-h-12">{{ __('community.verify_to_join') }}</a>
                            @endif
                        @endguest
                    </div>

                    @if ($notice)
                        <p class="mt-5 border border-success/25 bg-success/10 px-4 py-3 text-sm font-bold text-ink" role="status">{{ $notice }}</p>
                    @endif
                    @error('auth') <p class="mt-5 border border-danger/25 bg-danger/10 px-4 py-3 text-sm font-bold text-danger" role="alert">{{ $message }}</p> @enderror
                    @error('rate_limit') <p class="mt-5 border border-danger/25 bg-danger/10 px-4 py-3 text-sm font-bold text-danger" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="border border-violet-700/20 bg-canvas-bright p-6 shadow-[0.8rem_0.8rem_0_rgba(109,70,146,0.12)] sm:p-9">
                    <div class="flex items-end justify-between gap-4 border-b border-ink/15 pb-5">
                        <div>
                            <p class="font-sans text-sm font-bold text-violet-700">{{ __('community.conversation') }}</p>
                            <h3 class="mt-1 font-display text-2xl font-black text-ink">{{ trans_choice('community.comments_count', $comments->total(), ['count' => $comments->total()]) }}</h3>
                        </div>
                    </div>

                    @auth
                        @if (auth()->user()->hasVerifiedEmail())
                            <form wire:submit="postComment" class="mt-6">
                                <label for="article-comment" class="font-sans text-sm font-bold text-ink-soft">{{ __('community.add_thought') }}</label>
                                <textarea id="article-comment" wire:model="commentBody" rows="4" maxlength="2000" class="mt-2 min-h-32 w-full resize-y rounded-[0.3rem] border border-ink/20 bg-canvas px-4 py-3 leading-7 text-ink outline-none transition-colors focus-visible:border-violet-600 focus-visible:bg-violet-50" placeholder="{{ __('community.comment_placeholder') }}"></textarea>
                                @error('commentBody') <p class="mt-2 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                                <div class="mt-3 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                                    <p class="text-xs leading-5 text-ink-muted">{{ __('community.moderation_note') }}</p>
                                    <button type="submit" wire:loading.attr="disabled" wire:target="postComment" class="button-primary min-h-11 w-full shrink-0 disabled:cursor-wait disabled:opacity-60 sm:w-auto">
                                        <span wire:loading.remove wire:target="postComment">{{ __('community.publish') }}</span>
                                        <span wire:loading wire:target="postComment">{{ __('community.publishing') }}</span>
                                    </button>
                                </div>
                            </form>
                        @endif
                    @else
                        <a href="{{ $loginUrl }}" class="mt-6 flex min-h-12 items-center justify-between gap-4 border border-dashed border-violet-600/50 bg-violet-50 px-5 py-4 font-sans text-sm font-bold text-violet-900 transition-colors hover:border-violet-700 hover:bg-violet-100">
                            <span>{{ __('community.sign_in_prompt') }}</span>
                            <x-phosphor-arrow-up-right class="size-5 rtl:-rotate-90" />
                        </a>
                    @endauth

                    <div class="mt-8 space-y-7">
                        @forelse ($comments as $comment)
                            <article id="comment-{{ $comment->getKey() }}" wire:key="comment-{{ $comment->getKey() }}" class="border-t border-ink/15 pt-6 first:border-t-0 first:pt-0">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-violet-900 font-sans text-sm font-bold text-violet-50" aria-hidden="true">{{ mb_strtoupper(mb_substr($comment->user?->name ?? '?', 0, 1)) }}</span>
                                        <div>
                                            <p class="font-sans font-bold text-ink">{{ $comment->user?->name ?? __('community.former_reader') }}</p>
                                            <time class="text-xs text-ink-muted" datetime="{{ $comment->created_at->toAtomString() }}">{{ $comment->created_at->diffForHumans() }}</time>
                                        </div>
                                    </div>
                                    @auth
                                        <div class="flex gap-1 font-sans text-xs font-bold text-ink-muted">
                                            <button type="button" wire:click="openReport({{ $comment->getKey() }})" class="inline-flex min-h-11 items-center px-2 transition-colors hover:text-danger">{{ __('community.report') }}</button>
                                            @can('delete', $comment)
                                                <button type="button" wire:click="deleteComment({{ $comment->getKey() }})" wire:confirm="{{ __('community.delete_confirm') }}" class="inline-flex min-h-11 items-center px-2 transition-colors hover:text-danger">{{ __('community.delete') }}</button>
                                            @endcan
                                        </div>
                                    @endauth
                                </div>
                                <p class="mt-4 whitespace-pre-line text-[0.98rem] leading-7 text-ink-soft">{{ $comment->body }}</p>

                                @auth
                                    @if (auth()->user()->hasVerifiedEmail())
                                        <button type="button" wire:click="startReply({{ $comment->getKey() }})" class="mt-2 inline-flex min-h-11 items-center font-sans text-sm font-bold text-violet-700 underline decoration-violet-300 underline-offset-4 transition-colors hover:text-violet-900 hover:decoration-violet-700">{{ __('community.reply') }}</button>
                                    @endif
                                @endauth

                                @if ($replyTo === $comment->getKey())
                                    <form wire:submit="postReply" class="mt-4 border-s-2 border-violet-500 bg-violet-50 p-4">
                                        <label for="reply-{{ $comment->getKey() }}" class="sr-only">{{ __('community.reply') }}</label>
                                        <textarea id="reply-{{ $comment->getKey() }}" wire:model="replyBody" rows="3" maxlength="2000" class="min-h-28 w-full rounded-[0.3rem] border border-ink/20 bg-canvas-bright px-4 py-3 leading-7 text-ink outline-none transition-colors focus-visible:border-violet-600" placeholder="{{ __('community.reply_placeholder') }}"></textarea>
                                        @error('replyBody') <p class="mt-2 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                                        <div class="mt-3 flex justify-end gap-3">
                                            <button type="button" wire:click="$set('replyTo', null)" class="button-quiet min-h-11">{{ __('community.cancel') }}</button>
                                            <button type="submit" wire:loading.attr="disabled" wire:target="postReply" class="button-primary min-h-11 disabled:opacity-60">{{ __('community.reply') }}</button>
                                        </div>
                                    </form>
                                @endif

                                @if ($comment->replies->isNotEmpty())
                                    <div class="ms-5 mt-5 space-y-5 border-s border-violet-700/25 ps-5 sm:ms-8 sm:ps-7">
                                        @foreach ($comment->replies as $reply)
                                            <article id="comment-{{ $reply->getKey() }}" wire:key="reply-{{ $reply->getKey() }}">
                                                <div class="flex items-center gap-3">
                                                    <span class="grid size-9 place-items-center rounded-full bg-violet-100 font-sans text-xs font-bold text-violet-900" aria-hidden="true">{{ mb_strtoupper(mb_substr($reply->user?->name ?? '?', 0, 1)) }}</span>
                                                    <div>
                                                        <p class="font-sans text-sm font-bold text-ink">{{ $reply->user?->name ?? __('community.former_reader') }}</p>
                                                        <time class="text-xs text-ink-muted" datetime="{{ $reply->created_at->toAtomString() }}">{{ $reply->created_at->diffForHumans() }}</time>
                                                    </div>
                                                </div>
                                                <p class="mt-3 whitespace-pre-line text-sm leading-7 text-ink-soft">{{ $reply->body }}</p>
                                            </article>
                                        @endforeach
                                    </div>
                                @endif
                            </article>
                        @empty
                            <div class="py-10 text-center">
                                <p class="font-display text-lg font-bold text-ink">{{ __('community.empty_title') }}</p>
                                <p class="mt-2 text-sm text-ink-muted">{{ __('community.empty_body') }}</p>
                            </div>
                        @endforelse
                    </div>

                    @if ($comments->hasPages())
                        <div class="mt-8 border-t border-ink/15 pt-6">{{ $comments->links(data: ['scrollTo' => '#article-community-title']) }}</div>
                    @endif
                </div>
            </div>

            @if ($reportingComment)
                <div class="fixed inset-0 z-50 grid place-items-center bg-ink/65 p-5" role="dialog" aria-modal="true" aria-labelledby="report-comment-title" aria-describedby="report-comment-description" wire:key="report-dialog">
                    <form wire:submit="submitReport" class="max-h-[calc(100svh-2.5rem)] w-full max-w-md overflow-y-auto border border-violet-700/25 bg-canvas-bright p-6 shadow-[0.8rem_0.8rem_0_rgba(109,70,146,0.25)] sm:p-8">
                        <h3 id="report-comment-title" class="font-display text-2xl font-black text-ink">{{ __('community.report_title') }}</h3>
                        <p id="report-comment-description" class="mt-2 text-sm leading-6 text-ink-muted">{{ __('community.report_description') }}</p>
                        <label class="mt-5 block font-sans text-sm font-bold text-ink-soft">
                            {{ __('community.reason') }}
                            <select wire:model="reportReason" class="mt-2 min-h-12 w-full rounded-[0.3rem] border border-ink/20 bg-canvas px-4 py-3 text-ink outline-none transition-colors focus-visible:border-violet-600 focus-visible:bg-violet-50">
                                @foreach (['spam', 'abuse', 'misinformation', 'privacy', 'other'] as $reason)
                                    <option value="{{ $reason }}">{{ __("community.reasons.{$reason}") }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="mt-4 block font-sans text-sm font-bold text-ink-soft">
                            {{ __('community.details_optional') }}
                            <textarea wire:model="reportDetails" rows="3" maxlength="500" class="mt-2 min-h-28 w-full rounded-[0.3rem] border border-ink/20 bg-canvas px-4 py-3 text-ink outline-none transition-colors focus-visible:border-violet-600 focus-visible:bg-violet-50"></textarea>
                        </label>
                        @error('reportDetails') <p class="mt-2 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" wire:click="$set('reportingComment', null)" class="button-quiet min-h-11">{{ __('community.cancel') }}</button>
                            <button type="submit" wire:loading.attr="disabled" wire:target="submitReport" class="inline-flex min-h-11 items-center justify-center rounded-[0.3rem] bg-ink px-5 py-3 font-sans text-sm font-bold text-canvas-bright transition-colors hover:bg-danger disabled:opacity-60">{{ __('community.send_report') }}</button>
                        </div>
                    </form>
                </div>
            @endif
        @endif
    </div>
</section>
