<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Support\Editorial\ArticleCatalog;
use App\Support\Editorial\ArticleTopicClusters;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WritingController extends Controller
{
    public function __construct(private readonly ArticleCatalog $articles) {}

    public function __invoke(Request $request): View|RedirectResponse
    {
        $allArticles = $this->articles->localized(includeBody: false);
        $topics = [];

        foreach ($allArticles as $article) {
            foreach ($article['topic_keys'] as $index => $key) {
                $topics[$key] ??= $article['topics'][$index] ?? $key;
            }
        }

        $requestedTopic = $request->query('topic');
        $hasTopicParameter = array_key_exists('topic', $request->query->all());

        if ($hasTopicParameter && (! is_string($requestedTopic) || trim($requestedTopic) === '')) {
            return redirect()->to(localized_route('writing'));
        }

        $selectedTopic = is_string($requestedTopic) ? $requestedTopic : null;

        if ($selectedTopic !== null) {
            $canonicalTopic = ArticleTopicClusters::forTopicKeys([$selectedTopic])[0] ?? null;

            if ($canonicalTopic !== null && $canonicalTopic !== $selectedTopic && array_key_exists($canonicalTopic, $topics)) {
                return redirect()->to(localized_route('writing', array_replace(
                    $request->query(),
                    ['topic' => $canonicalTopic],
                )), 301);
            }
        }

        abort_unless($selectedTopic === null || array_key_exists($selectedTopic, $topics), 404);

        $articles = $selectedTopic === null
            ? $allArticles
            : array_values(array_filter(
                $allArticles,
                fn (array $article): bool => in_array($selectedTopic, $article['topic_keys'], true),
            ));

        return view('website.writing', [
            'articles' => $articles,
            'topics' => $topics,
            'selectedTopic' => $selectedTopic,
            'isFiltered' => $selectedTopic !== null,
        ]);
    }
}
