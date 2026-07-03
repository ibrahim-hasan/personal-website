<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Support\SiteContent;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __invoke(): View
    {
        return view('website.contact', [
            'contact' => SiteContent::contact(),
            'services' => SiteContent::services(),
        ]);
    }
}
