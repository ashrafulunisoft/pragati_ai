@extends('layouts.receptionist')

@section('title', $insurancePackage->name . ' - Insurance Package')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="glass-card package-detail-card">
                <div class="card-body p-5">
                    <!-- Header -->
                    <div class="text-center mb-5">
                        <div class="package-icon-large mx-auto mb-4">
                            <i class="fas fa-shield-alt fa-3x text-success"></i>
                        </div>
                        <h1 class="fw-bold text-dark mb-3">{{ $insurancePackage->name }}</h1>
                        <p class="text-muted lead mb-0">{{ $insurancePackage->description ?? 'Comprehensive insurance coverage for your peace of mind.' }}</p>
                    </div>

                    <!-- Price and Coverage -->
                    <div class="row g-4 mb-5">
                        <div class="col-md-6">
                            <div class="price-box p-4 rounded-4 text-center h-100">
                                <span class="text-muted small text-uppercase fw-bold d-block mb-2">Price</span>
                                <div class="display-4 fw-bold text-success">${{ number_format($insurancePackage->price, 2) }}</div>
                                <span class="text-muted">per {{ $insurancePackage->duration_months }} months</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="coverage-box p-4 rounded-4 text-center h-100">
                                <span class="text-muted small text-uppercase fw-bold d-block mb-2">Coverage Amount</span>
                                <div class="display-4 fw-bold text-dark">${{ number_format($insurancePackage->coverage_amount, 2) }}</div>
                                <span class="text-muted">Maximum Coverage</span>
                            </div>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="mb-5">
                        <h4 class="fw-bold text-dark mb-4 text-center">Package Features</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="feature-item d-flex align-items-center p-3 rounded-3 bg-light">
                                    <i class="fas fa-check-circle text-success me-3 fa-lg"></i>
                                    <div>
                                        <strong>Duration</strong>
                                        <p class="mb-0 text-muted small">{{ $insurancePackage->duration_months }} Months Coverage</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="feature-item d-flex align-items-center p-3 rounded-3 bg-light">
                                    <i class="fas fa-check-circle text-success me-3 fa-lg"></i>
                                    <div>
                                        <strong>Coverage Limit</strong>
                                        <p class="mb-0 text-muted small">Up to ${{ number_format($insurancePackage->coverage_amount, 2) }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="feature-item d-flex align-items-center p-3 rounded-3 bg-light">
                                    <i class="fas fa-check-circle text-success me-3 fa-lg"></i>
                                    <div>
                                        <strong>24/7 Support</strong>
                                        <p class="mb-0 text-muted small">Round the clock customer service</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="feature-item d-flex align-items-center p-3 rounded-3 bg-light">
                                    <i class="fas fa-check-circle text-success me-3 fa-lg"></i>
                                    <div>
                                        <strong>Quick Claims</strong>
                                        <p class="mb-0 text-muted small">Fast and easy claim processing</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-grid gap-3">
                        <form action="{{ route('packages.purchase', $insurancePackage->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg py-3 w-100">
                                <i class="fas fa-shopping-cart me-2"></i>
                                Buy Now - ${{ number_format($insurancePackage->price, 2) }}
                            </button>
                        </form>
                        <a href="{{ route('packages.index') }}" class="btn btn-outline-dark btn-lg py-3">
                            <i class="fas fa-arrow-left me-2"></i>
                            Back to Packages
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .package-detail-card {
        border-radius: 24px;
    }

    .package-icon-large {
        width: 100px;
        height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(11, 214, 150, 0.15) 0%, rgba(11, 214, 150, 0.05) 100%);
        border-radius: 50%;
    }

    .price-box {
        background: linear-gradient(135deg, rgba(11, 214, 150, 0.1) 0%, rgba(11, 214, 150, 0.05) 100%);
        border: 2px solid rgba(11, 214, 150, 0.3);
    }

    .coverage-box {
        background: rgba(255, 255, 255, 0.5);
        border: 2px solid rgba(0, 0, 0, 0.1);
    }

    .feature-item {
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.7) !important;
    }

    .feature-item:hover {
        background: rgba(11, 214, 150, 0.1) !important;
        transform: translateX(5px);
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
</style>
