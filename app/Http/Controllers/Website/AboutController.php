<?php

namespace App\Http\Controllers\Website;

use App\Enums\AtharPlacement;
use App\Http\Controllers\Controller;
use App\Support\AtharPublicProof;
use App\Support\PortfolioAtlas;
use App\Support\SiteContent;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function __invoke(): View
    {
        return view('website.about', [
            'biography' => SiteContent::aboutBiography(),
            'companies' => PortfolioAtlas::companies(),
            'athar' => AtharPublicProof::forPlacement(AtharPlacement::About, app()->getLocale()),
        ]);
    }
}
