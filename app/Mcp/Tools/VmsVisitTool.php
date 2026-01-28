<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Tools\Tool;
use App\Models\Visit;
use App\Models\Visitor;

class VmsVisitTool extends Tool
{
    protected string $name = 'get_visit';
    protected string $description = 'Get detailed information about a specific visit by ID';

    protected array $parameters = [
        'id' => [
            'type' => 'integer',
            'description' => 'Visit ID',
            'required' => true,
        ],
    ];

    public function execute(array $parameters): array
    {
        $id = $parameters['id'];

        $visit = Visit::with(['visitor', 'host', 'visitType'])->find($id);

        if (!$visit) {
            return $this->error("Visit with ID {$id} not found.");
        }

        $visitorName = $visit->visitor ? $visit->visitor->name : 'N/A';
        $hostName = $visit->host ? $visit->host->name : 'N/A';
        $visitType = $visit->visitType ? $visit->visitType->name : 'N/A';
        $checkin = $visit->checked_in_at ? $visit->checked_in_at->format('Y-m-d H:i') : 'Not checked in';
        $checkout = $visit->checked_out_at ? $visit->checked_out_at->format('Y-m-d H:i') : 'Not checked out';

        return $this->text("Visit Information\n\nID: {$visit->id}\nVisitor: {$visitorName}\nHost: {$hostName}\nType: {$visitType}\nPurpose: {$visit->purpose}\nStatus: {$visit->status}\nSchedule: {$visit->scheduled_at}\nCheck-in: {$checkin}\nCheck-out: {$checkout}\nCreated: {$visit->created_at->format('Y-m-d H:i')}");
    }
}

class VmsSearchVisitsTool extends Tool
{
    protected string $name = 'search_visits';
    protected string $description = 'Search for visits with filters like status, date, visitor';

    protected array $parameters = [
        'status' => [
            'type' => 'string',
            'description' => 'Filter by status (pending, approved, rejected, completed, checked_in, checked_out)',
            'required' => false,
        ],
        'visitor_name' => [
            'type' => 'string',
            'description' => 'Filter by visitor name',
            'required' => false,
        ],
        'limit' => [
            'type' => 'integer',
            'description' => 'Maximum results (default: 10)',
            'required' => false,
            'default' => 10,
        ],
    ];

    public function execute(array $parameters): array
    {
        $status = $parameters['status'] ?? null;
        $visitorName = $parameters['visitor_name'] ?? null;
        $limit = $parameters['limit'] ?? 10;

        $query = Visit::with(['visitor', 'host']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($visitorName) {
            $query->whereHas('visitor', function ($q) use ($visitorName) {
                $q->where('name', 'like', "%{$visitorName}%");
            });
        }

        $visits = $query->orderBy('created_at', 'desc')->limit($limit)->get();

        if ($visits->isEmpty()) {
            return $this->text("No visits found matching the criteria.");
        }

        $list = $visits->map(function ($v) {
            $visitorName = $v->visitor ? $v->visitor->name : 'Unknown';
            return "- Visit #{$v->id}: {$visitorName} - {$v->status} ({$v->created_at->format('Y-m-d')})";
        })->implode("\n");

        return $this->text("Search Results ({$visits->count()} visits found)\n\n{$list}");
    }
}
