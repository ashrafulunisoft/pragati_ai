@extends('layouts.receptionist')

@section('title', 'Pending Approvals - UCB Bank')

@section('content')
    <!-- Header -->
    <div class="header-section">
        <div>
            <h3 class="fw-800 mb-1 text-white letter-spacing-1">Pending Approvals</h3>
            <p class="sub-label mb-0">Visits waiting for host approval</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('visitor.live') }}" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="fas fa-broadcast-tower"></i>
                <span>Live Dashboard</span>
            </a>
            <a href="{{ route('visitor.index') }}" class="btn btn-outline d-flex align-items-center gap-2">
                <i class="fas fa-list"></i>
                <span>All Visitors</span>
            </a>
        </div>
    </div>

    <!-- Pending Visits Table -->
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
                            <div class="d-flex gap-2">
                                <a href="{{ route('visitor.show', $visit->id) }}" class="btn btn-circle text-info" title="View Details">
                                    <i class="fas fa-eye small"></i>
                                </a>
                                @can('approve visit')
                                <button type="button" class="btn btn-circle btn-accept" onclick="approveVisit({{ $visit->id }})" title="Approve Visit">
                                    <i class="fas fa-check small"></i>
                                </button>
                                @endcan
                                @can('reject visit')
                                <button type="button" class="btn btn-circle btn-reject" onclick="rejectVisit({{ $visit->id }})" title="Reject Visit">
                                    <i class="fas fa-times small"></i>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-clock mb-3" style="font-size: 3rem;"></i>
                                <p class="mb-0">No pending approvals</p>
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

    <!-- Approve/Reject Scripts -->
    <script>
        function approveVisit(visitId) {
            Swal.fire({
                title: 'Approve Visit?',
                text: 'This will approve the visit and generate an RFID badge.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#22c55e',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, approve!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`{{ route('visit.approve', ':id') }}`.replace(':id', visitId), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Approved!', data.message, 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error!', 'Failed to approve visit', 'error');
                    });
                }
            });
        }

        function rejectVisit(visitId) {
            Swal.fire({
                title: 'Reject Visit?',
                input: 'textarea',
                inputLabel: 'Reason for rejection',
                inputPlaceholder: 'Please enter the reason...',
                inputAttributes: {
                    'aria-label': 'Type your reason here'
                },
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, reject!',
                preConfirm: (reason) => {
                    if (!reason) {
                        Swal.showValidationMessage('Please enter a reason for rejection');
                        return false;
                    }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`{{ route('visit.reject', ':id') }}`.replace(':id', visitId), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            reason: result.value
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Rejected!', data.message, 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error!', 'Failed to reject visit', 'error');
                    });
                }
            });
        }
    </script>
@endsection
