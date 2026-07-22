<?php

namespace App\Http\Controllers\Website;

use App\Enums\AtharPlacement;
use App\Http\Controllers\Controller;
use App\Support\AtharPublicProof;
use App\Support\SiteContent;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('website.services', [
            'services' => SiteContent::services(),
            'process' => SiteContent::process(),
            'athar' => AtharPublicProof::forPlacement(AtharPlacement::Services, app()->getLocale()),
        ]);
    }
}
