<x-layouts.front :title="__('reader_auth.library_title')" :description="__('reader_auth.library_description')" robots="noindex, nofollow, noarchive, noimageindex">
    <section class="min-h-[75vh] bg-canvas pb-20 pt-32 sm:pb-24 sm:pt-36" aria-labelledby="reader-library-title">
        <div class="site-container">
            <div class="flex flex-col justify-between gap-8 border-b border-ink/15 pb-8 lg:flex-row lg:items-end">
                <div>
                    <p class="signal-label">{{ __('reader_auth.community') }}</p>
                    <h1 id="reader-library-title" class="mt-5 font-display text-4xl font-black leading-[1.08] text-ink sm:text-6xl">{{ __('reader_auth.library_title') }}</h1>
                    <p class="mt-4 max-w-2xl text-base leading-8 text-ink-muted">{{ __('reader_auth.library_description') }}</p>
                </div>
                <x-reader-account-nav active="library" />
            </div>

            @if (session('status'))
                <p class="mt-6 border-s-2 border-violet-700 bg-violet-100 px-5 py-4 text-sm font-semibold leading-6 text-violet-900" role="status">
                    {{ session('status') }}
                </p>
            @endif

            <section class="border-b border-ink/15 py-10" aria-labelledby="reader-notifications-title">
                <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                    <div class="max-w-2xl">
                        <p class="signal-label">{{ __('community_notifications.inbox_kicker') }}</p>
                        <h2 id="reader-notifications-title" class="mt-4 font-display text-2xl font-black leading-tight text-ink sm:text-3xl">
                            {{ __('community_notifications.inbox_title') }}
                        </h2>
                        <p class="mt-3 text-sm leading-7 text-ink-muted sm:text-base">
                            {{ __('community_notifications.inbox_description') }}
                        </p>
                    </div>
                    <p class="w-fit border border-violet-700/25 bg-violet-100 px-3 py-2 font-sans text-xs font-bold text-violet-800" aria-live="polite">
                        {{ trans_choice('community_notifications.unread_count', $unreadNotificationCount, ['count' => $unreadNotificationCount]) }}
                    </p>
                </div>

                @if ($readerNotifications->isEmpty())
                    <div class="mt-6 flex items-start gap-4 border border-dashed border-ink/20 bg-canvas-bright p-5 sm:p-6">
                        <div class="grid size-11 shrink-0 place-items-center rounded-[var(--control-radius)] bg-violet-100 text-violet-700" aria-hidden="true">
                            <x-phosphor-envelope-simple class="size-5" />
                        </div>
                        <div>
                            <h3 class="font-display text-lg font-bold text-ink">{{ __('community_notifications.empty_title') }}</h3>
                            <p class="mt-1 text-sm leading-6 text-ink-muted">{{ __('community_notifications.empty_body') }}</p>
                        </div>
                    </div>
                @else
                    <div class="mt-6 grid gap-3">
                        @foreach ($readerNotifications as $notification)
                            <form method="POST" action="{{ localized_route('reader.notifications.read', ['notification' => $notification['id']]) }}">
                                @csrf
                                <button
                                    type="submit"
                                    @class([
                                        'group flex min-h-11 w-full items-start gap-4 border p-5 text-start transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-700 focus-visible:ring-offset-2 focus-visible:ring-offset-canvas sm:items-center sm:p-6',
                                        'border-violet-700/35 bg-violet-100/60 shadow-[0.35rem_0.35rem_0_rgba(109,70,146,0.10)]' => $notification['unread'],
                                        'border-ink/15 bg-canvas-bright hover:border-violet-600/45' => ! $notification['unread'],
                                    ])
                                >
                                    <span @class([
                                        'mt-1 size-2.5 shrink-0 rounded-full sm:mt-0',
                                        'bg-violet-700' => $notification['unread'],
                                        'bg-ink/20' => ! $notification['unread'],
                                    ]) aria-hidden="true"></span>

                                    <span class="min-w-0 flex-1">
                                        <span class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                            <span class="font-display text-base font-bold text-ink sm:text-lg">{{ $notification['title'] }}</span>
                                            @if ($notification['unread'])
                                                <span class="bg-violet-700 px-2 py-1 font-sans text-[0.65rem] font-black uppercase tracking-[0.12em] text-white">
                                                    {{ __('community_notifications.unread_badge') }}
                                                </span>
                                            @endif
                                        </span>
                                        <span class="mt-1 block text-sm leading-6 text-ink-muted">{{ $notification['message'] }}</span>
                                        <span class="mt-2 block font-sans text-xs font-bold text-violet-800">
                                            {{ __('community_notifications.article_label') }} “{{ $notification['article_title'] }}” · {{ $notification['created_label'] }}
                                        </span>
                                    </span>

                                    <span class="hidden shrink-0 items-center gap-2 font-sans text-xs font-bold text-ink-muted transition-colors group-hover:text-violet-800 sm:flex">
                                        {{ __('community_notifications.open_update') }}
                                        <x-phosphor-arrow-up-right class="size-4 rtl:-rotate-90" aria-hidden="true" />
                                    </span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                @endif
            </section>

            @if ($bookmarks->isEmpty())
                <section class="mt-10 border border-dashed border-violet-700/35 bg-canvas-bright p-8 text-center shadow-[0.65rem_0.65rem_0_rgba(109,70,146,0.10)] sm:p-14" aria-labelledby="reader-library-empty-title">
                    <div class="mx-auto grid size-14 place-items-center rounded-[var(--control-radius)] border border-violet-700/20 bg-violet-100 text-violet-700" aria-hidden="true"><x-phosphor-bookmark-simple class="size-7" /></div>
                    <h2 id="reader-library-empty-title" class="mt-6 font-display text-2xl font-black text-ink">{{ __('reader_auth.library_empty_title') }}</h2>
                    <p class="mx-auto mt-3 max-w-lg leading-7 text-ink-muted">{{ __('reader_auth.library_empty_body') }}</p>
                    <a href="{{ localized_route('writing') }}" class="button-primary mt-6">{{ __('reader_auth.explore_writing') }}</a>
                </section>
            @else
                <div class="mt-10 grid gap-5 md:grid-cols-2">
                    @foreach ($bookmarks as $article)
                        <article class="group overflow-hidden border border-ink/15 bg-canvas-bright transition-colors hover:border-violet-600/50">
                            <a href="{{ $article['url'] }}" class="grid min-h-44 gap-0 sm:grid-cols-[9rem_1fr]">
                                <img src="{{ asset($article['image']) }}" alt="" class="h-48 w-full object-cover sm:h-full" loading="lazy" decoding="async">
                                <div class="flex min-w-0 flex-col p-6">
                                    <p class="font-sans text-xs font-bold text-violet-700">{{ $article['type'] }} · {{ $article['read_time'] }}</p>
                                    <h2 class="mt-3 font-display text-xl font-bold leading-snug text-ink transition-colors group-hover:text-violet-800">{{ $article['title'] }}</h2>
                                    <p class="mt-3 line-clamp-2 text-sm leading-6 text-ink-muted">{{ $article['summary'] }}</p>
                                    <div class="mt-auto pt-6">
                                        <div class="flex items-center justify-between font-sans text-xs font-bold text-ink-muted">
                                            <span>{{ __('reader_auth.reading_progress') }}</span>
                                            <span>{{ $article['progress_percent'] }}%</span>
                                        </div>
                                        <div class="mt-2 h-1.5 overflow-hidden bg-violet-100" role="progressbar" aria-label="{{ __('reader_auth.reading_progress') }}" aria-valuenow="{{ $article['progress_percent'] }}" aria-valuemin="0" aria-valuemax="100" aria-valuetext="{{ $article['progress_percent'] }}%">
                                            <span class="block h-full bg-violet-700" style="width: {{ $article['progress_percent'] }}%"></span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts.front>
