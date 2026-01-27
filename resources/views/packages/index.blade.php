@extends('layouts.guest')

@section('title', 'Insurance Packages - Pragati AI')

@section('content')
<div class="container py-5">
    <!-- Header -->
    <div class="text-center mb-5">
        <div class="glass-card d-inline-block px-5 py-4 mb-4">
            <h1 class="fw-bold text-dark mb-2">
                <i class="fas fa-shield-alt text-success me-2"></i>
                Insurance Packages
            </h1>
            <p class="text-muted mb-0">Choose the perfect insurance plan for your needs</p>
        </div>
    </div>

    @if($packages->isEmpty())
        <div class="glass-card p-5 text-center mx-auto" style="max-width: 500px;">
            <div class="text-muted mb-4">
                <i class="fas fa-box-open fa-5x" style="color: #0bd696;"></i>
            </div>
            <h3 class="fw-bold text-dark mb-3">No Packages Available</h3>
            <p class="text-muted">Check back later for our insurance packages.</p>
        </div>
    @else
        <div class="row g-4 justify-content-center">
            @foreach($packages as $package)
                <div class="col-md-6 col-lg-4">
                    <div class="glass-card h-100 package-card">
                        <div class="card-body p-4">
                            <!-- Header -->
                            <div class="text-center mb-4">
                                <div class="package-icon mx-auto mb-3">
                                    <i class="fas fa-shield-alt fa-2x text-success"></i>
                                </div>
                                <h4 class="card-title fw-bold text-dark">{{ $package->name }}</h4>
                                <p class="text-muted small mb-0">{{ Str::limit($package->description, 60) }}</p>
                            </div>

                            <!-- Price -->
                            <div class="text-center mb-4">
                                <span class="display-5 fw-bold text-success">${{ number_format($package->price, 2) }}</span>
                                <span class="text-muted">/ {{ $package->duration_months }} months</span>
                            </div>

                            <!-- Features -->
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Coverage: <strong>${{ number_format($package->coverage_amount, 2) }}</strong>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Duration: <strong>{{ $package->duration_months }} Months</strong>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    24/7 Support
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Quick Claims
                                </li>
                            </ul>

                            <!-- Buttons -->
                            <div class="d-grid gap-2">
                                <a href="{{ route('packages.show', $package->id) }}" class="btn btn-outline-success">
                                    <i class="fas fa-eye me-2"></i>View Details
                                </a>
                                <form action="{{ route('packages.purchase', $package->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-shopping-cart me-2"></i>Buy Now
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<style>
    .package-card {
        transition: all 0.3s ease;
    }

    .package-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15) !important;
    }

    .package-icon {
        width: 70px;
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(11, 214, 150, 0.15) 0%, rgba(11, 214, 150, 0.05) 100%);
        border-radius: 50%;
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

    .list-unstyled li {
        color: #495057;
    }
</style>
