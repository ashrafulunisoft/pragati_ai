@extends('layouts.admin')

@section('title', 'Admin Dashboard - UCB Bank')

@section('content')
    <!-- Header -->
    <div class="header-section">
        <div>
            <h3 class="fw-800 mb-1 text-white letter-spacing-1">Admin Dashboard</h3>
            <p class="sub-label mb-0">Welcome back, {{ Auth::user()->name }}</p>
        </div>
        <div class="header-profile-box glass-card">
            <div class="avatar bg-primary">
                <i class="fas fa-user-tie text-white small"></i>
            </div>
            <div>
                <p class="small fw-800 mb-0 text-white">{{ Auth::user()->name }}</p>
                <span class="sub-label fs-9">Administrator</span>
            </div>
        </div>
    </div>

    <!-- User & Policy Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="glass-card summary-card">
                <div>
                    <span class="sub-label d-block mb-1">Total Users</span>
                    <h2>{{ $stats['total_users'] }}</h2>
                </div>
                <div class="summary-icon"><i class="fas fa-users"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="glass-card summary-card">
                <div>
                    <span class="sub-label d-block mb-1">Active Policies</span>
                    <h2>{{ $stats['active_policies'] }}</h2>
                </div>
                <div class="summary-icon text-success" style="background:rgba(34,197,94,0.1)"><i class="fas fa-file-contract"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="glass-card summary-card">
                <div>
                    <span class="sub-label d-block mb-1">Pending Policies</span>
                    <h2>{{ $stats['pending_policies'] }}</h2>
                </div>
                <div class="summary-icon text-warning" style="background:rgba(255,193,7,0.1)"><i class="fas fa-clock"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="glass-card summary-card">
                <div>
                    <span class="sub-label d-block mb-1">Expired Policies</span>
                    <h2>{{ $stats['expired_policies'] }}</h2>
                </div>
                <div class="summary-icon text-danger" style="background:rgba(239,68,68,0.1)"><i class="fas fa-exclamation-circle"></i></div>
            </div>
        </div>
    </div>

    <!-- Visitor Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="glass-card summary-card">
                <div>
                    <span class="sub-label d-block mb-1">Total Visitors</span>
                    <h2>{{ $stats['total_visitors'] }}</h2>
                </div>
                <div class="summary-icon"><i class="fas fa-user-friends"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="glass-card summary-card">
                <div>
                    <span class="sub-label d-block mb-1">Today's Visits</span>
                    <h2>{{ $stats['visits_today'] }}</h2>
                </div>
                <div class="summary-icon"><i class="fas fa-calendar-day"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="glass-card summary-card">
                <div>
                    <span class="sub-label d-block mb-1">Pending</span>
                    <h2>{{ $stats['pending_visits'] }}</h2>
                </div>
                <div class="summary-icon text-warning" style="background:rgba(255,193,7,0.1)"><i class="fas fa-clock"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="glass-card summary-card">
                <div>
                    <span class="sub-label d-block mb-1">Active Visits</span>
                    <h2>{{ $stats['checked_in_visits'] }}</h2>
                </div>
                <div class="summary-icon text-success" style="background:rgba(34,197,94,0.1)"><i class="fas fa-user-check"></i></div>
            </div>
        </div>
    </div>

    <!-- Claims Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <a href="{{ route('admin.claims.index') }}" class="text-decoration-none">
                <div class="glass-card summary-card text-decoration-none">
                    <div>
                        <span class="sub-label d-block mb-1 text-white">Total Claims</span>
                        <h2 class="text-white">{{ $stats['total_claims'] }}</h2>
                    </div>
                    <div class="summary-icon"><i class="fas fa-clipboard-list"></i></div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a href="{{ route('admin.claims.index') }}" class="text-decoration-none">
                <div class="glass-card summary-card">
                    <div>
                        <span class="sub-label d-block mb-1 text-white">Pending Claims</span>
                        <h2 class="text-warning">{{ $stats['pending_claims'] }}</h2>
                    </div>
                    <div class="summary-icon text-warning" style="background:rgba(255,193,7,0.1)"><i class="fas fa-hourglass-half"></i></div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a href="{{ route('admin.claims.index') }}" class="text-decoration-none">
                <div class="glass-card summary-card">
                    <div>
                        <span class="sub-label d-block mb-1 text-white">Approved Claims</span>
                        <h2 class="text-success">{{ $stats['approved_claims'] }}</h2>
                    </div>
                    <div class="summary-icon text-success" style="background:rgba(34,197,94,0.1)"><i class="fas fa-check-circle"></i></div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <div class="glass-card summary-card justify-content-center cursor-pointer border-dashed" style="border-width: 2px;">
                <a href="{{ route('admin.visitor.registration.create') }}" class="d-flex align-items-center gap-2 text-white text-decoration-none">
                    <i class="fas fa-plus"></i>
                    <span class="fw-bold text-uppercase fs-9">Register New Visitor</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Links Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="{{ route('admin.policies.index') }}" class="glass-card p-4 d-flex align-items-center gap-3 text-decoration-none" style="transition: 0.3s;">
                <div class="summary-icon text-info" style="background:rgba(59,130,246,0.2); width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-file-contract" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <h6 class="fw-800 text-white mb-0">View All Policies</h6>
                    <span class="sub-label">{{ $stats['total_policies'] }} total</span>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('admin.claims.index') }}" class="glass-card p-4 d-flex align-items-center gap-3 text-decoration-none" style="transition: 0.3s;">
                <div class="summary-icon text-warning" style="background:rgba(234,179,8,0.2); width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-clipboard-list" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <h6 class="fw-800 text-white mb-0">View All Claims</h6>
                    <span class="sub-label">{{ $stats['pending_claims'] }} pending</span>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('insurance-packages.index') }}" class="glass-card p-4 d-flex align-items-center gap-3 text-decoration-none" style="transition: 0.3s;">
                <div class="summary-icon text-success" style="background:rgba(34,197,94,0.2); width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-box" style="font-size: 1.5rem;"></i>
                </div>
                <div>
                    <h6 class="fw-800 text-white mb-0">Insurance Packages</h6>
                    <span class="sub-label">Manage packages</span>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('admin.live.dashboard') }}" class="glass-card p-4 d-flex align-items-center gap-3 text-decoration-none" style="transition: 0.3s;">
                <div class="summary-icon" style="background:rgba(139,92,246,0.2); width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-broadcast-tower" style="font-size: 1.5rem; color: #8b5cf6;"></i>
                </div>
                <div>
                    <h6 class="fw-800 text-white mb-0">Live Dashboard</h6>
                    <span class="sub-label">Real-time view</span>
                </div>
            </a>
        </div>
    </div>

    <!-- Recent Policies & Claims Row -->
    <div class="row g-4 mb-4">
        <!-- Recent Policies -->
        <div class="col-lg-6">
            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-800 sub-label mb-0">Recent Policies</h6>
                    <a href="{{ route('admin.policies.index') }}" class="text-info small text-decoration-none">View All <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
                <div class="table-responsive log-container">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Policy #</th>
                                <th>Customer</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $recentPolicies = \App\Models\pragati\Order::with('user')->latest()->limit(5)->get();
                            @endphp
                            @forelse($recentPolicies as $policy)
                            <tr>
                                <td>
                                    <span class="fw-800 small text-info">{{ $policy->policy_number }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                            <span class="fw-800 small" style="font-size: 0.7rem;">{{ substr($policy->user->name, 0, 1) }}</span>
                                        </div>
                                        <span class="small">{{ $policy->user->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $policyStatusClass = match($policy->status) {
                                            'active' => 'bg-success bg-opacity-10 text-success',
                                            'pending' => 'bg-warning bg-opacity-10 text-warning',
                                            'expired' => 'bg-danger bg-opacity-10 text-danger',
                                            default => 'bg-secondary bg-opacity-10 text-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $policyStatusClass }}" style="font-size: 0.65rem; padding: 0.25rem 0.5rem; font-weight: 800;">
                                        {{ strtoupper($policy->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-white-50 py-4">
                                    <i class="fas fa-folder-open mb-2" style="font-size: 1.5rem; opacity: 0.5;"></i>
                                    <p class="mb-0 small">No policies yet</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Claims -->
        <div class="col-lg-6">
            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-800 sub-label mb-0">Recent Claims</h6>
                    <a href="{{ route('admin.claims.index') }}" class="text-info small text-decoration-none">View All <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
                <div class="table-responsive log-container">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Claim #</th>
                                <th>Customer</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $recentClaims = \App\Models\pragati\Claim::with('user')->latest()->limit(5)->get();
                            @endphp
                            @forelse($recentClaims as $claim)
                            <tr>
                                <td>
                                    <span class="fw-800 small text-warning">{{ $claim->claim_number }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                            <span class="fw-800 small" style="font-size: 0.7rem;">{{ substr($claim->user->name, 0, 1) }}</span>
                                        </div>
                                        <span class="small">{{ $claim->user->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $claimStatusClass = match($claim->status) {
                                            'approved' => 'bg-success bg-opacity-10 text-success',
                                            'pending' => 'bg-warning bg-opacity-10 text-warning',
                                            'rejected' => 'bg-danger bg-opacity-10 text-danger',
                                            default => 'bg-secondary bg-opacity-10 text-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $claimStatusClass }}" style="font-size: 0.65rem; padding: 0.25rem 0.5rem; font-weight: 800;">
                                        {{ strtoupper($claim->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-white-50 py-4">
                                    <i class="fas fa-clipboard mb-2" style="font-size: 1.5rem; opacity: 0.5;"></i>
                                    <p class="mb-0 small">No claims yet</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Visits -->
    @if($todayVisits->count() > 0)
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="glass-card p-4">
                <h6 class="fw-800 sub-label mb-4">Today's Visits</h6>
                <div class="table-responsive log-container">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Visitor</th>
                                <th>Host</th>
                                <th>Visit Type</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($todayVisits as $visit)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <span class="fw-800 small">{{ substr($visit->visitor->name, 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <span class="small fw-800 d-block">{{ $visit->visitor->name }}</span>
                                            <span class="fs-9 text-white-50">{{ $visit->visitor->phone ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="small">{{ $visit->meetingUser->name }}</td>
                                <td class="small">{{ $visit->type->name ?? 'N/A' }}</td>
                                <td class="small">{{ \Carbon\Carbon::parse($visit->schedule_time)->format('g:i A') }}</td>
                                <td>
                                    @if($visit->status == 'approved')
                                        <span class="status-badge text-success">Active</span>
                                    @elseif($visit->status == 'pending_host')
                                        <span class="status-badge text-warning">Pending</span>
                                    @elseif($visit->status == 'completed')
                                        <span class="status-badge">Completed</span>
                                    @elseif($visit->status == 'checked_in')
                                        <span class="status-badge text-success">Checked In</span>
                                    @elseif($visit->status == 'rejected')
                                        <span class="status-badge text-danger">Rejected</span>
                                    @else
                                        <span class="status-badge">{{ ucfirst($visit->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.visitor.show', $visit->id) }}" class="btn btn-circle text-info" title="View Details">
                                            <i class="fas fa-eye small"></i>
                                        </a>
                                        <a href="{{ route('admin.visitor.edit', $visit->id) }}" class="btn btn-circle text-primary" title="Edit">
                                            <i class="fas fa-edit small"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Pending Visits -->
    @if($pendingVisits->count() > 0)
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="glass-card p-4">
                <h6 class="fw-800 sub-label mb-4">Pending Approvals</h6>
                <div class="table-responsive log-container">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Visitor</th>
                                <th>Purpose</th>
                                <th>Host</th>
                                <th>Requested</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingVisits as $visit)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <span class="fw-800 small">{{ substr($visit->visitor->name, 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <span class="small fw-800 d-block">{{ $visit->visitor->name }}</span>
                                            <span class="fs-9 text-white-50">{{ $visit->visitor->email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="small">{{ substr($visit->purpose, 0, 30) }}...</td>
                                <td class="small">{{ $visit->meetingUser->name }}</td>
                                <td class="small">{{ $visit->created_at->diffForHumans() }}</td>
                                <td>
                                    <span class="status-badge text-warning border-orange">Pending</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.visitor.show', $visit->id) }}" class="btn btn-circle text-info" title="View Details">
                                            <i class="fas fa-eye small"></i>
                                        </a>
                                        <a href="{{ route('admin.visitor.edit', $visit->id) }}" class="btn btn-circle text-primary" title="Edit/Approve">
                                            <i class="fas fa-edit small"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Visits -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="glass-card p-4">
                <h6 class="fw-800 sub-label mb-4">Recent Visits Log</h6>
                <div class="table-responsive log-container">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Visitor</th>
                                <th>Host</th>
                                <th>Purpose</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentVisits as $visit)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <span class="fw-800 small">{{ substr($visit->visitor->name, 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <span class="small fw-800 d-block">{{ $visit->visitor->name }}</span>
                                            <span class="fs-9 text-white-50">{{ $visit->visitor->email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="small">{{ $visit->meetingUser->name }}</td>
                                <td class="small">{{ substr($visit->purpose, 0, 25) }}...</td>
                                <td class="small">{{ \Carbon\Carbon::parse($visit->schedule_time)->format('M j, Y') }}</td>
                                <td>
                                    @if($visit->status == 'approved')
                                        <span class="status-badge text-success">Active</span>
                                    @elseif($visit->status == 'pending_host')
                                        <span class="status-badge text-warning">Pending</span>
                                    @elseif($visit->status == 'completed')
                                        <span class="status-badge">Completed</span>
                                    @elseif($visit->status == 'checked_in')
                                        <span class="status-badge text-success">Checked In</span>
                                    @elseif($visit->status == 'rejected')
                                        <span class="status-badge text-danger">Rejected</span>
                                    @else
                                        <span class="status-badge">{{ ucfirst($visit->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.visitor.show', $visit->id) }}" class="btn btn-circle text-info" title="View Details">
                                            <i class="fas fa-eye small"></i>
                                        </a>
                                        <a href="{{ route('admin.visitor.edit', $visit->id) }}" class="btn btn-circle text-primary" title="Edit">
                                            <i class="fas fa-edit small"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
