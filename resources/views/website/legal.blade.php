<x-layouts.front
    :title="$content['title']"
    :description="$content['description']"
    schemaType="WebPage"
    activeMenu="true">
    <section class="border-b border-ink/10 bg-canvas py-16 sm:py-24">
        <div class="site-container">
            <div class="max-w-4xl">
                <p class="signal-label">{{ $content['eyebrow'] }}</p>
                <h1 class="display-section mt-5 max-w-[15ch]">{{ $content['title'] }}</h1>
                <p class="copy-lead mt-6 max-w-[58ch]">{{ $content['introduction'] }}</p>
                <p class="mt-4 text-sm font-bold text-ink-soft">{{ $content['effective_date'] }}</p>
            </div>
        </div>
    </section>

    <section class="bg-canvas-bright py-10 sm:py-14">
        <div class="site-container">
            <div class="max-w-4xl space-y-10">
                @foreach ($content['sections'] as $section)
                    <section class="scroll-mt-32 border-t border-ink/10 pt-7 first:border-t-0 first:pt-0" aria-labelledby="legal-section-{{ $loop->iteration }}">
                        <h2 id="legal-section-{{ $loop->iteration }}" class="font-display text-xl font-black leading-tight text-ink sm:text-2xl">{{ $section['heading'] }}</h2>
                        @if (filled($section['paragraphs'] ?? null))
                            <div class="mt-4 max-w-[72ch] space-y-3 text-[0.98rem] leading-8 text-ink-muted">
                                @foreach ($section['paragraphs'] as $paragraph)
                                    <p>{{ $paragraph }}</p>
                                @endforeach
                            </div>
                        @endif
                        @if (filled($section['bullets'] ?? null))
                            <ul class="mt-5 max-w-[72ch] list-disc space-y-2 ps-6 text-[0.98rem] leading-8 text-ink-muted marker:text-violet-600">
                                @foreach ($section['bullets'] as $bullet)
                                    <li>{{ $bullet }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @if (filled($section['facts'] ?? null))
                            <dl class="mt-5 border-y border-ink/15">
                                @foreach ($section['facts'] as $fact)
                                    <div class="grid min-w-0 gap-4 border-b border-ink/10 py-5 last:border-b-0 lg:grid-cols-[minmax(0,0.62fr)_minmax(0,1.38fr)] lg:gap-8">
                                        <dt class="min-w-0 text-start font-sans text-sm font-bold leading-6 text-ink">
                                            <span>{{ $fact['title'] }}</span>
                                            @if (filled($fact['tokens'] ?? null))
                                                <span class="mt-3 block text-start">
                                                    <span dir="ltr" class="inline-flex max-w-full flex-wrap gap-x-3 gap-y-2">
                                                        @foreach ($fact['tokens'] as $token)
                                                            <bdi dir="ltr" lang="en" class="inline-block max-w-full text-left font-sans text-xs font-bold text-ink-soft [overflow-wrap:anywhere]">
                                                                <code class="font-sans not-italic">{{ $token }}</code>
                                                            </bdi>
                                                        @endforeach
                                                    </span>
                                                </span>
                                            @endif
                                        </dt>
                                        <dd class="min-w-0">
                                            <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 xl:grid-cols-3">
                                                @foreach ($fact['values'] as $value)
                                                    <div class="min-w-0">
                                                        <dt class="font-sans text-xs font-bold text-ink-muted">{{ $value['label'] }}</dt>
                                                        <dd class="mt-1 text-sm leading-6 text-ink-soft">{{ $value['value'] }}</dd>
                                                    </div>
                                                @endforeach
                                            </dl>
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif
                    </section>
                @endforeach
            </div>

            @if (in_array($document, ['privacy', 'cookies_policy'], true))
                <div class="mt-10 max-w-4xl border-t border-ink/10 pt-5">
                    <button type="button" class="text-sm font-bold text-violet-700 underline decoration-violet-700/30 underline-offset-4 hover:decoration-violet-700" data-open-cookie-preferences>
                        {{ __('legal.cookie_preferences') }}
                    </button>
                </div>
            @endif
        </div>
    </section>
</x-layouts.front>
