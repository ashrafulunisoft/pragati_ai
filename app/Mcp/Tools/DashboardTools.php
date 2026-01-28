<?php

namespace App\Mcp\Tools;

use App\Models\Visitor;
use App\Models\Visit;
use App\Models\User;

class DashboardTools
{
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        return [
            'total_visitors' => Visitor::count(),
            'total_visits' => Visit::count(),
            'pending_visits' => Visit::where('status', 'pending')->count(),
            'approved_visits' => Visit::where('status', 'approved')->count(),
            'rejected_visits' => Visit::where('status', 'rejected')->count(),
            'completed_visits' => Visit::where('status', 'completed')->count(),
            'today_visits' => Visit::whereDate('created_at', today())->count(),
            'this_week_visits' => Visit::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'total_hosts' => User::count(),
        ];
    }

    /**
     * Get system health status
     */
    public function getSystemStatus(): array
    {
        return [
            'status' => 'healthy',
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'database_connected' => true,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
