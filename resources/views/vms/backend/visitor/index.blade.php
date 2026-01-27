@extends('layouts.receptionist')

@section('title', 'Visitor List - UCB Bank')

@section('content')
    <!-- Header -->
    <div class="header-section">
        <div>
            <h3 class="fw-800 mb-1 text-white letter-spacing-1">Visitor List</h3>
            <p class="sub-label mb-0">Manage all registered visitors</p>
        </div>
        <div class="d-flex gap-2">
            @can('create visitors')
            <a href="{{ route('visitor.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="fas fa-plus"></i>
                <span>Register Visitor</span>
            </a>
            @endcan
            <a href="{{ route('visitor.statistics') }}" class="btn btn-outline d-flex align-items-center gap-2">
                <i class="fas fa-chart-bar"></i>
                <span>Statistics</span>
            </a>
        </div>
    </div>

    <!-- Visitor List Table -->
    <div class="glass-card p-4">
        <div class="table-responsive log-container">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Visitor</th>
                        <th>Host</th>
                        <th>Visit Type</th>
                        <th>Purpose</th>
                        <th>Scheduled Date</th>
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
                        <td class="small">{{ $visit->meetingUser->name }}</td>
                        <td class="small">{{ $visit->type->name ?? 'N/A' }}</td>
                        <td class="small">{{ substr($visit->purpose, 0, 30) }}...</td>
                        <td class="small">{{ \Carbon\Carbon::parse($visit->schedule_time)->format('M j, Y - g:i A') }}</td>
                        <td>
                            @if($visit->status == 'approved')
                                <span class="status-badge text-success">Approved</span>
                            @elseif($visit->status == 'pending')
                                <span class="status-badge text-warning border-orange">Pending</span>
                            @elseif($visit->status == 'completed')
                                <span class="status-badge">Completed</span>
                            @else
                                <span class="status-badge text-danger">Cancelled</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('visitor.show', $visit->id) }}" class="btn btn-circle text-info" title="View Details">
                                    <i class="fas fa-eye small"></i>
                                </a>
                                @can('edit visitors')
                                <a href="{{ route('visitor.edit', $visit->id) }}" class="btn btn-circle text-primary" title="Edit">
                                    <i class="fas fa-edit small"></i>
                                </a>
                                @endcan
                                @can('delete visitors')
                                <button type="button" class="btn btn-circle btn-reject" onclick="deleteVisit({{ $visit->id }})" title="Delete">
                                    <i class="fas fa-trash small"></i>
                                </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-users-slash mb-3" style="font-size: 3rem;"></i>
                                <p class="mb-0">No visitors found</p>
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

    <!-- Delete Confirmation Script -->
    <script>
        function deleteVisit(visitId) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'You want to delete this visitor record?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`{{ route('visitor.destroy', ':id') }}`.replace(':id', visitId), {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Deleted!', data.message, 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Error!', 'Failed to delete visitor', 'error');
                    });
                }
            });
        }
    </script>
@endsection
