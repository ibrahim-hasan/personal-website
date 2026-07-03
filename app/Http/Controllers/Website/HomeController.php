<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Support\SiteContent;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('website.home', SiteContent::home());
    }
}
