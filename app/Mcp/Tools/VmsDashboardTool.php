<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Tools\Tool;
use App\Models\Visit;
use App\Models\Visitor;
use App\Models\User;

class VmsDashboardTool extends Tool
{
    protected string $name = 'get_dashboard_stats';
    protected string $description = 'Get dashboard statistics and metrics for the visitor management system';

    protected array $parameters = [
        'period' => [
            'type' => 'string',
            'description' => 'Time period: today, this_week, this_month, all (default: this_week)',
            'required' => false,
            'default' => 'this_week',
        ],
    ];

    public function execute(array $parameters): array
    {
        $period = $parameters['period'] ?? 'this_week';

        switch ($period) {
            case 'today':
                $startDate = now()->startOfDay();
                break;
            case 'this_week':
                $startDate = now()->startOfWeek();
                break;
            case 'this_month':
                $startDate = now()->startOfMonth();
                break;
            default:
                $startDate = now()->subDays(30);
        }

        $stats = [
            'total_visitors' => Visitor::count(),
            'blocked_visitors' => Visitor::where('is_blocked', true)->count(),
            'total_visits' => Visit::count(),
            'visits_in_period' => Visit::where('created_at', '>=', $startDate)->count(),
            'pending_visits' => Visit::where('status', 'pending')->count(),
            'approved_visits' => Visit::where('status', 'approved')->count(),
            'completed_visits' => Visit::where('status', 'completed')->count(),
            'checked_in_today' => Visit::whereDate('checked_in_at', today())->count(),
            'total_hosts' => User::count(),
            'period' => $period,
        ];

        return $this->text("Dashboard Statistics ({$period})\n\n" .
            "Visitors:\n" .
            "- Total: {$stats['total_visitors']}\n" .
            "- Blocked: {$stats['blocked_visitors']}\n\n" .
            "Visits:\n" .
            "- Total: {$stats['total_visits']}\n" .
            "- This {$period}: {$stats['visits_in_period']}\n" .
            "- Pending: {$stats['pending_visits']}\n" .
            "- Approved: {$stats['approved_visits']}\n" .
            "- Completed: {$stats['completed_visits']}\n" .
            "- Checked-in Today: {$stats['checked_in_today']}\n\n" .
            "Users/Hosts: {$stats['total_hosts']}");
    }
}
