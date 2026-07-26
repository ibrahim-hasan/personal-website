<?php

namespace Tests\Feature;

use Tests\TestCase;

class AnalyticsInteractionTrackingTest extends TestCase
{
    public function test_delegated_analytics_only_tracks_fixed_events_and_controlled_payload_attributes(): void
    {
        $javascript = $this->readProjectFile('resources/js/app.js');

        $this->assertStringContainsString('const analyticsInteractionEvents = new Set([', $javascript);
        $this->assertStringContainsString('window.IbrahimAnalytics?.track(eventName, payload)', $javascript);
        $this->assertStringContainsString("['analyticsUiLocation', 'ui_location']", $javascript);
        $this->assertStringContainsString("['analyticsDestinationCategory', 'destination_category']", $javascript);
        $this->assertStringContainsString("['analyticsServiceSlug', 'service_slug']", $javascript);
        $this->assertStringContainsString("['analyticsContentSlug', 'content_slug']", $javascript);
        $this->assertStringContainsString("['analyticsContactChannel', 'contact_channel']", $javascript);
        $this->assertStringContainsString("window.addEventListener('analytics-consent-updated', trackConsultationStates", $javascript);
        $this->assertStringNotContainsString('element.href', $javascript);
        $this->assertStringNotContainsString('element.value', $javascript);
        $this->assertStringNotContainsString('new FormData', $javascript);
        $this->assertStringNotContainsString('document.referrer', $javascript);
    }

    public function test_public_markup_exposes_only_approved_interaction_context_and_never_contact_content(): void
    {
        $navbar = $this->readProjectFile('resources/views/components/partials/navbar.blade.php');
        $footer = $this->readProjectFile('resources/views/components/partials/footer.blade.php');

        $this->assertStringContainsString('data-analytics-event="primary_cta_click"', $navbar);
        $this->assertStringContainsString('data-analytics-event="language_switch"', $navbar);
        $this->assertStringContainsString('data-analytics-event="direct_contact_click"', $footer);
        $this->assertStringContainsString('data-analytics-contact-channel="email"', $footer);
    }

    private function readProjectFile(string $path): string
    {
        $contents = file_get_contents(base_path($path));

        $this->assertIsString($contents);

        return $contents;
    }
}
