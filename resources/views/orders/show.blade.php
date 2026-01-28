@extends('layouts.receptionist')

@section('title', 'Policy Details - ' . $order->policy_number)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Success Alert -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Policy Card -->
            <div class="glass-card policy-card">
                <div class="card-header bg-success text-white py-3">
                    <div class="d-flex justify-content-between align-items-center px-4">
                        <h5 class="mb-0">
                            <i class="fas fa-file-contract me-2"></i>
                            Insurance Policy
                        </h5>
                        <span class="badge bg-light text-success px-3 py-2">
                            <i class="fas fa-check-circle me-1"></i>
                            Active
                        </span>
                    </div>
                </div>
                <div class="card-body p-5">
                    <!-- Policy Number -->
                    <div class="text-center mb-5">
                        <p class="text-muted small text-uppercase mb-1">Policy Number</p>
                        <h3 class="fw-bold text-dark policy-number">{{ $order->policy_number }}</h3>
                    </div>

                    <!-- Policy Details Grid -->
                    <div class="row g-4 mb-5">
                        <div class="col-md-6">
                            <div class="detail-box p-3 rounded-3">
                                <p class="text-muted small mb-1">Package</p>
                                <h6 class="fw-bold text-dark mb-0">{{ $order->package->name }}</h6>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-box p-3 rounded-3">
                                <p class="text-muted small mb-1">Coverage Amount</p>
                                <h6 class="fw-bold text-success mb-0">${{ number_format($order->package->coverage_amount, 2) }}</h6>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-box p-3 rounded-3">
                                <p class="text-muted small mb-1">Premium Paid</p>
                                <h6 class="fw-bold text-primary mb-0">${{ number_format($order->package->price, 2) }}</h6>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-box p-3 rounded-3">
                                <p class="text-muted small mb-1">Duration</p>
                                <h6 class="fw-bold text-dark mb-0">{{ $order->package->duration_months }} Months</h6>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-box p-3 rounded-3">
                                <p class="text-muted small mb-1">Start Date</p>
                                <h6 class="fw-bold text-dark mb-0">{{ $order->start_date->format('M d, Y') }}</h6>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-box p-3 rounded-3">
                                <p class="text-muted small mb-1">End Date</p>
                                <h6 class="fw-bold text-danger mb-0">{{ $order->end_date->format('M d, Y') }}</h6>
                            </div>
                        </div>
                    </div>

                    <!-- Coverage Period Progress -->
                    <div class="coverage-progress mb-5">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="small text-muted">Coverage Period</span>
                            <span class="small text-muted">
                                @php
                                    $totalDays = $order->start_date->diffInDays($order->end_date);
                                    $elapsedDays = $order->start_date->diffInDays(now());
                                    $progress = min(100, max(0, ($elapsedDays / $totalDays) * 100));
                                @endphp
                                {{ floor($progress) }}% Complete
                            </span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progress }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span class="small text-muted">{{ $order->start_date->format('M d, Y') }}</span>
                            <span class="small text-muted">{{ $order->end_date->format('M d, Y') }}</span>
                        </div>
                    </div>

                    <!-- Policy Features -->
                    <div class="policy-features mb-5">
                        <h6 class="fw-bold text-dark mb-3">Policy Benefits</h6>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="feature-item d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <span class="small">24/7 Support</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="feature-item d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <span class="small">Quick Claims</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="feature-item d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <span class="small">Cashless Treatment</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="feature-item d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <span class="small">Nationwide Coverage</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-grid gap-3">
                        @if($order->status === 'active')
                        <a href="{{ route('orders.claim.create', $order->id) }}" class="btn btn-warning btn-lg py-3">
                            <i class="fas fa-file-medical me-2"></i>
                            File a Claim
                        </a>
                        @endif
                        <a href="{{ route('orders.index') }}" class="btn btn-outline-dark btn-lg py-3">
                            <i class="fas fa-list me-2"></i>
                            View All Policies
                        </a>
                        <a href="{{ route('packages.index') }}" class="btn btn-success btn-lg py-3">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Buy Another Package
                        </a>
                        <a href="#" class="btn btn-outline-secondary btn-lg py-3" onclick="window.print(); return false;">
                            <i class="fas fa-print me-2"></i>
                            Print Policy
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="text-center mt-4">
                <p class="text-muted small mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    This is an electronically generated policy. For any queries, contact customer support.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    .policy-card {
        border-radius: 24px;
        overflow: hidden;
    }

    .policy-number {
        letter-spacing: 2px;
        font-family: 'Courier New', monospace;
    }

    .detail-box {
        background: rgba(255, 255, 255, 0.5);
        border: 1px solid rgba(0, 0, 0, 0.1);
    }

    .coverage-progress .progress {
        background: rgba(0, 0, 0, 0.1);
        border-radius: 10px;
    }

    .policy-features .feature-item {
        padding: 8px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 8px;
    }

    .btn-success {
        background: linear-gradient(135deg, #0bd696 0%, #09a87e 100%);
        border: none;
        border-radius: 12px;
        font-weight: 600;
    }

    .btn-success:hover {
        background: linear-gradient(135deg, #09a87e 0%, #088a6a 100%);
        color: #fff;
    }

    .btn-outline-dark {
        border: 2px solid #495057;
        color: #495057;
        border-radius: 12px;
        font-weight: 600;
    }

    .btn-outline-dark:hover {
        background: #495057;
        color: #fff;
    }

    .btn-outline-secondary {
        border: 2px solid #6c757d;
        color: #6c757d;
        border-radius: 12px;
        font-weight: 600;
    }

    .btn-outline-secondary:hover {
        background: #6c757d;
        color: #fff;
    }

    .btn-warning {
        background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
        border: none;
        color: #212529;
        border-radius: 12px;
        font-weight: 600;
    }

    .btn-warning:hover {
        background: linear-gradient(135deg, #e0a800 0%, #c69500 100%);
        color: #212529;
    }

    @media print {
        .btn, .alert {
            display: none !important;
        }
        .glass-card {
            box-shadow: none !important;
            border:1px solid #ddd !important;
        }
    }

    /* Mobile Responsive Styles */
    @media (max-width: 992px) {
        .container {
            max-width: 95%;
            padding: 0 1rem;
        }

        .policy-card {
            margin-bottom: 1rem;
        }
    }

    @media (max-width: 768px) {
        h3 {
            font-size: 1.5rem !important;
        }

        .card-body {
            padding: 2rem !important;
        }

        .detail-box {
            padding: 1.2rem;
        }

        .coverage-progress {
            margin-bottom: 3rem;
        }

        .policy-features {
            margin-bottom: 3rem;
        }

        .btn-lg {
            padding: 1rem;
            font-size: 1rem;
        }
    }

    @media (max-width: 576px) {
        .container {
            padding: 0 0.5rem;
        }

        .card-body {
            padding: 1.5rem !important;
        }

        .card-header {
            padding: 1rem !important;
        }

        .card-header h5 {
            font-size: 1rem !important;
        }

        .card-header .badge {
            font-size: 0.75rem;
            padding: 0.5rem 1rem;
        }

        h3 {
            font-size: 1.25rem !important;
            line-height: 1.3;
        }

        .detail-box {
            padding: 1rem;
            margin-bottom: 0.8rem;
        }

        .detail-box p {
            font-size: 0.75rem;
            margin-bottom: 0.3rem;
        }

        .detail-box h6 {
            font-size: 0.95rem;
        }

        .coverage-progress {
            margin-bottom: 2.5rem;
        }

        .coverage-progress .d-flex {
            flex-direction: column;
            gap: 0.5rem;
            align-items: flex-start !important;
        }

        .progress {
            height: 8px !important;
        }

        .policy-features {
            margin-bottom: 2.5rem;
        }

        .policy-features h6 {
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .policy-features .row {
            gap: 0.5rem;
        }

        .policy-features .feature-item {
            padding: 0.75rem;
            gap: 0.5rem;
        }

        .policy-features .feature-item i {
            font-size: 0.9rem;
        }

        .policy-features .feature-item span {
            font-size: 0.85rem;
        }

        .d-grid {
            gap: 0.75rem;
        }

        .btn-lg {
            padding: 0.875rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-lg i {
            font-size: 0.9rem;
        }

        .footer-note {
            padding: 0 1rem;
        }

        .footer-note p {
            font-size: 0.8rem;
        }
    }

    @media (max-width: 400px) {
        .card-body {
            padding: 1.2rem !important;
        }

        h3 {
            font-size: 1.1rem !important;
        }

        .detail-box {
            padding: 0.8rem;
        }

        .detail-box h6 {
            font-size: 0.9rem;
            word-break: break-word;
        }

        .btn-lg {
            padding: 0.75rem;
            font-size: 0.9rem;
        }

        .policy-features .row > div {
            flex: 0 0 100%;
            max-width: 100%;
        }

        .coverage-progress .progress {
            height: 6px !important;
        }
    }
</style>
