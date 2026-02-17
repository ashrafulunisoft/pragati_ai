@extends('layouts.receptionist')

@section('title', 'Claim Details - ' . $claim->claim_number)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Back Button -->
            <div class="mb-4">
                <a href="{{ route('claims.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Claims
                </a>
            </div>

            <!-- Claim Card -->
            <div class="glass-card claim-card">
                <div class="card-header 
                    @switch($claim->status)
                        @case('submitted') bg-info @break
                        @case('under_review') bg-warning @break
                        @case('approved') bg-success @break
                        @case('rejected') bg-danger @break
                        @default bg-secondary
                    @endswitch
                    text-white py-3">
                    <div class="d-flex justify-content-between align-items-center px-4">
                        <h5 class="mb-0">
                            <i class="fas fa-clipboard me-2"></i>
                            Insurance Claim
                        </h5>
                        <span class="badge bg-light 
                            @switch($claim->status)
                                @case('submitted') text-info @break
                                @case('under_review') text-warning @break
                                @case('approved') text-success @break
                                @case('rejected') text-danger @break
                                @default text-secondary
                            @endswitch
                            px-3 py-2">
                            @switch($claim->status)
                                @case('submitted')<i class="fas fa-check-circle me-1"></i>Submitted @break
                                @case('under_review')<i class="fas fa-search me-1"></i>Under Review @break
                                @case('approved')<i class="fas fa-check-circle me-1"></i>Approved @break
                                @case('rejected')<i class="fas fa-times-circle me-1"></i>Rejected @break
                            @endswitch
                        </span>
                    </div>
                </div>
                <div class="card-body p-5">
                    <!-- Claim Number -->
                    <div class="text-center mb-5">
                        <p class="text-muted small text-uppercase mb-1">Claim Number</p>
                        <h3 class="fw-bold text-dark claim-number">{{ $claim->claim_number }}</h3>
                    </div>

                    <!-- Claim Details Grid -->
                    <div class="row g-4 mb-5">
                        <div class="col-md-6">
                            <div class="detail-box p-3 rounded-3">
                                <p class="text-muted small mb-1">Package</p>
                                <h6 class="fw-bold text-dark mb-0">{{ $claim->package->name }}</h6>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-box p-3 rounded-3">
                                <p class="text-muted small mb-1">Claim Amount</p>
                                <h6 class="fw-bold text-success mb-0">${{ number_format($claim->claim_amount, 2) }}</h6>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-box p-3 rounded-3">
                                <p class="text-muted small mb-1">Policy Number</p>
                                <h6 class="fw-bold text-dark mb-0">{{ $claim->order->policy_number }}</h6>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-box p-3 rounded-3">
                                <p class="text-muted small mb-1">Coverage Limit</p>
                                <h6 class="fw-bold text-dark mb-0">${{ number_format($claim->package->coverage_amount, 2) }}</h6>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-box p-3 rounded-3">
                                <p class="text-muted small mb-1">Submitted On</p>
                                <h6 class="fw-bold text-dark mb-0">{{ $claim->created_at->format('M d, Y') }}</h6>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-box p-3 rounded-3">
                                <p class="text-muted small mb-1">Status</p>
                                <h6 class="fw-bold mb-0 text-capitalize">
                                    @switch($claim->status)
                                        @case('submitted') <span class="text-info">Submitted</span> @break
                                        @case('under_review') <span class="text-warning">Under Review</span> @break
                                        @case('approved') <span class="text-success">Approved</span> @break
                                        @case('rejected') <span class="text-danger">Rejected</span> @break
                                    @endswitch
                                </h6>
                            </div>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="reason-section mb-5">
                        <h6 class="fw-bold text-dark mb-3">Claim Reason</h6>
                        <div class="reason-box p-4 rounded-3 bg-light">
                            <p class="mb-0 text-dark">{{ $claim->reason }}</p>
                        </div>
                    </div>

                    <!-- Status Timeline -->
                    <div class="status-timeline mb-5">
                        <h6 class="fw-bold text-dark mb-3">Claim Timeline</h6>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-icon bg-success">
                                    <i class="fas fa-check text-white"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="fw-bold mb-0">Claim Submitted</h6>
                                    <p class="text-muted small mb-0">{{ $claim->created_at->format('M d, Y - h:i A') }}</p>
                                </div>
                            </div>
                            @if(in_array($claim->status, ['under_review', 'approved', 'rejected']))
                            <div class="timeline-item">
                                <div class="timeline-icon {{ $claim->status !== 'submitted' ? 'bg-warning' : 'bg-secondary' }}">
                                    <i class="fas fa-search text-white"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="fw-bold mb-0">Under Review</h6>
                                    <p class="text-muted small mb-0">Your claim is being reviewed</p>
                                </div>
                            </div>
                            @endif
                            @if(in_array($claim->status, ['approved', 'rejected']))
                            <div class="timeline-item">
                                <div class="timeline-icon {{ $claim->status === 'approved' ? 'bg-success' : 'bg-danger' }}">
                                    <i class="fas {{ $claim->status === 'approved' ? 'fa-check' : 'fa-times' }} text-white"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="fw-bold mb-0">{{ $claim->status === 'approved' ? 'Claim Approved' : 'Claim Rejected' }}</h6>
                                    <p class="text-muted small mb-0">Status updated to {{ $claim->status }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-grid gap-3">
                        <a href="{{ route('claims.index') }}" class="btn btn-outline-dark btn-lg py-3">
                            <i class="fas fa-list me-2"></i>
                            View All Claims
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .claim-card { border-radius: 24px; overflow: hidden; }
    .claim-number { letter-spacing: 2px; font-family: 'Courier New', monospace; }
    .detail-box { background: rgba(255, 255, 255, 0.5); border: 1px solid rgba(0, 0, 0, 0.1); }
    .reason-box { background: rgba(255, 255, 255, 0.7); border: 1px solid rgba(0, 0, 0, 0.1); }
    .timeline { position: relative; padding-left: 30px; }
    .timeline-item { position: relative; padding-bottom: 20px; border-left: 2px solid #e0e0e0; padding-left: 20px; margin-left: 10px; }
    .timeline-item:last-child { border-left: none; }
    .timeline-icon { position: absolute; left: -12px; top: 0; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; }
    .timeline-content { padding-left: 10px; }
    .btn-outline-dark { border: 2px solid #495057; color: #495057; border-radius: 12px; font-weight: 600; }
    .btn-outline-dark:hover { background: #495057; color: #fff; }
</style>
