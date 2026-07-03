<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Support\SiteContent;
use Illuminate\View\View;

class WritingController extends Controller
{
    public function __invoke(): View
    {
        return view('website.writing', [
            'writing' => SiteContent::writing(),
            'toolchain' => SiteContent::toolchain(),
        ]);
    }
}
