<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Visitor;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffController extends Controller
{
    /**
     * Display the staff dashboard
     */
    public function dashboard()
    {
        // Get visitor statistics
        $stats = [
            'total_visitors' => Visitor::count(),
            'total_visits' => Visit::count(),
            'pending_visits' => Visit::where('status', 'pending')->count(),
            'approved_visits' => Visit::where('status', 'approved')->count(),
            'completed_visits' => Visit::where('status', 'completed')->count(),
            'cancelled_visits' => Visit::where('status', 'cancelled')->count(),
            'visits_today' => Visit::whereDate('schedule_time', today())->count(),
            'active_visits' => Visit::where('status', 'approved')
                ->whereDate('schedule_time', today())
                ->count(),
        ];

        // Get recent visits
        $recentVisits = Visit::with(['visitor', 'type', 'meetingUser'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get today's visits
        $todayVisits = Visit::with(['visitor', 'type', 'meetingUser'])
            ->whereDate('schedule_time', today())
            ->orderBy('schedule_time', 'asc')
            ->get();

        // Get pending visits
        $pendingVisits = Visit::with(['visitor', 'type', 'meetingUser'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('vms.backend.staff.views.dashboard', compact(
            'stats',
            'recentVisits',
            'todayVisits',
            'pendingVisits'
        ));
    }
}
