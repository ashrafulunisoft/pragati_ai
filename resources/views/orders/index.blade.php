@extends('layouts.receptionist')

@section('title', 'My Policies - Pragati AI')

@section('content')
<div class="container py-5">
    <!-- Header -->
    <div class="text-center mb-5">
        <div class="glass-card d-inline-block px-5 py-4 mb-4">
            <h1 class="fw-bold text-dark mb-2">
                <i class="fas fa-file-contract text-success me-2"></i>
                My Insurance Policies
            </h1>
            <p class="text-muted mb-0">View and manage your insurance policies</p>
        </div>
    </div>

    @if($orders->isEmpty())
        <div class="glass-card p-5 text-center mx-auto" style="max-width: 500px;">
            <div class="text-muted mb-4">
                <i class="fas fa-folder-open fa-5x" style="color: #0bd696;"></i>
            </div>
            <h3 class="fw-bold text-dark mb-3">No Policies Found</h3>
            <p class="text-muted mb-4">You haven't purchased any insurance policies yet.</p>
            <a href="{{ route('packages.index') }}" class="btn btn-success btn-lg">
                <i class="fas fa-shopping-cart me-2"></i>Browse Packages
            </a>
        </div>
    @else
        <div class="row g-4">
            @foreach($orders as $order)
                <div class="col-md-6 col-lg-4">
                    <div class="glass-card h-100 policy-card">
                        <div class="card-body p-4">
                            <!-- Policy Status -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                @switch($order->status)
                                    @case('active')
                                        <span class="badge bg-success">Active</span>
                                        @break
                                    @case('pending')
                                        <span class="badge bg-warning text-dark">Pending</span>
                                        @break
                                    @case('expired')
                                        <span class="badge bg-danger">Expired</span>
                                        @break
                                    @case('cancelled')
                                        <span class="badge bg-secondary">Cancelled</span>
                                        @break
                                    @default
                                        <span class="badge bg-info">{{ ucfirst($order->status) }}</span>
                                @endswitch
                                <small class="text-muted">{{ $order->created_at->format('M d, Y') }}</small>
                            </div>

                            <!-- Policy Number -->
                            <h5 class="fw-bold text-dark mb-2 policy-number">{{ $order->policy_number }}</h5>

                            <!-- Package Name -->
                            <h6 class="text-success mb-3">
                                <i class="fas fa-shield-alt me-1"></i>
                                {{ $order->package->name }}
                            </h6>

                            <!-- Policy Details -->
                            <div class="policy-details mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Coverage</span>
                                    <span class="fw-bold">${{ number_format($order->package->coverage_amount, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Premium</span>
                                    <span class="fw-bold">${{ number_format($order->package->price, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Duration</span>
                                    <span class="fw-bold">{{ $order->package->duration_months }} Months</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Valid Until</span>
                                    <span class="fw-bold {{ $order->end_date->isPast() ? 'text-danger' : '' }}">
                                        {{ $order->end_date->format('M d, Y') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            @php
                                $totalDays = $order->start_date->diffInDays($order->end_date);
                                $elapsedDays = $order->start_date->diffInDays(now());
                                $progress = min(100, max(0, ($elapsedDays / $totalDays) * 100));
                            @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Coverage Used</span>
                                    <span class="text-muted">{{ floor($progress) }}%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progress }}%"></div>
                                </div>
                            </div>

                            <!-- View Details Button -->
                            <a href="{{ route('orders.show', $order->id) }}" class="btn btn-outline-success w-100">
                                <i class="fas fa-eye me-2"></i>View Policy
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Browse More -->
        <div class="text-center mt-5">
            <a href="{{ route('packages.index') }}" class="btn btn-success btn-lg">
                <i class="fas fa-plus-circle me-2"></i>Purchase Another Policy
            </a>
        </div>
    @endif
</div>

<style>
    .policy-card {
        transition: all 0.3s ease;
    }

    .policy-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15) !important;
    }

    .policy-number {
        letter-spacing: 1px;
        font-family: 'Courier New', monospace;
        font-size: 1rem;
    }

    .policy-details {
        background: rgba(255, 255, 255, 0.3);
        padding: 12px;
        border-radius: 10px;
    }

    .progress {
        background: rgba(0, 0, 0, 0.1);
        border-radius: 10px;
    }

    .btn-success {
        background: linear-gradient(135deg, #0bd696 0%, #09a87e 100%);
        border: none;
        border-radius: 10px;
        padding: 12px 24px;
        font-weight: 600;
    }

    .btn-success:hover {
        background: linear-gradient(135deg, #09a87e 0%, #088a6a 100%);
        color: #fff;
    }

    .btn-outline-success {
        border: 2px solid #0bd696;
        color: #0bd696;
        border-radius: 10px;
        padding: 12px 24px;
        font-weight: 600;
    }

    .btn-outline-success:hover {
        background: #0bd696;
        color: #fff;
    }

    .badge {
        font-size: 0.75rem;
        padding: 5px 10px;
        border-radius: 20px;
    }
</style>
