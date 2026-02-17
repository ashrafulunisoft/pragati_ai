@extends('layouts.admin')

@section('title', 'Video Call Admin Dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-video"></i> Video Call Dashboard</h2>
        <div class="btn-group">
            <a href="?period=today" class="btn btn-outline-primary {{ $period === 'today' ? 'active' : '' }}">Today</a>
            <a href="?period=week" class="btn btn-outline-primary {{ $period === 'week' ? 'active' : '' }}">This Week</a>
            <a href="?period=month" class="btn btn-outline-primary {{ $period === 'month' ? 'active' : '' }}">This Month</a>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-primary">{{ $todayCalls }}</h3>
                    <p class="text-muted">Total Calls</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-success">{{ $activeCalls }}</h3>
                    <p class="text-muted">Active Calls</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-warning">{{ $waitingQueue }}</h3>
                    <p class="text-muted">In Queue</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-info">{{ $freeAgents }}/{{ $totalAgents }}</h3>
                    <p class="text-muted">Free Agents</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Metrics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-clock"></i> Call Duration</h5>
                </div>
                <div class="card-body">
                    <h2 class="text-primary">{{ $metrics->formatted_duration ?? '00:00:00' }}</h2>
                    <p class="text-muted">Total Duration</p>
                    <hr>
                    <p>Avg Duration: <strong>{{ gmdate('i:s', $metrics->total_duration / max($metrics->total_calls, 1) ?? 0) }}</strong></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-hourglass-half"></i> Wait Time</h5>
                </div>
                <div class="card-body">
                    <h2 class="text-warning">{{ $metrics->formatted_wait_time ?? '0s' }}</h2>
                    <p class="text-muted">Average Wait Time</p>
                    <hr>
                    <p>Total Wait: <strong>{{ gmdate('i:s', $metrics->total_wait_time ?? 0) }}</strong></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-star"></i> Customer Satisfaction</h5>
                </div>
                <div class="card-body">
                    <h2 class="text-success">{{ number_format($metrics->average_rating ?? 0, 1) }} / 5</h2>
                    <p class="text-muted">Average Rating</p>
                    <hr>
                    <div class="d-flex justify-content-center">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star {{ $i <= round($metrics->average_rating ?? 0) ? 'text-warning' : 'text-secondary' }}"></i>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Agent Status -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-users"></i> Agent Status</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Calls Today</th>
                            <th>Total Duration</th>
                            <th>Avg Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(\App\Models\Agent::all() as $agent)
                        <tr>
                            <td>{{ $agent->name }}</td>
                            <td>{{ $agent->department ?? 'General' }}</td>
                            <td>
                                <span class="badge badge-{{ $agent->status === 'free' ? 'success' : ($agent->status === 'busy' ? 'danger' : 'secondary') }}">
                                    {{ ucfirst($agent->status) }}
                                </span>
                            </td>
                            <td>{{ $agent->total_calls }}</td>
                            <td>{{ gmdate('H:i:s', $agent->total_duration) }}</td>
                            <td>{{ number_format($agent->average_rating, 1) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Calls -->
    <div class="card mt-4">
        <div class="card-header">
            <h5><i class="fas fa-history"></i> Recent Calls</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Started</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Agent</th>
                            <th>Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(\App\Models\CallSession::latest()->take(10)->get() as $session)
                        <tr>
                            <td>{{ $session->started_at->format('d M Y, h:i A') }}</td>
                            <td>{{ gmdate('i:s', $session->duration) }}</td>
                            <td>
                                <span class="badge badge-{{ $session->status === 'ended' ? 'success' : 'warning' }}">
                                    {{ ucfirst($session->status) }}
                                </span>
                            </td>
                            <td>{{ $session->agent->name ?? 'N/A' }}</td>
                            <td>
                                @if($session->feedback)
                                    {{ $session->feedback->rating }} / 5
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
