<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Support\Editorial\ArticleCatalog;
use Illuminate\View\View;

class WritingController extends Controller
{
    public function __construct(private readonly ArticleCatalog $articles) {}

    public function __invoke(): View
    {
        return view('website.writing', [
            'articles' => $this->articles->localized(),
        ]);
    }
}
