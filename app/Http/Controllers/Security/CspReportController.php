<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Services\Security\CspReportSanitizer;
use App\Services\Security\CspReportSignalStore;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

final class CspReportController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, CspReportSanitizer $sanitizer, CspReportSignalStore $signals): Response
    {
        if (! (bool) config('security.csp.report_only', false)) {
            return $this->emptyResponse();
        }

        try {
            $body = $request->getContent();

            if (! is_string($body) || strlen($body) > $this->maximumRequestBytes()) {
                return $this->emptyResponse();
            }

            $payload = json_decode($body, true, flags: JSON_THROW_ON_ERROR);

            if (! is_array($payload)) {
                return $this->emptyResponse();
            }

            foreach ($sanitizer->signals($payload) as $signal) {
                $signals->record($signal['directive'], $signal['category']);
            }
        } catch (Throwable) {
            // Never log or return a browser report, which can contain URLs or script samples.
        }

        return $this->emptyResponse();
    }

    private function maximumRequestBytes(): int
    {
        return min(65_536, max(1_024, (int) config('security.csp.reporting.max_request_bytes', 16_384)));
    }

    private function emptyResponse(): Response
    {
        return response()->noContent()->withHeaders([
            'Cache-Control' => 'no-store',
            'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, noarchive',
        ]);
    }
}
