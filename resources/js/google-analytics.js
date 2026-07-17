const measurementId = document
    .querySelector('meta[name="google-analytics-id"]')
    ?.getAttribute('content')
    ?.trim();

if (measurementId && ! document.querySelector('script[data-google-analytics]')) {
    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function gtag() {
        window.dataLayer.push(arguments);
    };

    window.gtag('js', new Date());
    window.gtag('config', measurementId);

    const googleAnalyticsScript = document.createElement('script');

    googleAnalyticsScript.async = true;
    googleAnalyticsScript.dataset.googleAnalytics = '';
    googleAnalyticsScript.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(measurementId)}`;

    document.head.append(googleAnalyticsScript);
}
