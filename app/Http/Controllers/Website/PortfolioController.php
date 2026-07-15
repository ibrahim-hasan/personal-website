<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Support\PortfolioAtlas;
use App\Support\SiteContent;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function __invoke(): View
    {
        return view('website.work', [
            'work' => PortfolioAtlas::projects(),
            'lenses' => PortfolioAtlas::lenses(),
            'services' => SiteContent::services(),
        ]);
    }
}
