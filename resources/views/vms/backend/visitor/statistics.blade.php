@extends('layouts.receptionist')

@section('title', 'Visitor Statistics - UCB Bank')

@section('content')
<!-- Header -->
<div class="header-section">
    <div>
        <h3 class="fw-800 mb-1 text-white letter-spacing-1">Visitor Statistics</h3>
        <p class="sub-label mb-0">Overview of visitor management metrics</p>
    </div>
    <a href="{{ route('visitor.index') }}" class="btn btn-outline d-flex align-items-center gap-2">
        <i class="fas fa-arrow-left"></i>
        <span>Back to List</span>
    </a>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <!-- Total Visitors -->
    <div class="col-md-3 col-6">
        <div class="glass-card p-4 h-100" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.05) 100%);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1 text-white-50 small fw-800">Total Visitors</p>
                    <h2 class="fw-800 mb-0 text-white">{{ $stats['total_visitors'] }}</h2>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(59, 130, 246, 0.2);">
                    <i class="fas fa-users text-info" style="font-size: 1.2rem;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Visits -->
    <div class="col-md-3 col-6">
        <div class="glass-card p-4 h-100" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1 text-white-50 small fw-800">Total Visits</p>
                    <h2 class="fw-800 mb-0 text-white">{{ $stats['total_visits'] }}</h2>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(16, 185, 129, 0.2);">
                    <i class="fas fa-calendar-check text-success" style="font-size: 1.2rem;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- This Month -->
    <div class="col-md-3 col-6">
        <div class="glass-card p-4 h-100" style="background: linear-gradient(135deg, rgba(168, 85, 247, 0.1) 0%, rgba(168, 85, 247, 0.05) 100%);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1 text-white-50 small fw-800">This Month</p>
                    <h2 class="fw-800 mb-0 text-white">{{ $stats['visits_this_month'] }}</h2>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(168, 85, 247, 0.2);">
                    <i class="fas fa-chart-line text-purple" style="font-size: 1.2rem;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Completion Rate -->
    <div class="col-md-3 col-6">
        <div class="glass-card p-4 h-100" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%);">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <p class="mb-1 text-white-50 small fw-800">Completion Rate</p>
                    <h2 class="fw-800 mb-0 text-white">{{ $stats['total_visits'] > 0 ? round(($stats['completed_visits'] / $stats['total_visits']) * 100, 1) : 0 }}%</h2>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background: rgba(245, 158, 11, 0.2);">
                    <i class="fas fa-chart-pie text-warning" style="font-size: 1.2rem;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Breakdown -->
<div class="glass-card p-4 mb-4">
    <h5 class="fw-800 mb-4 text-white">Visit Status Breakdown</h5>
    <div class="row g-4">
        <!-- Pending -->
        <div class="col-md-3 col-6">
            <div class="p-4 text-center" style="background: rgba(245, 158, 11, 0.1); border-radius: 12px; border: 1px solid rgba(245, 158, 11, 0.3);">
                <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px; background: rgba(245, 158, 11, 0.2);">
                    <i class="fas fa-clock text-warning" style="font-size: 1.5rem;"></i>
                </div>
                <h3 class="fw-800 mb-1 text-white">{{ $stats['pending_visits'] }}</h3>
                <p class="mb-0 text-white-50 small fw-800">Pending</p>
            </div>
        </div>

        <!-- Approved -->
        <div class="col-md-3 col-6">
            <div class="p-4 text-center" style="background: rgba(16, 185, 129, 0.1); border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.3);">
                <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px; background: rgba(16, 185, 129, 0.2);">
                    <i class="fas fa-check-circle text-success" style="font-size: 1.5rem;"></i>
                </div>
                <h3 class="fw-800 mb-1 text-white">{{ $stats['approved_visits'] }}</h3>
                <p class="mb-0 text-white-50 small fw-800">Approved</p>
            </div>
        </div>

        <!-- Completed -->
        <div class="col-md-3 col-6">
            <div class="p-4 text-center" style="background: rgba(59, 130, 246, 0.1); border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.3);">
                <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px; background: rgba(59, 130, 246, 0.2);">
                    <i class="fas fa-check-double text-info" style="font-size: 1.5rem;"></i>
                </div>
                <h3 class="fw-800 mb-1 text-white">{{ $stats['completed_visits'] }}</h3>
                <p class="mb-0 text-white-50 small fw-800">Completed</p>
            </div>
        </div>

        <!-- Cancelled -->
        <div class="col-md-3 col-6">
            <div class="p-4 text-center" style="background: rgba(239, 68, 68, 0.1); border-radius: 12px; border: 1px solid rgba(239, 68, 68, 0.3);">
                <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px; background: rgba(239, 68, 68, 0.2);">
                    <i class="fas fa-times-circle text-danger" style="font-size: 1.5rem;"></i>
                </div>
                <h3 class="fw-800 mb-1 text-white">{{ $stats['cancelled_visits'] }}</h3>
                <p class="mb-0 text-white-50 small fw-800">Cancelled</p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="glass-card p-4">
    <h5 class="fw-800 mb-4 text-white">Quick Actions</h5>
    <div class="row g-3">
        <div class="col-md-4 col-6">
            <a href="{{ route('visitor.create') }}" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 py-3">
                <i class="fas fa-plus"></i>
                <span>Register Visitor</span>
            </a>
        </div>
        <div class="col-md-4 col-6">
            <a href="{{ route('visitor.index') }}" class="btn btn-outline w-100 d-flex align-items-center justify-content-center gap-2 py-3">
                <i class="fas fa-list"></i>
                <span>View All Visitors</span>
            </a>
        </div>
        <div class="col-md-4 col-6">
            <a href="{{ route('visitor.index', ['status' => 'pending']) }}" class="btn btn-outline w-100 d-flex align-items-center justify-content-center gap-2 py-3" style="border-color: rgba(245, 158, 11, 0.5);">
                <i class="fas fa-clock text-warning"></i>
                <span>Pending Visits</span>
            </a>
        </div>
    </div>
</div>
@endsection
