<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Support\SiteContent;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function __invoke(): View
    {
        return view('website.work', [
            'work' => SiteContent::work(),
            'services' => SiteContent::services(),
        ]);
    }
}
