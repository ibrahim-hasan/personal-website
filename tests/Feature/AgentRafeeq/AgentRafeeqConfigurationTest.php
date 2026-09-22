<?php

namespace Tests\Feature\AgentRafeeq;

use App\Support\AgentRafeeqConfiguration;
use Tests\TestCase;

class AgentRafeeqConfigurationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance('env', 'production');
        config()->set('services.agent_rafeeq_widget', [
            'enabled' => true,
            'script_url' => 'https://agent.example.com/widget.js',
            'public_key' => 'pk_live_widget_test',
        ]);
    }

    public function test_widget_scripts_accept_only_a_single_raw_hexadecimal_version_parameter(): void
    {
        foreach ([str_repeat('a', 16), str_repeat('9', 32), str_repeat('Ab09', 16)] as $version) {
            $url = 'https://agent.example.com/widget.js?v='.$version;
            config()->set('services.agent_rafeeq_widget.script_url', $url);

            $this->assertSame($url, AgentRafeeqConfiguration::widgetUrl());
            $this->assertSame('https://agent.example.com', AgentRafeeqConfiguration::widgetOrigin());
        }
    }

    public function test_widget_scripts_reject_other_query_shapes_and_values(): void
    {
        $version = str_repeat('a', 16);
        foreach ([
            '', 'v=', 'v='.str_repeat('a', 15), 'v='.str_repeat('a', 65),
            'v='.str_repeat('g', 16), 'v='.$version.'&v='.$version,
            'v='.$version.'&token=secret', 'token=secret&v='.$version,
            'v='.$version.'&', 'v='.$version.';', 'v='.$version.'%20',
            'v='.$version.'+', 'v='.$version.'%0A', 'V='.$version,
            '%76='.$version, 'v=%61'.str_repeat('a', 15), 'v[]='.$version,
        ] as $query) {
            config()->set('services.agent_rafeeq_widget.script_url', 'https://agent.example.com/widget.js?'.$query);

            $this->assertNull(AgentRafeeqConfiguration::widgetUrl(), $query);
            $this->assertNull(AgentRafeeqConfiguration::widgetOrigin(), $query);
        }
    }

    public function test_a_valid_version_does_not_allow_unsafe_widget_urls(): void
    {
        $query = '?v='.str_repeat('a', 16);
        foreach ([
            'http://agent.example.com/widget.js'.$query,
            'https://user:password@agent.example.com/widget.js'.$query,
            'https://127.0.0.1/widget.js'.$query,
            'https://agent.local/widget.js'.$query,
            'https://agent.example.com/widget.js'.$query.'#fragment',
        ] as $url) {
            config()->set('services.agent_rafeeq_widget.script_url', $url);

            $this->assertNull(AgentRafeeqConfiguration::widgetUrl());
            $this->assertNull(AgentRafeeqConfiguration::widgetOrigin());
        }
    }

    public function test_api_origins_and_general_urls_still_reject_version_queries(): void
    {
        $query = '?v='.str_repeat('a', 16);

        $this->assertSame('https://agent.example.com', AgentRafeeqConfiguration::safeUrl('https://agent.example.com/', originOnly: true));
        $this->assertNull(AgentRafeeqConfiguration::safeUrl('https://agent.example.com/'.$query, originOnly: true));
        $this->assertNull(AgentRafeeqConfiguration::safeUrl('https://agent.example.com/'.$query, originOnly: true, allowVersionQuery: true));
        $this->assertNull(AgentRafeeqConfiguration::safeUrl('https://agent.example.com/widget.js'.$query));
    }
}
