<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Support\SiteContent;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function privacy(): View
    {
        return $this->render('privacy');
    }

    public function terms(): View
    {
        return $this->render('terms');
    }

    public function cookies(): View
    {
        return $this->render('cookies_policy');
    }

    private function render(string $document): View
    {
        $contact = SiteContent::contact();
        $retention = config('legal.retention');

        /**
         * @var array{
         *     title: string,
         *     description: string,
         *     eyebrow: string,
         *     effective_date: string,
         *     introduction: string,
         *     sections: list<array{
         *         heading: string,
         *         paragraphs?: list<string>,
         *         bullets?: list<string>,
         *         facts?: list<array{
         *             title: string,
         *             tokens?: list<string>,
         *             values: list<array{label: string, value: string}>
         *         }>
         *     }>
         * } $content
         */
        $content = trans("legal.{$document}", [
            'email' => $contact['email'],
            'archived_inquiries_days' => (string) $retention['archived_inquiries_days'],
            'resolved_reports_days' => (string) $retention['resolved_reports_days'],
        ]);

        return view('website.legal', [
            'document' => $document,
            'content' => $content,
            'contact' => $contact,
        ]);
    }
}
