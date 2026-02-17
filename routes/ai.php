<?php

use Laravel\Mcp\Facades\Mcp;
use App\Mcp\Servers\VmsChatServer;

/*
|--------------------------------------------------------------------------
| AI/MCP Routes
|--------------------------------------------------------------------------
|
| These routes are for the Model Context Protocol (MCP) server.
| AI clients connect here to access tools, resources, and prompts.
|
*/

// Register the VMS Chat MCP Server
Mcp::web('/chat', VmsChatServer::class);

/*
|--------------------------------------------------------------------------
| Available MCP Endpoints:
|--------------------------------------------------------------------------
|
| POST /mcp/chat - Main MCP endpoint for AI communication
|
| AI can use these tools:
| - query_database: Ask any database question
| - get_visitor: Get visitor details by ID
| - search_visitors: Search visitors by name/email/phone
| - get_visit: Get visit details by ID
| - search_visits: Search visits with filters
| - get_dashboard_stats: Get dashboard statistics
| - ask_ai: General AI chat for non-database questions
|
*/
