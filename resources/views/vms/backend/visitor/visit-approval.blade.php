@extends('layouts.receptionist')

@section('title', 'Visit Approval - UCB Bank')

@section('styles')
<style>
.timer-badge {
    font-family: 'Monaco', 'Menlo', monospace;
    font-weight: bold;
}
.timer-warning {
    animation: pulse 1s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
.rfid-tag {
    font-family: 'Monaco', 'Menlo', monospace;
    letter-spacing: 1px;
}
</style>
@endsection

@section('content')
    <!-- Header -->
    <div class="header-section">
        <div>
            <h3 class="fw-800 mb-1 text-white letter-spacing-1">Visit Approval Dashboard</h3>
            <p class="sub-label mb-0">Manage visit requests and generate RFID tags</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" onclick="loadPendingVisits()">
                <i class="fas fa-sync-alt me-2"></i>Refresh
            </button>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="glass-card summary-card">
                <div>
                    <span class="sub-label d-block mb-1">Pending Requests</span>
                    <h2 id="pending-count">0</h2>
                </div>
                <div class="summary-icon text-warning" style="background:rgba(255,193,7,0.1)">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="glass-card summary-card">
                <div>
                    <span class="sub-label d-block mb-1">Approved Today</span>
                    <h2 id="approved-count">0</h2>
                </div>
                <div class="summary-icon text-success" style="background:rgba(34,197,94,0.1)">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="glass-card summary-card">
                <div>
                    <span class="sub-label d-block mb-1">RFIDs Generated</span>
                    <h2 id="rfid-count">0</h2>
                </div>
                <div class="summary-icon text-info" style="background:rgba(59,130,246,0.1)">
                    <i class="fas fa-id-card"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="glass-card summary-card">
                <div>
                    <span class="sub-label d-block mb-1">Live Visits</span>
                    <h2 id="live-count">0</h2>
                </div>
                <div class="summary-icon text-primary" style="background:rgba(99,102,241,0.1)">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Visits Section -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-800 sub-label mb-0">
                        <i class="fas fa-bell text-warning me-2"></i>Pending Visit Requests
                        <span class="badge bg-warning text-dark ms-2" id="pending-badge">0</span>
                    </h6>
                    <small class="text-white-50">
                        <i class="fas fa-clock me-1"></i>5-minute approval window
                    </small>
                </div>

                <div id="pending-visits-container" class="table-responsive">
                    <p class="text-center text-white-50 py-5">
                        <i class="fas fa-spinner fa-spin me-2"></i>Loading pending visits...
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Visits with RFID Section -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="glass-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-800 sub-label mb-0">
                        <i class="fas fa-satellite-dish text-success me-2"></i>Live Visits with RFID
                    </h6>
                </div>

                <div id="live-visits-container" class="table-responsive">
                    <p class="text-center text-white-50 py-5">
                        <i class="fas fa-spinner fa-spin me-2"></i>Loading live visits...
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
let refreshInterval;

// Load pending visits on page load
document.addEventListener('DOMContentLoaded', function() {
    loadPendingVisits();
    loadLiveVisits();

    // Auto-refresh every 10 seconds
    refreshInterval = setInterval(function() {
        loadPendingVisits();
        loadLiveVisits();
    }, 10000);
});

// Load pending visits
function loadPendingVisits() {
    fetch('{{ route("visit-approval.pending") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderPendingVisits(data.pending_visits);
            }
        })
        .catch(error => {
            console.error('Error loading pending visits:', error);
        });
}

// Load live visits with RFID
function loadLiveVisits() {
    fetch('{{ route("visit-approval.live-visits") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderLiveVisits(data.live_visits);
            }
        })
        .catch(error => {
            console.error('Error loading live visits:', error);
        });
}

