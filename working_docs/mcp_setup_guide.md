# MCP Server Setup Guide for Pragati AI VMS

## Overview

This guide explains how to use the MCP (Model Context Protocol) server in the Pragati AI VMS application. The MCP server allows AI assistants like Claude to interact with your VMS data.

## Files Created

```
app/
├── Console/
│   └── Commands/
│       └── McpServeCommand.php    # Stdio-based MCP server
├── Mcp/
│   └── Tools/
│       ├── VisitorTools.php       # Visitor-related tools
│       ├── VisitTools.php         # Visit-related tools
│       └── DashboardTools.php     # Dashboard & system tools
config/
└── mcp.php                        # MCP configuration
routes/
└── api.php                        # HTTP API routes for MCP
```

## Available Tools

| Tool Name | Description |
|-----------|-------------|
| `list_visitors` | Get all visitors with optional limit |
| `get_visitor` | Get a specific visitor by ID |
| `search_visitors` | Search visitors by name, email, or phone |
| `list_visits` | Get visits with optional status filter |
| `get_visit` | Get a specific visit by ID |
| `get_today_visits` | Get all visits for today |
| `get_dashboard_stats` | Get comprehensive dashboard statistics |
| `get_system_status` | Check system health and status |

## Usage

### Option 1: Stdio-based Server (for Cline/Claude Desktop)

Start the MCP server:
```bash
php artisan mcp:serve
```

### Option 2: HTTP API (for API integrations)

Start Laravel development server:
```bash
php artisan serve
```

API Endpoints:
- `POST /api/mcp/initialize` - Initialize MCP connection
- `POST /api/mcp/tools/list` - List all available tools
- `POST /api/mcp/tools/call` - Call a specific tool

Example API calls:
```bash
# List all tools
curl -X POST http://localhost:8000/api/mcp/tools/list

# Get dashboard stats
curl -X POST http://localhost:8000/api/mcp/tools/call \
  -H "Content-Type: application/json" \
  -d '{"name": "get_dashboard_stats", "arguments": {}}'

# List visitors (with limit)
curl -X POST http://localhost:8000/api/mcp/tools/call \
  -H "Content-Type: application/json" \
  -d '{"name": "list_visitors", "arguments": {"limit": 5}}'

# Search visitors
curl -X POST http://localhost:8000/api/mcp/tools/call \
  -H "Content-Type: application/json" \
  -d '{"name": "search_visitors", "arguments": {"query": "john", "limit": 10}}'
```

## Cline Integration

Add to `~/.config/Code/User/globalStorage/saoudrizwan.claude-dev/settings/cline_mcp_settings.json`:

```json
{
  "mcpServers": {
    "pragati_ai": {
      "command": "php",
      "args": ["/home/unisoft/pragati_ai/artisan", "mcp:serve"],
      "cwd": "/home/unisoft/pragati_ai",
      "env": {
        "APP_ENV": "local"
      }
    }
  }
}
```

After adding, restart VS Code to activate the MCP server.

## Testing the Stdio Server

```bash
# Test initialize
echo '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{}}' | php artisan mcp:serve

# Test tools/list
echo '{"jsonrpc":"2.0","id":2,"method":"tools/list","params":{}}' | php artisan mcp:serve
```

## Adding New Tools

1. Add method to appropriate tool class in `app/Mcp/Tools/`
2. Register the tool in `McpServeCommand.php` under `registerTools()`
3. Add the match case in `handleToolCall()`
4. Add the route handler in `routes/api.php`

Example adding a new tool:
```php
// In app/Mcp/Tools/VisitorTools.php
public function countVisitors(): int
{
    return Visitor::count();
}

// In McpServeCommand.php registerTools()
'count_visitors' => [
    'description' => 'Get total visitor count',
    'inputSchema' => ['type' => 'object', 'properties' => []],
],

// In handleToolCall()
'count_visitors' => $visitorTools->countVisitors(),
```

## Troubleshooting

1. **Command not found**: Run `php artisan list | grep mcp` to verify registration
2. **Database errors**: Ensure database connection is configured in `.env`
3. **Permission errors**: Check file permissions on the artisan file

## Laravel Version

This setup is compatible with Laravel 12.47.0.
