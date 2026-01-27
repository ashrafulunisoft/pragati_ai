@extends('layouts.guest')

@section('title', 'File a Claim - ' . $order->policy_number)

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Back Button -->
            <div class="mb-4">
                <a href="{{ route('orders.show', $order->id) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Policy
                </a>
            </div>

            <!-- Claim Form Card -->
            <div class="glass-card">
                <div class="card-header bg-success text-white py-3">
                    <div class="d-flex justify-content-between align-items-center px-4">
                        <h5 class="mb-0">
                            <i class="fas fa-file-medical me-2"></i>
                            File an Insurance Claim
                        </h5>
                    </div>
                </div>
                <div class="card-body p-5">
                    <!-- Policy Info -->
                    <div class="policy-info mb-4 p-4 rounded-3 bg-light">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="fw-bold text-dark mb-1">{{ $order->package->name }}</h6>
                                <p class="text-muted mb-0 small">Policy: {{ $order->policy_number }}</p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <span class="badge bg-success">Active</span>
                                <p class="text-muted small mb-0 mt-1">Coverage: ${{ number_format($order->package->coverage_amount, 2) }}</p>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('orders.claim.store', $order->id) }}" method="POST">
                        @csrf
                        
                        <!-- Claim Amount -->
                        <div class="mb-4">
                            <label for="claim_amount" class="form-label fw-bold text-dark">Claim Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">$</span>
                                <input type="number" 
                                       class="form-control @error('claim_amount') is-invalid @enderror" 
                                       id="claim_amount" 
                                       name="claim_amount" 
                                       placeholder="Enter claim amount"
                                       min="1"
                                       max="{{ $order->package->coverage_amount }}"
                                       value="{{ old('claim_amount') }}"
                                       required>
                            </div>
                            <div class="form-text text-muted">
                                Maximum claim amount: ${{ number_format($order->package->coverage_amount, 2) }}
                            </div>
                            @error('claim_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Reason -->
                        <div class="mb-4">
                            <label for="reason" class="form-label fw-bold text-dark">Reason for Claim <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('reason') is-invalid @enderror" 
                                      id="reason" 
                                      name="reason" 
                                      rows="5"
                                      placeholder="Please provide detailed information about your claim..."
                                      required>{{ old('reason') }}</textarea>
                            <div class="form-text text-muted">Minimum 10 characters. Please be as detailed as possible.</div>
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Important Notes -->
                        <div class="alert alert-info mb-4">
                            <h6 class="alert-heading fw-bold">
                                <i class="fas fa-info-circle me-2"></i>Important Information
                            </h6>
                            <ul class="mb-0 small">
                                <li>Claims are typically processed within 5-7 business days</li>
                                <li>You will receive email updates on your claim status</li>
                                <li>Additional documentation may be required for verification</li>
                                <li>Claim amount cannot exceed your policy coverage limit</li>
                            </ul>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid gap-3">
                            <button type="submit" class="btn btn-success btn-lg py-3">
                                <i class="fas fa-paper-plane me-2"></i>
                                Submit Claim
                            </button>
                            <a href="{{ route('orders.show', $order->id) }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-control:focus, .input-group-text:focus {
        border-color: #0bd696;
        box-shadow: 0 0 0 0.2rem rgba(11, 214, 150, 0.25);
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
</style>
