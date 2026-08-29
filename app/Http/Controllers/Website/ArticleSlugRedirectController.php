<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Article as ArticleRecord;
use App\Models\ArticleSlugRedirect;
use App\Support\Editorial\ArticleCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class ArticleSlugRedirectController extends Controller
{
    public function __construct(private readonly ArticleCatalog $articles) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $route = $request->route();

        abort_unless($route instanceof Route, 404);

        $slug = $route->originalParameter('article');

        abort_unless(is_string($slug) && trim($slug) !== '', 404);

        $redirect = ArticleSlugRedirect::query()
            ->where('locale', current_locale())
            ->where('slug', $slug)
            ->with('article:id,key,slug_ar,slug_en')
            ->first();
        $articleRecord = $redirect?->article;

        abort_unless($articleRecord instanceof ArticleRecord, 404);

        $currentSlug = $articleRecord->getAttribute('slug_'.current_locale());

        abort_unless(is_string($currentSlug) && trim($currentSlug) !== '', 404);

        $article = $this->articles->findByKey($articleRecord->key);

        abort_unless($article !== null, 404);

        $url = localized_route(
            'writing.show',
            ['article' => $currentSlug],
            locale: current_locale(),
        );
        $query = $request->getQueryString();

        if (is_string($query) && $query !== '') {
            $url .= '?'.$query;
        }

        return redirect()->to($url, 301);
    }
}