// Render pending visits
function renderPendingVisits(visits) {
    const container = document.getElementById('pending-visits-container');
    const badge = document.getElementById('pending-badge');
    const count = document.getElementById('pending-count');

    badge.textContent = visits.length;
    count.textContent = visits.length;

    if (visits.length === 0) {
        container.innerHTML = `
            <p class="text-center text-white-50 py-5">
                <i class="fas fa-check-circle text-success me-2 fa-2x"></i>
                No pending visit requests
            </p>
        `;
        return;
    }

    let html = `
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Visitor</th>
                    <th>Purpose</th>
                    <th>Host</th>
                    <th>Time Remaining</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
    `;

    visits.forEach(visit => {
        const timeRemaining = formatTimeRemaining(visit.time_remaining);
        const isWarning = visit.time_remaining < 120; // Less than 2 minutes

        html += `
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <span class="fw-800 small text-primary">${visit.visitor_name.charAt(0)}</span>
                        </div>
                        <div>
                            <span class="small fw-800 d-block text-white">${visit.visitor_name}</span>
                            <span class="fs-9 text-white-50">${visit.visitor_email}</span>
                        </div>
                    </div>
                </td>
                <td class="small text-white">${visit.purpose}</td>
                <td class="small text-white">${visit.host_name}</td>
                <td>
                    <span class="timer-badge ${isWarning ? 'text-danger timer-warning' : 'text-success'}">
                        <i class="fas fa-stopwatch me-1"></i>${timeRemaining}
                    </span>
                </td>
                <td>
                    ${visit.is_expired ?
                        '<span class="status-badge text-danger">Expired</span>' :
                        '<span class="status-badge text-warning border-orange">Pending</span>'
                    }
                </td>
                <td>
                    <div class="d-flex gap-2">
                        ${!visit.is_expired ? `
                            <button onclick="viewVisitDetails(${visit.id})" class="btn btn-circle text-info" title="View Details">
                                <i class="fas fa-eye small"></i>
                            </button>
                            <button onclick="showApproveModal(${visit.id})" class="btn btn-circle text-success" title="Approve">
                                <i class="fas fa-check small"></i>
                            </button>
                            <button onclick="showRejectModal(${visit.id})" class="btn btn-circle text-danger" title="Reject">
                                <i class="fas fa-times small"></i>
                            </button>
                        ` : `
                            <button onclick="viewVisitDetails(${visit.id})" class="btn btn-circle text-info" title="View Details">
                                <i class="fas fa-eye small"></i>
                            </button>
                        `}
                    </div>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

// Render live visits with RFID
function renderLiveVisits(visits) {
    const container = document.getElementById('live-visits-container');
    const liveCount = document.getElementById('live-count');
    const approvedCount = document.getElementById('approved-count');
    const rfidCount = document.getElementById('rfid-count');

    liveCount.textContent = visits.length;
    approvedCount.textContent = visits.filter(v => v.status === 'approved').length;
    rfidCount.textContent = visits.filter(v => v.rfid_tag).length;

    if (visits.length === 0) {
        container.innerHTML = `
            <p class="text-center text-white-50 py-5">
                <i class="fas fa-users text-info me-2 fa-2x"></i>
                No active visits with RFID
            </p>
        `;
        return;
    }

    let html = `
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Visitor</th>
                    <th>Host</th>
                    <th>Purpose</th>
                    <th>Schedule Time</th>
                    <th>RFID Tag</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
    `;

    visits.forEach(visit => {
        const statusClass = visit.status === 'approved' ? 'text-success' : 'text-info';
        const statusText = visit.status === 'approved' ? 'Active' : 'Completed';

        html += `
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <span class="fw-800 small text-success">${visit.visitor_name.charAt(0)}</span>
                        </div>
                        <div>
                            <span class="small fw-800 d-block text-white">${visit.visitor_name}</span>
                            <span class="fs-9 text-white-50">${visit.visitor_email}</span>
                        </div>
                    </div>
                </td>
                <td class="small text-white">${visit.host_name}</td>
                <td class="small text-white">${visit.purpose}</td>
                <td class="small text-white">${visit.schedule_time}</td>
                <td>
                    ${visit.rfid_tag ?
                        `<div class="rfid-tag bg-primary bg-opacity-10 text-primary px-2 py-1 rounded">
                            <i class="fas fa-id-card me-1"></i>${visit.rfid_tag}
                        </div>
                        <small class="fs-9 text-white-50 d-block">Generated by: ${visit.rfid_generated_by || 'System'}</small>` :
                        '<span class="text-white-50">N/A</span>'
                    }
                </td>
                <td>
                    <span class="status-badge ${statusClass}">${statusText}</span>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

// Format time remaining
function formatTimeRemaining(seconds) {
    if (seconds <= 0) return '00:00';

    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

// Show approve modal
function showApproveModal(visitId) {
    const rfidTag = prompt('Enter RFID Tag for this visit:');

    if (!rfidTag) return;

    const formData = new FormData();
    formData.append('rfid_tag', rfidTag);
    formData.append('_token', '{{ csrf_token() }}');

    fetch(`{{ route('visit-approval.approve', ['id' => ':id']) }}`.replace(':id', visitId), {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Visit approved successfully! RFID: ' + data.rfid);
            loadPendingVisits();
            loadLiveVisits();
        } else {
            alert('❌ ' + data.message);
            loadPendingVisits();
        }
    })
    .catch(error => {
        console.error('Error approving visit:', error);
        alert('❌ Failed to approve visit. Please try again.');
    });
}

// Show reject modal
function showRejectModal(visitId) {
    const reason = prompt('Enter rejection reason:');

    if (!reason) return;

    const formData = new FormData();
    formData.append('rejected_reason', reason);
    formData.append('_token', '{{ csrf_token() }}');

    fetch(`{{ route('visit-approval.reject', ['id' => ':id']) }}`.replace(':id', visitId), {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Visit rejected successfully.');
            loadPendingVisits();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error rejecting visit:', error);
        alert('❌ Failed to reject visit. Please try again.');
    });
}

// View visit details
function viewVisitDetails(visitId) {
    fetch(`{{ route('visit-approval.details', ['id' => ':id']) }}`.replace(':id', visitId))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const visit = data.visit;
                const details = `
Visit ID: ${visit.id}
Visitor: ${visit.visitor_name}
Email: ${visit.visitor_email}
Phone: ${visit.visitor_phone || 'N/A'}
Company: ${visit.visitor_company || 'N/A'}

Purpose: ${visit.purpose}
Visit Type: ${visit.visit_type}
Schedule Time: ${visit.schedule_time}
Host: ${visit.host_name}
Host Email: ${visit.host_email}

Status: ${visit.status.toUpperCase()}
Time Remaining: ${formatTimeRemaining(visit.time_remaining)}
${visit.rejected_reason ? 'Rejection Reason: ' + visit.rejected_reason : ''}
Requested At: ${visit.created_at}
                `;
                alert(details);
            }
        })
        .catch(error => {
            console.error('Error loading visit details:', error);
        });
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>
@endsection
