@extends('layouts.receptionist')

@section('title', 'My Profile - UCB Bank')

@section('content')
    <!-- Header -->
    <div class="header-section">
        <div>
            <h3 class="fw-800 mb-1 text-white letter-spacing-1">My Profile</h3>
            <p class="sub-label mb-0">Manage your account information</p>
        </div>
        <div class="header-profile-box glass-card">
            <div class="avatar bg-primary">
                <i class="fas fa-user text-white small"></i>
            </div>
            <div>
                <p class="small fw-800 mb-0 text-white">{{ Auth::user()->name }}</p>
                <span class="sub-label fs-9">{{ ucfirst(auth()->user()->getRoleNames()->first()) ?? 'User' }}</span>
            </div>
        </div>
    </div>

    <!-- Profile Content -->
    <div class="row g-4">
        <!-- User Information -->
        <div class="col-lg-6">
            <div class="glass-card p-4">
                <h6 class="fw-800 sub-label mb-4">
                    <i class="fas fa-user-circle me-2"></i> Personal Information
                </h6>
                <form action="#" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-white">Full Name</label>
                            <input type="text"
                                   class="input-dark"
                                   value="{{ Auth::user()->name }}"
                                   readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white">Email Address</label>
                            <input type="email"
                                   class="input-dark"
                                   value="{{ Auth::user()->email }}"
                                   readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white">Role</label>
                            <input type="text"
                                   class="input-dark"
                                   value="{{ ucfirst(auth()->user()->getRoleNames()->first()) ?? 'User' }}"
                                   readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white">Member Since</label>
                            <input type="text"
                                   class="input-dark"
                                   value="{{ Auth::user()->created_at->format('F j, Y') }}"
                                   readonly>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Permissions -->
        <div class="col-lg-6">
            <div class="glass-card p-4">
                <h6 class="fw-800 sub-label mb-4">
                    <i class="fas fa-shield-alt me-2"></i> Your Permissions
                </h6>

                @if(auth()->user()->getAllPermissions()->count() > 0)
                    <div class="row g-2">
                        @foreach(auth()->user()->getAllPermissions() as $permission)
                        <div class="col-6">
                            <div class="checkbox-label">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-check-circle" style="color: var(--accent-emerald);"></i>
                                <span class="small fw-600 text-white">{{ ucfirst($permission->name) }}</span>
                            </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-circle text-warning fs-2 mb-3"></i>
                        <p class="text-white-50 small">No specific permissions assigned. Contact administrator.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Change Password -->
        <div class="col-12">
            <div class="glass-card p-4">
                <h6 class="fw-800 sub-label mb-4">
                    <i class="fas fa-lock me-2"></i> Change Password
                </h6>
                <form action="{{ route('profile.send-reset-email') }}" method="POST">
                    @csrf
                    <div class="row g-3 ">
                        <div class="col-md-6">
                            <label class="form-label text-white">Current Password</label>
                            <div class="position-relative">
                                <input type="password"
                                       class="input-dark"
                                       placeholder="Enter current password"
                                       required>
                                <i class="fas fa-key input-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white">New Password</label>
                            <div class="position-relative">
                                <input type="password"
                                       class="input-dark"
                                       placeholder="Enter new password"
                                       required>
                                <i class="fas fa-lock input-icon"></i>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white">Confirm New Password</label>
                            <div class="position-relative">
                                <input type="password"
                                       class="input-dark"
                                       placeholder="Confirm new password"
                                       required>
                                <i class="fas fa-lock input-icon"></i>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn-gradient">
                                    Update Password
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Account Settings -->
        <div class="col-12">
            <div class="glass-card p-4">
                <h6 class="fw-800 sub-label mb-4">
                    <i class="fas fa-cog me-2"></i> Account Settings
                </h6>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="checkbox-label">
                            <div class="d-flex align-items-center gap-2">
                                <input type="checkbox" class="checkbox-custom" id="email-notifications" checked>
                                <label for="email-notifications" class="mb-0 small">Email Notifications</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="checkbox-label">
                            <div class="d-flex align-items-center gap-2">
                                <input type="checkbox" class="checkbox-custom" id="sms-notifications" checked>
                                <label for="sms-notifications" class="mb-0 small">SMS Notifications</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="checkbox-label">
                            <div class="d-flex align-items-center gap-2">
                                <input type="checkbox" class="checkbox-custom" id="two-factor" unchecked>
                                <label for="two-factor" class="mb-0 small">Enable Two-Factor Authentication</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-4 mt-4">
        <div class="col-12">
            <div class="glass-card p-4">
                <h6 class="fw-800 sub-label mb-4">
                    <i class="fas fa-chart-pie me-2"></i> Activity Summary
                </h6>
                <div class="row g-3">
                    @can('view visitors')
                    <div class="col-6 col-md-3">
                        <div class="text-center">
                            <h3 class="fw-800 mb-1" style="color: var(--accent-emerald);">
                                {{ App\Models\Visit::where('meeting_user_id', Auth::id())->count() }}
                            </h3>
                            <p class="sub-label mb-0">Total Visits Hosted</p>
                        </div>
                    </div>
                    @endcan
                    <div class="col-6 col-md-3">
                        <div class="text-center">
                            <h3 class="fw-800 mb-1" style="color: var(--accent-emerald);">
                                {{ App\Models\Visit::where('status', 'completed')->where('meeting_user_id', Auth::id())->count() }}
                            </h3>
                            <p class="sub-label mb-0">Completed Visits</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-center">
                            <h3 class="fw-800 mb-1" style="color: var(--accent-amber);">
                                {{ App\Models\Visit::where('status', 'pending')->where('meeting_user_id', Auth::id())->count() }}
                            </h3>
                            <p class="sub-label mb-0">Pending Requests</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-center">
                            <h3 class="fw-800 mb-1" style="color: var(--accent-teal);">
                                {{ App\Models\Visit::whereDate('schedule_time', today())->where('meeting_user_id', Auth::id())->count() }}
                            </h3>
                            <p class="sub-label mb-0">Today's Visits</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
