<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Server Name
    |--------------------------------------------------------------------------
    |
    | The name of your MCP server as it will appear to AI assistants.
    |
    */
    'name' => 'Pragati AI VMS',
    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | MCP Tools
    |--------------------------------------------------------------------------
    |
    | Register all your MCP tool classes here. These classes will be
    | automatically discovered and made available to AI assistants.
    |
    */
    'tools' => [
        // Database query tool (natural language)
        \App\Mcp\Tools\VmsDatabaseTool::class,
        
        // Visitor management
        \App\Mcp\Tools\VmsVisitorTool::class,
        \App\Mcp\Tools\VmsSearchVisitorsTool::class,
        
        // Visit management
        \App\Mcp\Tools\VmsVisitTool::class,
        \App\Mcp\Tools\VmsSearchVisitsTool::class,
        
        // Dashboard & AI
        \App\Mcp\Tools\VmsDashboardTool::class,
        \App\Mcp\Tools\VmsAskAiTool::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Resources
    |--------------------------------------------------------------------------
    |
    | Resources are data sources that can be accessed by AI assistants.
    |
    */
    'resources' => [
        \App\Mcp\Resources\VmsDatabaseSchemaResource::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Prompts
    |--------------------------------------------------------------------------
    |
    | Pre-defined prompts that can be used by AI assistants.
    |
    */
    'prompts' => [
        \App\Mcp\Prompts\VmsChatPrompt::class,
    ],
];
