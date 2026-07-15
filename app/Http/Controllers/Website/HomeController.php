<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Support\Editorial\ArticleCatalog;
use App\Support\PortfolioAtlas;
use App\Support\SiteContent;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(private readonly ArticleCatalog $articles) {}

    public function index(): View
    {
        return view('website.home', [
            ...SiteContent::home(),
            'companies' => PortfolioAtlas::companies(),
            'experience' => PortfolioAtlas::experience(),
            'lenses' => PortfolioAtlas::lenses(),
            'projects' => PortfolioAtlas::featuredProjects(limit: 5),
            'articles' => $this->articles->featured(),
        ]);
    }
}
