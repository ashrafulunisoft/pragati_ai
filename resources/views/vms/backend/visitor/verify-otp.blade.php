@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">
                        <i class="fas fa-shield-alt me-2"></i>
                        Verify OTP
                    </h4>
                </div>

                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <div class="mb-3">
                            <i class="fas fa-envelope-open-text fa-4x text-primary"></i>
                        </div>
                        <p class="text-muted">
                            A 6-digit OTP has been sent to your email address:
                        </p>
                        <p class="font-weight-bold text-dark">
                            {{ $visit->visitor->email }}
                        </p>
                        <p class="text-muted small">
                            Please enter the OTP below to verify your visit request.
                        </p>
                    </div>

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('visitor.verify.otp', $visit->id) }}">
                        @csrf

                        <div class="mb-4">
                            <label for="otp" class="form-label fw-bold">Enter OTP</label>
                            <input type="text"
                                   id="otp"
                                   name="otp"
                                   class="form-control form-control-lg text-center fs-4 fw-bold"
                                   maxlength="6"
                                   pattern="[0-9]{6}"
                                   placeholder="000000"
                                   required
                                   autofocus
                                   style="letter-spacing: 0.5rem;">
                            <div class="form-text text-muted text-center mt-2">
                                Enter the 6-digit OTP sent to your email
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-check-circle me-2"></i>
                                Verify OTP
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="text-muted small mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            OTP is valid for 10 minutes
                        </p>
                    </div>
                </div>

                <div class="card-footer bg-light text-center">
                    <small class="text-muted">
                        Visit ID: #{{ $visit->id }} |
                        Scheduled: {{ \Carbon\Carbon::parse($visit->schedule_time)->format('M j, Y - g:i A') }}
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #otp:focus {
        box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        border-color: #0d6efd;
    }
</style>
@endpush
