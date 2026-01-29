@extends('layouts.receptionist')

@section('title', 'Dashboard - UCB Bank')

@section('content')
    <!-- Header -->
    <div class="header-section">
        <div>
            <h3 class="fw-800 mb-1 text-white letter-spacing-1">
                {{ auth()->user()->hasRole('receptionist') ? 'Receptionist Dashboard' : (auth()->user()->hasRole('staff') ? 'Staff Dashboard' : 'Visitor Dashboard') }}
            </h3>
            <p class="sub-label mb-0">Welcome back, {{ Auth::user()->name }}</p>
        </div>
        <div class="header-profile-box glass-card">
            <div class="avatar bg-primary">
                <i class="fas fa-user-tie text-white small"></i>
            </div>
            <div>
                <p class="small fw-800 mb-0 text-white">{{ Auth::user()->name }}</p>
                <span class="sub-label fs-9">
                    {{ ucfirst(auth()->user()->getRoleNames()->first()) ?? 'User' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Insurance Stats Row -->
    @if(isset($insuranceStats) && ($insuranceStats['total_policies'] > 0 || $insuranceStats['total_claims'] > 0))
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="glass-card summary-card">
                <div>
                    <span class="sub-label d-block mb-1">Total Policies</span>
                    <h2>{{ $insuranceStats['total_policies'] }}</h2>
                </div>
                <div class="summary-icon text-success" style="background: rgba(11, 214, 150, 0.1);">
                    <i class="fas fa-file-contract"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="glass-card summary-card">
                <div>
                    <span class="sub-label d-block mb-1">Active Policies</span>
                    <h2>{{ $insuranceStats['active_policies'] }}</h2>
                </div>
                <div class="summary-icon text-primary" style="background: rgba(59, 130, 246, 0.1);">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="glass-card summary-card">
                <div>
                    <span class="sub-label d-block mb-1">Total Claims</span>
                    <h2>{{ $insuranceStats['total_claims'] }}</h2>
                </div>
                <div class="summary-icon text-warning" style="background: rgba(255, 193, 7, 0.1);">
                    <i class="fas fa-clipboard-list"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="glass-card summary-card">
                <div>
                    <span class="sub-label d-block mb-1">Pending Claims</span>
                    <h2>{{ $insuranceStats['pending_claims'] }}</h2>
                </div>
                <div class="summary-icon text-info" style="background: rgba(13, 202, 240, 0.1);">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Stats Row - Based on permissions -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl">
            <div class="glass-card summary-card">
                <div>
                    <span class="sub-label d-block mb-1">Total Visitors</span>
                    <h2>{{ $stats['total_visitors'] }}</h2>
                </div>
                <div class="summary-icon"><i class="fas fa-users"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="glass-card summary-card">
                <div>
                    <span class="sub-label d-block mb-1">Today's Visits</span>
                    <h2>{{ $stats['visits_today'] }}</h2>
                </div>
                <div class="summary-icon"><i class="fas fa-calendar-day"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="glass-card summary-card">
                <div>
                    <span class="sub-label d-block mb-1">Pending</span>
                    <h2>{{ $stats['pending_visits'] }}</h2>
                </div>
                <div class="summary-icon text-warning" style="background:rgba(255,193,7,0.1)"><i class="fas fa-clock"></i></div>
            </div>
        </div>
        <div class="col-6 col-xl">
            <div class="glass-card summary-card">
                <div>
                    <span class="sub-label d-block mb-1">Active Visits</span>
                    <h2>{{ $stats['active_visits'] }}</h2>
                </div>
                <div class="summary-icon text-success" style="background:rgba(34,197,94,0.1)"><i class="fas fa-user-check"></i></div>
            </div>
        </div>
        <div class="col-12 col-xl">
            @can('create visitors')
            <a href="{{ route('visitor.create') }}" class="glass-card summary-card justify-content-center cursor-pointer border-dashed text-decoration-none" style="border-width: 2px;">
                <div class="d-flex align-items-center gap-2 text-white">
                    <i class="fas fa-plus"></i>
                    <span class="fw-bold text-uppercase fs-9">Register New Visitor</span>
                </div>
            </a>
            @else
            <div class="glass-card summary-card justify-content-center border-dashed" style="border-width: 2px;">
                <div class="d-flex align-items-center gap-2 text-white">
                    <i class="fas fa-lock"></i>
                    <span class="fw-bold text-uppercase fs-9">No Permission to Register Visitors</span>
                </div>
            </div>
            @endcan
        </div>
    </div>

    <!-- My Policies Section -->
    @if(isset($userOrders) && $userOrders->count() > 0)
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-800 sub-label mb-0">My Insurance Policies</h6>
                    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-success">
                        <i class="fas fa-external-link-alt me-1"></i>View All
                    </a>
                </div>
                <div class="row g-3">
                    @foreach($userOrders as $order)
                    <div class="col-md-6 col-lg-4">
                        <div class="policy-mini-card p-3 rounded-3" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-{{ $order->status == 'active' ? 'success' : ($order->status == 'pending' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                                <small class="text-muted">{{ $order->created_at->format('M j, Y') }}</small>
                            </div>
                            <h6 class="fw-bold text-white mb-1" style="font-family: 'Courier New', monospace; font-size: 0.85rem;">
                                {{ $order->policy_number }}
                            </h6>
                            <p class="text-success small mb-2">
                                <i class="fas fa-shield-alt me-1"></i>
                                {{ $order->package->name ?? 'N/A' }}
                            </p>
                            <div class="d-flex justify-content-between small">
                                <span class="text-muted">Coverage</span>
                                <span class="fw-bold">${{ number_format($order->package->coverage_amount ?? 0, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between small mt-1">
                                <span class="text-muted">Premium</span>
                                <span class="fw-bold">${{ number_format($order->package->price ?? 0, 2) }}</span>
                            </div>
                            <div class="mt-2">
                                <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-outline-success w-100">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- My Claims Section -->
    @if(isset($userClaims) && $userClaims->count() > 0)
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-800 sub-label mb-0">My Insurance Claims</h6>
                    <a href="{{ route('claims.index') }}" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-external-link-alt me-1"></i>View All
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Claim #</th>
                                <th>Package</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($userClaims as $claim)
                            <tr>
                                <td>
                                    <span class="fw-bold text-white" style="font-family: 'Courier New', monospace;">
                                        {{ $claim->claim_number }}
                                    </span>
                                </td>
                                <td class="small text-success">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    {{ $claim->package->name ?? 'N/A' }}
                                </td>
                                <td class="fw-bold">${{ number_format($claim->claim_amount, 2) }}</td>
                                <td>
                                    @switch($claim->status)
                                        @case('submitted')
                                            <span class="badge bg-info">Submitted</span>
                                            @break
                                        @case('under_review')
                                            <span class="badge bg-warning text-dark">Under Review</span>
                                            @break
                                        @case('approved')
                                            <span class="badge bg-success">Approved</span>
                                            @break
                                        @case('rejected')
                                            <span class="badge bg-danger">Rejected</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ ucfirst($claim->status) }}</span>
                                    @endswitch
                                </td>
                                <td class="small">{{ $claim->created_at->format('M j, Y') }}</td>
                                <td>
                                    <a href="{{ route('claims.show', $claim->id) }}" class="btn btn-circle btn-sm text-info" title="View Details">
                                        <i class="fas fa-eye small"></i>
                                    </a>
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

   
@endsection
