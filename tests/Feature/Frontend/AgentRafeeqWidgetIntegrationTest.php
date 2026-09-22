<?php

namespace Tests\Feature\Frontend;

use App\Support\AgentRafeeqConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentRafeeqWidgetIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_widget_loader_is_configured_for_public_arabic_and_english_pages(): void
    {
        config()->set('services.agent_rafeeq_widget', [
            'enabled' => true,
            'script_url' => 'http://agent-rafeeq-saas.test/widget.js',
            'public_key' => 'pk_live_widget_integration_test',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-agent-rafeeq-widget', false)
            ->assertSee('data-widget-url="http://agent-rafeeq-saas.test/widget.js"', false)
            ->assertSee('data-bot-key="pk_live_widget_integration_test"', false)
            ->assertSee('data-locale="ar"', false)
            ->assertSee('data-history="session"', false);

        $this->get('/en')
            ->assertOk()
            ->assertSee('data-agent-rafeeq-widget', false)
            ->assertSee('data-locale="en"', false);
    }

    public function test_the_widget_loader_stays_off_private_and_legal_routes(): void
    {
        config()->set('services.agent_rafeeq_widget', [
            'enabled' => true,
            'script_url' => 'http://agent-rafeeq-saas.test/widget.js',
            'public_key' => 'pk_live_widget_integration_test',
        ]);

        foreach (['/privacy', '/cookies', '/terms', '/reader/login'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertDontSee('data-agent-rafeeq-widget', false);
        }
    }

    public function test_an_incomplete_or_unsafe_widget_configuration_renders_nothing(): void
    {
        config()->set('services.agent_rafeeq_widget', [
            'enabled' => true,
            'script_url' => 'javascript:alert(1)',
            'public_key' => 'pk_live_widget_integration_test',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('data-agent-rafeeq-widget', false);
    }

    public function test_production_rejects_insecure_or_credentialed_widget_urls(): void
    {
        $this->app->instance('env', 'production');

        foreach ([
            'http://agent.example.com/widget.js',
            'https://user:password@agent.example.com/widget.js',
            'https://agent.example.com/widget.js?token=secret',
            'https://127.0.0.1/widget.js',
            'https://agent.local/widget.js',
            'https://[::1]/widget.js',
            'https://[::ffff:127.0.0.1]/widget.js',
            'https://localhost./widget.js',
            'https://agent.local./widget.js',
            'https://127.0.0.1./widget.js',
            'https://0177.0.0.1/widget.js',
            'https://0x7f.0.0.1/widget.js',
            'https://agent.invalid/widget.js',
            'https://agent.example/widget.js',
            'https://agent.home.arpa/widget.js',
            'https://invalid_host.example.com/widget.js',
        ] as $url) {
            config()->set('services.agent_rafeeq_widget', [
                'enabled' => true,
                'script_url' => $url,
                'public_key' => 'pk_live_widget_integration_test',
            ]);

            $this->get('/')->assertOk()->assertDontSee('data-agent-rafeeq-widget', false);
        }
    }

    public function test_production_allows_valid_https_hostnames_with_explicit_ports(): void
    {
        $this->app->instance('env', 'production');
        config()->set('services.agent_rafeeq_widget', [
            'enabled' => true,
            'script_url' => 'https://Agent.Example.com:443/widget.js',
            'public_key' => 'pk_live_widget_integration_test',
        ]);

        $this->assertSame('https://Agent.Example.com:443/widget.js', AgentRafeeqConfiguration::widgetUrl());
        $this->assertSame('https://agent.example.com:443', AgentRafeeqConfiguration::widgetOrigin());
    }

    public function test_only_the_valid_configured_assistant_origin_is_added_to_csp(): void
    {
        config()->set('security.csp.report_only', true);
        config()->set('services.agent_rafeeq_widget', [
            'enabled' => true,
            'script_url' => 'https://agent.example.com/widget.js',
            'public_key' => 'pk_live_widget_integration_test',
        ]);
        config()->set('services.agent_rafeeq_sync.secret_key', 'sk_live_must_remain_private');

        $response = $this->get('/');
        $response->assertOk()->assertDontSee('sk_live_must_remain_private');
        $policy = (string) $response->headers->get('Content-Security-Policy-Report-Only');
        $this->assertSame(5, substr_count($policy, 'https://agent.example.com'));
        $this->assertStringNotContainsString('https://agent.example.com/widget.js', $policy);
        $this->assertStringNotContainsString('unsafe-eval', $policy);

        config()->set('services.agent_rafeeq_widget.script_url', 'https://secret@agent.example.com/widget.js');
        $this->assertStringNotContainsString('agent.example.com', (string) $this->get('/')->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_assistant_privacy_and_session_storage_are_disclosed_in_both_languages(): void
    {
        $this->get('/en/privacy')->assertOk()->assertSee('AI assistant')->assertSee('does not delete server records');
        $this->get('/privacy')->assertOk()->assertSee('المساعد الذكي')->assertSee('لا يحذف سجلات الخادم');
        $this->get('/en/cookies')->assertOk()->assertSee('arw_messages:*')->assertSee('one hour of inactivity');
        $this->get('/cookies')->assertOk()->assertSee('arw_messages:*')->assertSee('ساعة من عدم النشاط');
    }
}
