<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Tools\Tool;
use App\Models\Visitor;

class VmsVisitorTool extends Tool
{
    protected string $name = 'get_visitor';
    protected string $description = 'Get detailed information about a specific visitor by ID';

    protected array $parameters = [
        'id' => [
            'type' => 'integer',
            'description' => 'Visitor ID',
            'required' => true,
        ],
    ];

    public function execute(array $parameters): array
    {
        $id = $parameters['id'];

        $visitor = Visitor::with(['blocks', 'otps'])->find($id);

        if (!$visitor) {
            return $this->error("Visitor with ID {$id} not found.");
        }

        return $this->text($this->formatVisitorInfo($visitor));
    }

    private function formatVisitorInfo($visitor): string
    {
        $name = $visitor->name;
        $phone = $visitor->phone;
        $email = $visitor->email ?? 'N/A';
        $address = $visitor->address ?? 'N/A';
        $blocked = $visitor->is_blocked ? 'Yes' : 'No';
        $created = $visitor->created_at->format('Y-m-d H:i');
        $totalVisits = $visitor->visits->count();

        return "👤 Visitor Information\n\nID: {$visitor->id}\nName: {$name}\nPhone: {$phone}\nEmail: {$email}\nAddress: {$address}\nBlocked: {$blocked}\nCreated: {$created}\n\nTotal Visits: {$totalVisits}";
    }
}

class VmsSearchVisitorsTool extends Tool
{
    protected string $name = 'search_visitors';
    protected string $description = 'Search for visitors by name, email, or phone';

    protected array $parameters = [
        'search' => [
            'type' => 'string',
            'description' => 'Search term (name, email, or phone)',
            'required' => true,
        ],
        'limit' => [
            'type' => 'integer',
            'description' => 'Maximum results to return (default: 10)',
            'required' => false,
            'default' => 10,
        ],
    ];

    public function execute(array $parameters): array
    {
        $search = $parameters['search'];
        $limit = $parameters['limit'] ?? 10;

        $visitors = Visitor::where('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->orWhere('phone', 'like', "%{$search}%")
            ->limit($limit)
            ->get();

        if ($visitors->isEmpty()) {
            return $this->text("No visitors found matching '{$search}'.");
        }

        $list = $visitors->map(function ($v) {
            $blocked = $v->is_blocked ? '[X]' : '[OK]';
            return "- {$v->name} (ID: {$v->id}) {$blocked} - {$v->phone}";
        })->implode("\n");

        return $this->text("Search Results ({$visitors->count()} visitors found)\n\n{$list}");
    }
}
