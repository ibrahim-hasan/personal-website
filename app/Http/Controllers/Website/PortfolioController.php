<?php

namespace App\Http\Controllers\Website;

use App\Enums\AtharPlacement;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Support\AtharPublicProof;
use App\Support\PortfolioAtlas;
use App\Support\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $lenses = PortfolioAtlas::lenses();
        $requestedLens = $request->query('lens');

        if ($requestedLens !== null && (! is_string($requestedLens) || trim($requestedLens) === '')) {
            return redirect()->to(localized_route('work'));
        }

        $lens = is_string($requestedLens) ? $requestedLens : null;

        if ($lens !== null && ! collect($lenses)->pluck('id')->contains($lens)) {
            abort(404);
        }

        return view('website.work', [
            'work' => array_values(array_filter(
                PortfolioAtlas::projects(),
                fn (array $project): bool => $lens === null || $project['lens'] === $lens,
            )),
            'lenses' => $lenses,
            'selectedLens' => $lens,
            'isFiltered' => $lens !== null,
            'services' => SiteContent::services(),
            'athar' => AtharPublicProof::forPlacement(AtharPlacement::Work, app()->getLocale()),
        ]);
    }

    public function show(Project $project): RedirectResponse
    {
        return redirect()->to(localized_route('work').'#project-'.$project->key);
    }
}
