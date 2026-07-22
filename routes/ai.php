<?php

use App\Http\Middleware\AssignApiRequestId;
use App\Mcp\Servers\EditorialServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/editorial', EditorialServer::class)
    ->middleware([AssignApiRequestId::class, 'article-scope', 'throttle:editorial-api-write']);
