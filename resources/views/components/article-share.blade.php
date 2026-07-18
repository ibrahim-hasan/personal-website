@props([
    'title',
    'canonicalUrl',
    'description' => null,
])

@php
    $shareDescription = $description ?: $title;
    $linkedInUrl = 'https://www.linkedin.com/sharing/share-offsite/?'.http_build_query([
        'url' => $canonicalUrl,
    ], '', '&', PHP_QUERY_RFC3986);
    $whatsAppUrl = 'https://wa.me/?'.http_build_query([
        'text' => $title."\n".$canonicalUrl,
    ], '', '&', PHP_QUERY_RFC3986);
    $qabilahUrl = 'https://qabilah.com/discover/following?'.http_build_query([
        'title' => $title,
        'text' => $shareDescription,
        'url' => $canonicalUrl,
    ], '', '&', PHP_QUERY_RFC3986).'#write-post';
    $emailUrl = 'mailto:?'.http_build_query([
        'subject' => $title,
        'body' => $shareDescription."\n\n".$canonicalUrl,
    ], '', '&', PHP_QUERY_RFC3986);
@endphp

<section
    class="article-share"
    data-article-share
    data-share-title="{{ $title }}"
    data-share-description="{{ $shareDescription }}"
    data-share-url="{{ $canonicalUrl }}"
    aria-label="{{ __('articles.share.label') }}"
>
    <p class="article-share__label">{{ __('articles.share.label') }}</p>

    <div class="article-share__actions" role="group" aria-label="{{ __('articles.share.actions') }}">
        <button
            type="button"
            class="article-share__action article-share__action--primary"
            data-article-native-share
            hidden
        >
            <x-phosphor-share-network class="h-4 w-4" aria-hidden="true" />
            <span>{{ __('articles.share.native') }}</span>
        </button>

        <button
            type="button"
            class="article-share__action"
            data-article-copy-link
        >
            <x-phosphor-link class="h-4 w-4" aria-hidden="true" />
            <span class="article-share__copy-label" aria-hidden="true">
                <span data-article-copy-default>{{ __('articles.share.copy') }}</span>
                <span data-article-copy-success>{{ __('articles.share.copied') }}</span>
            </span>
            <span class="sr-only" data-article-copy-label data-default-label="{{ __('articles.share.copy') }}">{{ __('articles.share.copy') }}</span>
        </button>

        <a
            href="{{ $linkedInUrl }}"
            class="article-share__action"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="{{ __('articles.share.linkedin') }}"
        >
            <x-phosphor-linkedin-logo class="h-4 w-4" aria-hidden="true" />
            <span>{{ __('articles.share.linkedin_short') }}</span>
        </a>

        <a
            href="{{ $qabilahUrl }}"
            class="article-share__action"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="{{ __('articles.share.qabilah') }}"
        >
            <x-phosphor-share-network class="h-4 w-4" aria-hidden="true" />
            <span>{{ __('articles.share.qabilah_short') }}</span>
        </a>

        <a
            href="{{ $whatsAppUrl }}"
            class="article-share__action"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="{{ __('articles.share.whatsapp') }}"
        >
            <x-phosphor-whatsapp-logo class="h-4 w-4" aria-hidden="true" />
            <span>{{ __('articles.share.whatsapp_short') }}</span>
        </a>

        <a
            href="{{ $emailUrl }}"
            class="article-share__action"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="{{ __('articles.share.email') }}"
        >
            <x-phosphor-envelope-simple class="h-4 w-4" aria-hidden="true" />
            <span>{{ __('articles.share.email_short') }}</span>
        </a>
    </div>

    <p
        class="article-share__status sr-only"
        data-article-share-status
        data-copy-success="{{ __('articles.share.copied') }}"
        data-copy-error="{{ __('articles.share.copy_failed') }}"
        data-share-error="{{ __('articles.share.share_failed') }}"
        aria-live="polite"
        aria-atomic="true"
    ></p>
</section>
