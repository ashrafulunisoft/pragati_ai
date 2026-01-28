<?php

namespace App\Mcp\Servers;

use Laravel\Mcp\Server;
use App\Mcp\Tools\VmsDatabaseTool;
use App\Mcp\Tools\VmsVisitorTool;
use App\Mcp\Tools\VmsSearchVisitorsTool;
use App\Mcp\Tools\VmsVisitTool;
use App\Mcp\Tools\VmsSearchVisitsTool;
use App\Mcp\Tools\VmsDashboardTool;
use App\Mcp\Tools\VmsAskAiTool;
use App\Mcp\Prompts\VmsChatPrompt;
use App\Mcp\Resources\VmsDatabaseSchemaResource;

class VmsChatServer extends Server
{
    protected string $name = 'VMS Chat Server';
    protected string $version = '1.0.0';

    protected array $tools = [
        // Database query tool (universal)
        VmsDatabaseTool::class,
        
        // Visitor tools
        VmsVisitorTool::class,
        VmsSearchVisitorsTool::class,
        
        // Visit tools
        VmsVisitTool::class,
        VmsSearchVisitsTool::class,
        
        // Dashboard tool
        VmsDashboardTool::class,
        
        // General AI chat tool
        VmsAskAiTool::class,
    ];

    protected array $resources = [
        // Database schema resource
        VmsDatabaseSchemaResource::class,
    ];

    protected array $prompts = [
        VmsChatPrompt::class,
    ];
}
