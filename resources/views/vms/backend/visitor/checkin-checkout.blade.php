@extends('layouts.receptionist')

@section('title', 'Check-in/Check-out - UCB Bank')

@section('content')
    <!-- Header -->
    <div class="header-section">
        <div>
            <h3 class="fw-800 mb-1 text-white letter-spacing-1">Check-in / Check-out</h3>
            <p class="sub-label mb-0">Manage visitor arrivals and departures</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('visitor.active') }}" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="fas fa-user-clock"></i>
                <span>Active Visits</span>
            </a>
            <a href="{{ route('visitor.approved') }}" class="btn btn-outline d-flex align-items-center gap-2">
                <i class="fas fa-check-circle"></i>
                <span>Approved Visits</span>
            </a>
        </div>
    </div>

    <!-- Visits Table -->
    <div class="glass-card p-4">
        <div class="table-responsive log-container">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Visitor</th>
                        <th>Company</th>
                        <th>Visit Type</th>
                        <th>Scheduled Date</th>
                        <th>Host</th>
                        <th>RFID</th>
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
                        <td class="small">{{ \Carbon\Carbon::parse($visit->schedule_time)->format('M j, Y - g:i A') }}</td>
                        <td class="small">{{ $visit->meetingUser->name }}</td>
                        <td class="small"><code>{{ $visit->rfid ?? 'N/A' }}</code></td>
                        <td>
                            @if($visit->status == 'approved')
                                <span class="status-badge text-success">Approved</span>
                            @elseif($visit->status == 'checked_in')
                                <span class="status-badge" style="background: rgba(59, 130, 246, 0.2);">Checked In</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('visitor.show', $visit->id) }}" class="btn btn-circle text-info" title="View Details">
                                    <i class="fas fa-eye small"></i>
                                </a>
                                @if($visit->status == 'approved')
                                @can('checkin visit')
                                <button type="button" class="btn btn-circle btn-accept" onclick="checkIn({{ $visit->id }})" title="Check In">
                                    <i class="fas fa-sign-in-alt small"></i>
                                </button>
                                @endcan
                                @elseif($visit->status == 'checked_in')
                                @can('checkout visit')
                                <button type="button" class="btn btn-circle btn-reject" onclick="checkOut({{ $visit->id }})" title="Check Out">
                                    <i class="fas fa-sign-out-alt small"></i>
                                </button>
                                @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-user-check mb-3" style="font-size: 3rem;"></i>
                                <p class="mb-0">No visits ready for check-in/check-out</p>
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

    <!-- Check-in/Check-out Scripts -->
    <script>
        function checkIn(visitId) {
            Swal.fire({
                title: 'Check In Visitor?',
                text: 'This will mark the visitor as checked in.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#22c55e',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, check in!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`{{ route('visit.checkin', ':id') }}`.replace(':id', visitId), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Success!', data.message, 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error!', 'Failed to check in visitor', 'error');
                    });
                }
            });
        }

        function checkOut(visitId) {
            Swal.fire({
                title: 'Check Out Visitor?',
                text: 'This will mark the visit as completed.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, check out!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`{{ route('visit.checkout', ':id') }}`.replace(':id', visitId), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Success!', data.message, 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error!', 'Failed to check out visitor', 'error');
                    });
                }
            });
        }
    </script>
@endsection
