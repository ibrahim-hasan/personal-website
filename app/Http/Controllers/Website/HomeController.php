<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Services\ArticleAudio\HomepageAudioSpotlight;
use App\Support\Editorial\ArticleCatalog;
use App\Support\PortfolioAtlas;
use App\Support\SiteContent;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly ArticleCatalog $articles,
        private readonly HomepageAudioSpotlight $audioSpotlight,
    ) {}

    public function index(): View
    {
        $featuredArticles = $this->articles->featured();

        return view('website.home', [
            ...SiteContent::home(),
            'companies' => PortfolioAtlas::companies(),
            'experience' => PortfolioAtlas::experience(),
            'lenses' => PortfolioAtlas::lenses(),
            'projects' => PortfolioAtlas::homepageProjects(),
            'articles' => $featuredArticles,
            'audioArticle' => $this->audioSpotlight->firstAvailable($featuredArticles, app()->getLocale()),
        ]);
    }
}
