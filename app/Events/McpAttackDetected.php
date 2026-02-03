<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class McpAttackDetected
{
    use Dispatchable, SerializesModels;

    public array $data;
    public array $mcp;

    public function __construct(array $data, array $mcp)
    {
        $this->data = $data;
        $this->mcp  = $mcp;
    }
}
