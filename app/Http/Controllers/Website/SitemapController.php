<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Services\Seo\SeoDocumentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SitemapController extends Controller
{
    public function __construct(private readonly SeoDocumentService $documents) {}

    public function __invoke(Request $request): Response
    {
        return $this->documents->sitemapResponse($request);
    }
}
