<?php

namespace App\Listeners;

use App\Events\McpAttackDetected;
use App\Services\SecurityEmailService;

class SendSecurityAlertEmail
{
    /**
     * Handle the event.
     */
    public function handle(McpAttackDetected $event): void
    {
        SecurityEmailService::sendAttackAlert($event->data, $event->mcp);
    }
}
