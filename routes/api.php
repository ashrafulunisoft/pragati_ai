<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| MCP Server endpoint (Problem 3 fix)
|--------------------------------------------------------------------------
| JSON-RPC 2.0 over Streamable HTTP (protocol 2025-06-18).
| Methods: initialize, notifications/initialized, tools/list, tools/call, ping.
| Auth: Bearer MCP_SERVER_TOKEN (401 without it).
*/
Route::post('/mcp', \App\Http\Controllers\Mcp\McpServerController::class);
