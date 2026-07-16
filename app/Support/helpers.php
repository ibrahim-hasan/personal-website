<?php

use App\Models\Setting;
use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Mcamara\LaravelLocalization\Interfaces\LocalizedUrlRoutable;
use Mcamara\LaravelLocalization\LaravelLocalization;

if (! function_exists('supported_locales')) {
    function supported_locales(): array
    {
        return config('app.supported_locales', []);
    }
}

if (! function_exists('current_locale')) {
    function current_locale(): string
    {
        return app()->getLocale();
    }
}

if (! function_exists('default_locale')) {
    function default_locale(): string
    {
        if (app()->bound('laravellocalization')) {
            return app('laravellocalization')->getDefaultLocale();
        }

        return config('app.locale', 'ar');
    }
}

if (! function_exists('is_rtl')) {
    function is_rtl(?string $locale = null): bool
    {
        $activeLocale = $locale ?? current_locale();

        return (bool) data_get(supported_locales(), "{$activeLocale}.rtl", false);
    }
}

if (! function_exists('locale_switch_url')) {
    function locale_switch_url(string $locale): string
    {
        return route('lang.switch', ['locale' => $locale]);
    }
}

if (! function_exists('localized_current_url')) {
    function localized_current_url(string $locale): string
    {
        $route = request()->route();

        if ($route instanceof IlluminateRoute && is_string($route->getName())) {
            $routeName = $route->getName();

            foreach (array_keys(app(LaravelLocalization::class)->getSupportedLocales()) as $supportedLocale) {
                if ($supportedLocale !== default_locale() && Str::startsWith($routeName, $supportedLocale.'.')) {
                    $routeName = Str::after($routeName, $supportedLocale.'.');

                    break;
                }
            }

            return localized_route(
                $routeName,
                [...$route->parameters(), ...request()->query()],
                locale: $locale,
            );
        }

        return localized_url($locale, request()->fullUrl());
    }
}

if (! function_exists('localized_url')) {
    function localized_url(string $locale, ?string $url = null): string
    {
        $localization = app(LaravelLocalization::class);
        $targetLocale = $localization->checkLocaleInSupportedLocales($locale)
            ? $locale
            : $localization->getDefaultLocale();

        return (string) $localization->getLocalizedURL(
            $targetLocale,
            $url ?? request()->fullUrl(),
        );
    }
}

if (! function_exists('localized_route')) {
    function localized_route(string $name, mixed $parameters = [], bool $absolute = true, ?string $locale = null): string
    {
        $localization = app(LaravelLocalization::class);
        $targetLocale = is_string($locale) && $localization->checkLocaleInSupportedLocales($locale)
            ? $locale
            : $localization->getCurrentLocale();
        $localizedParameters = localized_route_parameters($parameters, $targetLocale);
        $localizedName = $targetLocale === $localization->getDefaultLocale()
            ? $name
            : $targetLocale.'.'.$name;
        $url = route(Route::has($localizedName) ? $localizedName : $name, $localizedParameters, $absolute);

        $localizedUrl = (string) $localization->getLocalizedURL(
            $targetLocale,
            $url,
            Arr::wrap($localizedParameters),
        );

        if ($absolute) {
            return $localizedUrl;
        }

        $path = (string) parse_url($localizedUrl, PHP_URL_PATH);
        $query = parse_url($localizedUrl, PHP_URL_QUERY);
        $fragment = parse_url($localizedUrl, PHP_URL_FRAGMENT);

        return $path
            .(is_string($query) ? '?'.$query : '')
            .(is_string($fragment) ? '#'.$fragment : '');
    }
}

if (! function_exists('localized_route_parameters')) {
    function localized_route_parameters(mixed $parameters, string $locale): mixed
    {
        if ($parameters instanceof LocalizedUrlRoutable) {
            return $parameters->getLocalizedRouteKey($locale);
        }

        if (! is_array($parameters)) {
            return $parameters;
        }

        return array_map(
            fn (mixed $parameter): mixed => $parameter instanceof LocalizedUrlRoutable
                ? $parameter->getLocalizedRouteKey($locale)
                : $parameter,
            $parameters,
        );
    }
}

if (! function_exists('placeholder_image_url')) {
    function placeholder_image_url(): string
    {
        return asset('images/placeholder.png');
    }
}

if (! function_exists('media_or_placeholder')) {
    function media_or_placeholder($model, string $collection, string $conversion = ''): string
    {
        if (! is_object($model) || ! method_exists($model, 'getFirstMediaUrl')) {
            return placeholder_image_url();
        }

        /** @var object $model */
        $url = $model->getFirstMediaUrl($collection, $conversion);

        return $url ? asset($url) : placeholder_image_url();
    }
}

if (! function_exists('admin_yes_no_label')) {
    function admin_yes_no_label(bool $state): string
    {
        return $state ? __('admin.values.yes') : __('admin.values.no');
    }
}

if (! function_exists('localized_model_attribute')) {
    function localized_model_attribute(object $record, string $field): ?string
    {
        $isTranslatable = method_exists($record, 'getTranslatableAttributes')
            && in_array($field, $record->getTranslatableAttributes(), true);

        if (! $isTranslatable) {
            $value = data_get($record, $field);

            return is_scalar($value) ? (string) $value : null;
        }

        return $record->getTranslation($field, app()->getLocale()) ?: $record->getTranslation($field, 'ar') ?: $record->getTranslation($field, 'en');
    }
}

if (! function_exists('setting_value')) {
    function setting_value(string $name, ?string $group = null, mixed $default = null): mixed
    {
        $value = Setting::getValue($name, $group);

        if ($value === null || $value === '') {
            return $default;
        }

        if (is_array($default)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : $default;
        }

        return $value;
    }
}

if (! function_exists('localized_setting_value')) {
    function localized_setting_value(string $name, ?string $group = null, mixed $default = null, ?string $locale = null): string
    {
        $value = setting_value($name, $group, is_array($default) ? $default : []);
        $locale ??= app()->getLocale();

        if (! is_array($value)) {
            return is_scalar($value) ? (string) $value : '';
        }

        return (string) ($value[$locale] ?? $value['ar'] ?? $value['en'] ?? '');
    }
}

if (! function_exists('get_app_version')) {
    function get_app_version(): string
    {
        return '1.0.0';
    }
}
