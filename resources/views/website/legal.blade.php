<x-layouts.front
    :title="$content['title']"
    :description="$content['description']"
    schemaType="WebPage"
    activeMenu="true">
    @php($isFeatureDocument = in_array($document, ['privacy', 'terms'], true))

    <section @class([
        'page-intro',
        'page-intro--violet' => $isFeatureDocument,
        'pb-12 pt-24 sm:pb-16 sm:pt-28' => ! $isFeatureDocument,
    ])>
        <div class="site-container page-intro__grid">
            <div>
                <p @class(['signal-label', 'signal-label--light' => $isFeatureDocument])>{{ $content['eyebrow'] }}</p>
                <h1 @class([
                    'display-page mt-7 max-w-[14ch]',
                    'text-canvas' => $isFeatureDocument,
                    'text-ink' => ! $isFeatureDocument,
                ])>{{ $content['title'] }}</h1>
            </div>
            <div class="max-w-[58ch] lg:self-end lg:justify-self-end">
                <p @class(['copy-lead', 'text-violet-100' => $isFeatureDocument, 'text-ink-muted' => ! $isFeatureDocument])>{{ $content['introduction'] }}</p>
                <p @class(['mt-6 text-sm font-bold', 'text-violet-100' => $isFeatureDocument, 'text-ink-soft' => ! $isFeatureDocument])>{{ $content['effective_date'] }}</p>
            </div>
        </div>
    </section>

    <section class="bg-canvas-bright py-12 sm:py-16">
        <div class="site-container max-w-4xl">
            <div class="space-y-12">
                @foreach ($content['sections'] as $section)
                    <section class="scroll-mt-32 border-t border-ink/10 pt-8 first:border-t-0 first:pt-0" aria-labelledby="legal-section-{{ $loop->iteration }}">
                        <h2 id="legal-section-{{ $loop->iteration }}" class="font-display text-2xl font-black leading-tight text-ink sm:text-3xl">{{ $section['heading'] }}</h2>
                        <div class="mt-4 space-y-4 text-base leading-8 text-ink-muted">
                            @foreach ($section['paragraphs'] as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                        @if (filled($section['bullets'] ?? null))
                            <ul class="mt-5 list-disc space-y-2 ps-6 text-base leading-8 text-ink-muted marker:text-violet-600">
                                @foreach ($section['bullets'] as $bullet)
                                    <li>{{ $bullet }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @if (filled($section['table'] ?? null))
                            <div class="mt-6 overflow-x-auto border border-ink/15">
                                <table class="min-w-[52rem] border-collapse text-start text-sm leading-6">
                                    <thead class="bg-violet-100 text-ink">
                                        <tr>
                                            @foreach ($section['table']['headers'] as $header)
                                                <th scope="col" class="border-b border-e border-ink/15 px-4 py-3 text-start font-sans font-bold last:border-e-0">{{ $header }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($section['table']['rows'] as $row)
                                            <tr class="border-b border-ink/10 align-top last:border-b-0">
                                                @foreach ($row as $cell)
                                                    <td class="border-e border-ink/10 px-4 py-3 text-ink-muted last:border-e-0">{{ $cell }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>
                @endforeach
            </div>

            @if (in_array($document, ['privacy', 'cookies_policy'], true))
                <div class="border-t border-ink/10 pt-6">
                    <button type="button" class="text-sm font-bold text-violet-700 underline decoration-violet-700/30 underline-offset-4 hover:decoration-violet-700" data-open-cookie-preferences>
                        {{ __('legal.cookie_preferences') }}
                    </button>
                </div>
            @endif
        </div>
    </section>
</x-layouts.front>
