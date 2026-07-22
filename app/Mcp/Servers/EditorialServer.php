<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\ArchiveEditorialArticleTool;
use App\Mcp\Tools\CreateEditorialDraftTool;
use App\Mcp\Tools\GetEditorialArticleTool;
use App\Mcp\Tools\PublishEditorialArticleTool;
use App\Mcp\Tools\UpdateEditorialDraftTool;
use App\Mcp\Tools\UploadEditorialImageTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Editorial Server')]
#[Version('1.0.0')]
#[Instructions('Use this server only for editorial article work. Create and update drafts first, upload a managed hero image, then publish only after the caller has explicitly confirmed publication. Every modifying tool requires the current article revision.')]
class EditorialServer extends Server
{
    protected array $tools = [
        CreateEditorialDraftTool::class,
        GetEditorialArticleTool::class,
        UpdateEditorialDraftTool::class,
        UploadEditorialImageTool::class,
        PublishEditorialArticleTool::class,
        ArchiveEditorialArticleTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
