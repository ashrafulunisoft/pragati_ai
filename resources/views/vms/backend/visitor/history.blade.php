@extends('layouts.receptionist')

@section('title', 'Visit History - UCB Bank')

@section('content')
    <!-- Header -->
    <div class="header-section">
        <div>
            <h3 class="fw-800 mb-1 text-white letter-spacing-1">Visit History</h3>
            <p class="sub-label mb-0">Complete record of all visits</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('visitor.active') }}" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="fas fa-clock"></i>
                <span>Active Visits</span>
            </a>
            <a href="{{ route('visitor.index') }}" class="btn btn-outline d-flex align-items-center gap-2">
                <i class="fas fa-list"></i>
                <span>All Visitors</span>
            </a>
        </div>
    </div>

    <!-- Visit History Table -->
    <div class="glass-card p-4">
        <div class="table-responsive log-container">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Visitor</th>
                        <th>Company</th>
                        <th>Visit Type</th>
                        <th>Purpose</th>
                        <th>Scheduled Date</th>
                        <th>Host</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($visits as $visit)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <span class="fw-800 small">{{ substr($visit->visitor->name, 0, 1) }}</span>
                                </div>
                                <div>
                                    <span class="small fw-800 d-block">{{ $visit->visitor->name }}</span>
                                    <span class="fs-9 text-white-50">{{ $visit->visitor->email }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="small">{{ $visit->visitor->address ?? 'N/A' }}</td>
                        <td class="small">{{ $visit->type->name ?? 'N/A' }}</td>
                        <td class="small">{{ substr($visit->purpose, 0, 30) }}...</td>
                        <td class="small">{{ \Carbon\Carbon::parse($visit->schedule_time)->format('M j, Y - g:i A') }}</td>
                        <td class="small">{{ $visit->meetingUser->name }}</td>
                        <td>
                            @if($visit->status == 'approved')
                                <span class="status-badge text-success">Approved</span>
                            @elseif($visit->status == 'pending_otp' || $visit->status == 'pending_host')
                                <span class="status-badge text-warning border-orange">Pending</span>
                            @elseif($visit->status == 'checked_in')
                                <span class="status-badge" style="background: rgba(59, 130, 246, 0.2);">Checked In</span>
                            @elseif($visit->status == 'completed')
                                <span class="status-badge">Completed</span>
                            @elseif($visit->status == 'rejected')
                                <span class="status-badge text-danger">Rejected</span>
                            @else
                                <span class="status-badge">{{ ucfirst($visit->status) }}</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('visitor.show', $visit->id) }}" class="btn btn-circle text-info" title="View Details">
                                <i class="fas fa-eye small"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-history mb-3" style="font-size: 3rem;"></i>
                                <p class="mb-0">No visit history</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($visits->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $visits->appends(request()->query())->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
@endsection
