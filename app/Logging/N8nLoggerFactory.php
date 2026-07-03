<?php

namespace App\Logging;

use Monolog\Level;
use Monolog\Logger;

class N8nLoggerFactory
{
    public function __invoke(array $config): Logger
    {
        $level = Level::fromName(strtoupper((string) ($config['level'] ?? 'warning')));

        return new Logger('n8n', [
            new N8nHandler($config, $level),
        ]);
    }
}
