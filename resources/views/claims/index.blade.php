@extends('layouts.receptionist')

@section('title', 'My Claims - Pragati AI')

@section('content')
<div class="container py-5">
    <!-- Header -->
    <div class="text-center mb-5">
        <div class="glass-card d-inline-block px-5 py-4 mb-4">
            <h1 class="fw-bold text-white mb-2">
                <i class="fas fa-clipboard-list me-2" style="color: var(--accent-emerald);"></i>
                My Insurance Claims
            </h1>
            <p class="text-muted mb-0">Track and manage your insurance claims</p>
        </div>
    </div>

    @if($claims->isEmpty())
        <div class="glass-card p-5 text-center mx-auto" style="max-width: 500px;">
            <div class="text-muted mb-4">
                <i class="fas fa-clipboard fa-5x" style="color: var(--accent-emerald);"></i>
            </div>
            <h3 class="fw-bold text-white mb-3">No Claims Yet</h3>
            <p class="text-muted mb-4">You haven't filed any insurance claims yet.</p>
            <a href="{{ route('orders.index') }}" class="btn btn-gradient btn-lg">
                <i class="fas fa-file-contract me-2"></i>View Your Policies
            </a>
        </div>
    @else
        <div class="row g-4">
            @foreach($claims as $claim)
                <div class="col-md-6 col-lg-4">
                    <div class="glass-card h-100 claim-card">
                        <div class="card-body p-4">
                            <!-- Claim Status -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
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
                                <small class="text-muted">{{ $claim->created_at->format('M d, Y') }}</small>
                            </div>

                            <!-- Claim Number -->
                            <h5 class="fw-bold text-white mb-2 claim-number">{{ $claim->claim_number }}</h5>

                            <!-- Package Name -->
                            <h6 class="mb-3 text-dark" style="color: var(--accent-emerald);">
                                <i class="fas fa-shield-alt me-1 "></i>
                                {{ $claim->package->name }}
                            </h6>

                            <!-- Claim Details -->
                            <div class="claim-details mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Claim Amount</span>
                                    <span class="fw-bold text-white" style="color: var(--accent-emerald);">${{ number_format($claim->claim_amount, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Policy</span>
                                    <span class="fw-bold">{{ $claim->order->policy_number }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Submitted</span>
                                    <span class="fw-bold">{{ $claim->created_at->format('M d, Y') }}</span>
                                </div>
                            </div>

                            <!-- Reason Preview -->
                            <div class="reason-preview mb-3">
                                <p class="text-muted small mb-1">Reason</p>
                                <p class="mb-0 small text-white">{{ Str::limit($claim->reason, 80) }}</p>
                            </div>

                            <!-- View Details Button -->
                            <a href="{{ route('claims.show', $claim->id) }}" class="btn btn-outline w-100 bg-dark" style="border: 2px solid var(--accent-emerald); color: var(--accent-emerald); border-radius: 10px; padding: 12px 24px; font-weight: 600;">
                                <i class="fas fa-eye me-2"></i>View Details
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- View Policies -->
        <div class="text-center mt-5">
            <a href="{{ route('orders.index') }}" class="btn btn-gradient btn-lg">
                <i class="fas fa-file-contract me-2"></i>View All Policies
            </a>
        </div>
    @endif
</div>

<style>
    .claim-card { transition: all 0.3s ease; }
    .claim-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15) !important; }
    .claim-number { letter-spacing: 1px; font-family: 'Courier New', monospace; font-size: 0.9rem; }
    .claim-details { background: rgba(255, 255, 255, 0.1); padding: 12px; border-radius: 10px; }
    .reason-preview { background: rgba(255, 255, 255, 0.1); padding: 10px; border-radius: 8px; }
</style>
