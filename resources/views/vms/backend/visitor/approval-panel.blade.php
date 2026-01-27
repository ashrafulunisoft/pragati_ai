@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-user-clock me-2"></i>
                Visitor Approval Request
            </h5>
            <span class="badge bg-warning fs-6">Pending Approval</span>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary fw-bold mb-3">
                        <i class="fas fa-user me-2"></i>Visitor Details
                    </h6>
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold" style="width: 120px;">Name:</td>
                            <td>{{ $visit->visitor->name }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Email:</td>
                            <td>{{ $visit->visitor->email }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Phone:</td>
                            <td>{{ $visit->visitor->phone ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Company:</td>
                            <td>{{ $visit->visitor->address ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>

                <div class="col-md-6">
                    <h6 class="text-primary fw-bold mb-3">
                        <i class="fas fa-calendar-alt me-2"></i>Visit Details
                    </h6>
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold" style="width: 120px;">Purpose:</td>
                            <td>{{ $visit->purpose }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Visit Type:</td>
                            <td>{{ $visit->type->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Scheduled Time:</td>
                            <td>{{ \Carbon\Carbon::parse($visit->schedule_time)->format('F j, Y - g:i A') }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Host:</td>
                            <td>{{ $visit->meetingUser->name }}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Visit ID:</td>
                            <td>#{{ $visit->id }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <hr class="my-4">

            <div class="row">
                <div class="col-12">
                    <h6 class="text-primary fw-bold mb-3">
                        <i class="fas fa-tasks me-2"></i>Actions
                    </h6>

                    <div class="d-flex gap-3 flex-wrap">
                        @can('approve visit')
                        <form method="POST" action="{{ route('visit.approve', $visit->id)">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('Are you sure you want to approve this visit? This will generate an RFID for the visitor.');">
                                <i class="fas fa-check-circle me-2"></i>
                                Approve Visit
                            </button>
                        </form>
                        @endcan

                        @can('reject visit')
                        <form method="POST" action="{{ route('visit.reject', $visit->id) }}" class="flex-grow-1">
                            @csrf
                            <div class="input-group">
                                <input type="text"
                                       name="reason"
                                       class="form-control"
                                       placeholder="Enter rejection reason (required)"
                                       required>
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this visit? The visitor will be notified.');">
                                    <i class="fas fa-times-circle me-2"></i>
                                    Reject
                                </button>
                            </div>
                        </form>
                        @endcan
                    </div>
                </div>
            </div>

            <div class="mt-4 p-3 bg-light rounded">
                <h6 class="fw-bold mb-2">
                    <i class="fas fa-info-circle me-2 text-info"></i>
                    Important Information
                </h6>
                <ul class="mb-0">
                    <li><strong>Approve:</strong> Generates RFID badge and notifies visitor via email</li>
                    <li><strong>Reject:</strong> Cancels the visit and sends rejection reason to visitor</li>
                    <li>Visitor OTP has been verified and is waiting for host approval</li>
                    <li>All actions are logged for audit purposes</li>
                </ul>
            </div>
        </div>

        <div class="card-footer bg-light text-muted small">
            <div class="row">
                <div class="col-md-6">
                    <i class="fas fa-clock me-1"></i>
                    Created: {{ $visit->created_at->format('M j, Y - g:i A') }}
                </div>
                <div class="col-md-6 text-md-end">
                    <i class="fas fa-user-shield me-1"></i>
                    Action by: {{ auth()->user()->name }}
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('visitor.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Back to Visitors List
        </a>
    </div>
</div>
@endsection
