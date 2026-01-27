@extends('layouts.receptionist')

@section('title', 'Visitor Report - UCB Bank')

@php
    // Prepare selected visitors data for JavaScript
    $initialSelectedVisitors = [];
    if (isset($selectedVisitors) && !is_null($selectedVisitors) && count($selectedVisitors) > 0) {
        $initialSelectedVisitors = collect($selectedVisitors)->map(function($v) {
            return ['id' => $v->id, 'name' => $v->name, 'phone' => $v->phone, 'email' => $v->email];
        })->toArray();
    }

    // Prepare selected host data for JavaScript
    $initialSelectedHost = [];
    if (isset($selectedHost) && !is_null($selectedHost)) {
        $initialSelectedHost = ['id' => $selectedHost->id, 'name' => $selectedHost->name, 'email' => $selectedHost->email];
    }
@endphp

@section('content')
    <!-- Header -->
    <div class="header-section">
        <div>
            <h3 class="fw-800 mb-1 text-white letter-spacing-1">Visitor Report</h3>
            <p class="sub-label mb-0">Generate visitor reports with filters</p>
        </div>
    </div>

    <!-- Filter Panel -->
    <div class="glass-card p-4 mb-4">
        <form id="reportForm" action="{{ route('visitor.report') }}" method="GET">
            @csrf
            <div class="row g-3">
                <!-- Start Date -->
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <div class="position-relative">
                        <input type="date"
                               name="start_date"
                               id="startDate"
                               value="{{ $startDate ?? '' }}"
                               class="input-dark input-custom text-primary">
                        {{-- <i class="fas fa-calendar-day input-icon"></i> --}}
                    </div>
                </div>

                <!-- End Date -->
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <div class="position-relative">
                        <input type="date"
                               name="end_date"
                               id="endDate"
                               value="{{ $endDate ?? '' }}"
                               class="input-dark input-custom">
                        {{-- <i class="fas fa-calendar-check input-icon"></i> --}}
                    </div>
                </div>

                <!-- Phone Search -->
                <div class="col-md-3">
                    <label class="form-label">Search Visitor by Phone</label>
                    <div class="position-relative">
                        <input type="text"
                               id="phoneSearch"
                               class="input-dark input-custom"
                               placeholder="Type phone number to search..."
                               autocomplete="off">
                        <i class="fas fa-phone input-icon"></i>
                        <!-- Autocomplete Dropdown -->
                        <div id="phoneDropdown" class="autocomplete-dropdown" style="display: none;"></div>
                    </div>
                </div>

                <!-- Host Email Search -->
                <div class="col-md-3">
                    <label class="form-label">Search Host by Email</label>
                    <div class="position-relative">
                        <input type="text"
                               id="hostEmailSearch"
                               class="input-dark input-custom"
                               placeholder="Type host email to search..."
                               autocomplete="off">
                        <i class="fas fa-envelope input-icon"></i>
                        <!-- Autocomplete Dropdown -->
                        <div id="hostEmailDropdown" class="autocomplete-dropdown" style="display: none;"></div>
                    </div>
                </div>
            </div>

            <!-- Hidden inputs for form submission -->
            <input type="hidden" name="visitor_ids[]" id="visitorIdsInput">
            <input type="hidden" name="host_email" id="hostEmailInput">
        </form>

        <!-- Action Buttons -->
        <div id="actionButtonsSection" class="mt-3" style="display: none;">
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-success" onclick="generateCSV()">
                    <i class="fas fa-file-csv me-1"></i>Generate CSV
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearAllSelections()">
                    <i class="fas fa-times me-1"></i>Clear All
                </button>
            </div>
        </div>
    </div>

    <!-- Results Table -->
    <div class="glass-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 class="fw-800 text-white mb-0">
                <i class="fas fa-table text-primary me-2"></i>Report Results
            </h6>
            <div class="text-muted small">
                Total: <span class="text-white fw-bold" id="totalVisits">0</span> visits
            </div>
        </div>

        <div class="table-responsive log-container">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Visitor</th>
                        <th>Phone</th>
                        <th>Host</th>
                        <th>Visit Type</th>
                        <th>Purpose</th>
                        <th>Scheduled Date</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="resultsTableBody">
                    <tr id="emptyState">
                        <td colspan="10" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-users-slash mb-3" style="font-size: 3rem;"></i>
                                <p class="mb-0">Select visitors or host to add to report</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="paginationSection" class="d-flex justify-content-center mt-4" style="display: none;">
        </div>
    </div>

    <!-- Custom Styles for Autocomplete -->
    <style>
        /* Ensure filter panel has higher z-index */
        .glass-card.mb-4 {
            position: relative;
            z-index: 1000;
        }

        .autocomplete-dropdown {
            position: absolute;
            background: rgba(15, 23, 42, 0.98);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            z-index: 999999 !important;
            max-height: 250px;
            overflow-y: auto;
            min-width: 200px;
            margin-top: 8px;
        }

        /* Reduce z-index of table container */
        .table-responsive {
            z-index: 1;
            position: relative;
        }

        .autocomplete-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            transition: 0.2s;
        }

        .autocomplete-item:hover {
            background: rgba(59, 130, 246, 0.2);
        }

        .autocomplete-item:last-child {
            border-bottom: none;
        }

        /* Make secondary text more visible in dropdown */
        .autocomplete-item .text-muted {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        /* Make secondary text more visible in results table */
        .glass-card.p-4 .text-muted {
            color: rgba(255, 255, 255, 0.7) !important;
        }

        .preview-row {
            background: rgba(59, 130, 246, 0.1);
            border-left: 3px solid #3b82f6;
        }

        .remove-btn {
            background: rgba(239, 68, 68, 0.2);
            border: none;
            color: #f87171;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
            font-size: 12px;
        }

        .remove-btn:hover {
            background: rgba(239, 68, 68, 0.4);
        }
    </style>

    <!-- JavaScript for Autocomplete and Multi-Select -->
    <script>
        // Store selected visitors and host
        let selectedVisitors = @json($initialSelectedVisitors);
        let selectedHost = {{ isset($selectedHost) ? json_encode($initialSelectedHost) : 'null' }};
        let searchTimeout;

        // Initialize selected visitors display
        document.addEventListener('DOMContentLoaded', function() {
            renderSelectedVisitors();
            renderSelectedHost();
            // Only render preview results if there are selections
            if (selectedVisitors.length > 0 || selectedHost) {
                renderPreviewResults();
            }
        });

        // Phone search with debounce
        document.getElementById('phoneSearch').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 3) {
                document.getElementById('phoneDropdown').style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(function() {
                searchVisitorsByPhone(query);
            }, 300);
        });

        // Host email search with debounce
        document.getElementById('hostEmailSearch').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 3) {
                document.getElementById('hostEmailDropdown').style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(function() {
                searchHostsByEmail(query);
            }, 300);
        });

        // Search visitors by phone
        function searchVisitorsByPhone(query) {
            fetch('{{ route('visitor.search-phone') }}?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.visitors.length > 0) {
                        renderDropdown(data.visitors);
                    } else {
                        document.getElementById('phoneDropdown').style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error searching visitors:', error);
                });
        }

        // Search hosts by email
        function searchHostsByEmail(query) {
            fetch('{{ route('visitor.search-host') }}?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.hosts.length > 0) {
                        renderHostDropdown(data.hosts);
                    } else {
                        document.getElementById('hostEmailDropdown').style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error searching hosts:', error);
                });
        }

        // Render autocomplete dropdown for visitors
        function renderDropdown(visitors) {
            const dropdown = document.getElementById('phoneDropdown');
            const input = document.getElementById('phoneSearch');
            const rect = input.getBoundingClientRect();

            dropdown.innerHTML = visitors.map(visitor => `
                <div class="autocomplete-item" onclick="addVisitor(${visitor.id}, '${visitor.name}', '${visitor.phone}', '${visitor.email}')">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold text-white">${visitor.name}</div>
                            <div class="small text-muted">${visitor.phone}</div>
                        </div>
                        <i class="fas fa-plus text-primary"></i>
                    </div>
                </div>
            `).join('');

            // Position dropdown below input
            dropdown.style.top = '100%';
            dropdown.style.left = '0';
            dropdown.style.width = '100%';
            dropdown.style.display = 'block';
        }

        // Render autocomplete dropdown for hosts
        function renderHostDropdown(hosts) {
            const dropdown = document.getElementById('hostEmailDropdown');
            const input = document.getElementById('hostEmailSearch');
            const rect = input.getBoundingClientRect();

            dropdown.innerHTML = hosts.map(host => `
                <div class="autocomplete-item" onclick="selectHost(${host.id}, '${host.name}', '${host.email}')">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold text-white">${host.name}</div>
                            <div class="small text-muted">${host.email}</div>
                        </div>
                        <i class="fas fa-plus text-primary"></i>
                    </div>
                </div>
            `).join('');

            // Position dropdown below input
            dropdown.style.top = '100%';
            dropdown.style.left = '0';
            dropdown.style.width = '100%';
            dropdown.style.display = 'block';
        }

        // Add visitor to selected list
        function addVisitor(id, name, phone, email) {
            // Check if already selected
            if (selectedVisitors.find(v => v.id === id)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Already Added',
                    text: 'This visitor is already in your selection.',
                    timer: 1500,
                    showConfirmButton: false
                });
                return;
            }

            selectedVisitors.push({ id, name, phone, email });
            renderSelectedVisitors();
            renderPreviewResults();
            document.getElementById('phoneSearch').value = '';
            document.getElementById('phoneDropdown').style.display = 'none';
        }

        // Select host
        function selectHost(id, name, email) {
            // Check if host already selected
            if (selectedHost && selectedHost.id === id) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Host Already Selected',
                    text: 'A host is already selected. Clear the current selection first.',
                    timer: 1500,
                    showConfirmButton: false
                });
                return;
            }

            selectedHost = { id, name, email };
            renderSelectedHost();
            renderPreviewResults();
            document.getElementById('hostEmailSearch').value = '';
            document.getElementById('hostEmailDropdown').style.display = 'none';
        }

        // Remove visitor from selected list
        function removeVisitor(id) {
            selectedVisitors = selectedVisitors.filter(v => v.id !== id);
            renderSelectedVisitors();
            renderPreviewResults();
        }

        // Clear selected host
        function clearSelectedHost() {
            selectedHost = null;
            renderSelectedHost();
            renderPreviewResults();
            document.getElementById('hostEmailSearch').value = '';
        }

        // Clear all selections
        function clearAllSelections() {
            Swal.fire({
                title: 'Clear All Selections?',
                text: 'This will remove all selected visitors and host from the report.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, clear all!'
            }).then((result) => {
                if (result.isConfirmed) {
                    selectedVisitors = [];
                    selectedHost = null;
                    renderSelectedVisitors();
                    renderSelectedHost();
                    renderPreviewResults();
                    document.getElementById('hostEmailSearch').value = '';
                }
            });
        }

        // Render selected visitors list (now only updates hidden input)
        function renderSelectedVisitors() {
            const input = document.getElementById('visitorIdsInput');
            input.value = selectedVisitors.map(v => v.id).join(',');
        }

        // Render selected host (now only updates hidden input)
        function renderSelectedHost() {
            const input = document.getElementById('hostEmailInput');
            input.value = selectedHost ? selectedHost.id : '';
        }

        // Render preview results for selected visitors and host
        function renderPreviewResults() {
            const tableBody = document.getElementById('resultsTableBody');
            const emptyState = document.getElementById('emptyState');
            const totalVisits = document.getElementById('totalVisits');
            const clearSection = document.getElementById('clearSelectionsSection');

            let html = '';
            let totalCount = 0;

            // Add selected visitors to results
            if (selectedVisitors.length > 0) {
                selectedVisitors.forEach(visitor => {
                    totalCount++;
                    html += `
                        <tr class="preview-row">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <span class="fw-800 small">${visitor.name.charAt(0)}</span>
                                    </div>
                                    <div>
                                        <span class="small fw-800 d-block">${visitor.name}</span>
                                        <span class="fs-9 text-white-50">${visitor.email}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="small">${visitor.phone || 'N/A'}</td>
                            <td class="small">${selectedHost ? selectedHost.name : 'N/A'}</td>
                            <td class="small">N/A</td>
                            <td class="small">-</td>
                            <td class="small">-</td>
                            <td class="small"><span class="text-muted">-</span></td>
                            <td class="small"><span class="text-muted">-</span></td>
                            <td>
                                <span class="status-badge text-info">Selected</span>
                            </td>
                            <td>
                                <button type="button" class="remove-btn" onclick="removeVisitor(${visitor.id})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }

            // Add selected host to results
            if (selectedHost) {
                totalCount++;
                html += `
                    <tr class="preview-row">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <span class="fw-800 small">${selectedHost.name.charAt(0)}</span>
                                </div>
                                <div>
                                    <span class="small fw-800 d-block">-</span>
                                    <span class="fs-9 text-white-50">${selectedHost.email}</span>
                                </div>
                            </div>
                        </td>
                        <td class="small">-</td>
                        <td class="small">${selectedHost.name}</td>
                        <td class="small">N/A</td>
                        <td class="small">-</td>
                        <td class="small">-</td>
                        <td class="small"><span class="text-muted">-</span></td>
                        <td class="small"><span class="text-muted">-</span></td>
                        <td>
                            <span class="status-badge text-info">Selected</span>
                        </td>
                        <td>
                            <button type="button" class="remove-btn" onclick="clearSelectedHost()">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }

            // Show/hide action buttons
            const actionButtonsSection = document.getElementById('actionButtonsSection');
            actionButtonsSection.style.display = (selectedVisitors.length > 0 || selectedHost) ? 'block' : 'none';

            // Update table
            // Remove existing preview rows first
            const existingPreviewRows = tableBody.querySelectorAll('.preview-row');
            existingPreviewRows.forEach(row => row.remove());

            if (html) {
                // Check if empty state exists and remove it
                if (emptyState) {
                    emptyState.remove();
                }

                // Add preview rows to the beginning of table
                tableBody.insertAdjacentHTML('afterbegin', html);

                // Update total count
                totalVisits.textContent = totalCount;
            } else {
                // Show empty state when no selections
                if (emptyState) {
                    emptyState.style.display = 'table-row';
                }
                totalVisits.textContent = '0';
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const phoneDropdown = document.getElementById('phoneDropdown');
            const hostDropdown = document.getElementById('hostEmailDropdown');
            const phoneSearch = document.getElementById('phoneSearch');
            const hostSearch = document.getElementById('hostEmailSearch');

            if (!phoneDropdown.contains(e.target) && e.target !== phoneSearch) {
                phoneDropdown.style.display = 'none';
            }

            if (!hostDropdown.contains(e.target) && e.target !== hostSearch) {
                hostDropdown.style.display = 'none';
            }
        });

        // Generate CSV function
        function generateCSV() {
            // Check if there are any selections
            if (selectedVisitors.length === 0 && !selectedHost) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Selection',
                    text: 'Please select at least one visitor or host to generate CSV.',
                    timer: 2000,
                    showConfirmButton: false
                });
                return;
            }

            // Get form values
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const visitorIds = selectedVisitors.map(v => v.id);
            const hostEmail = selectedHost ? selectedHost.id : null;

            // Build query parameters
            const params = new URLSearchParams();
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);
            visitorIds.forEach(id => params.append('visitor_ids[]', id));
            if (hostEmail) params.append('host_email', hostEmail);

            // Show loading state
            Swal.fire({
                title: 'Generating CSV...',
                text: 'Please wait while we generate your report.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Fetch CSV
            fetch('{{ route('visitor.report.export-csv') }}?' + params.toString(), {
                method: 'GET',
                headers: {
                    'Accept': 'text/csv',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to generate CSV');
                }
                return response.blob();
            })
            .then(blob => {
                // Create download link
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'visitor_report_' + new Date().toISOString().slice(0,10) + '.csv';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'CSV has been downloaded successfully!',
                    timer: 2000,
                    showConfirmButton: false
                });
            })
            .catch(error => {
                console.error('Error generating CSV:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to generate CSV. Please try again.',
                });
            });
        }
    </script>
@endsection
