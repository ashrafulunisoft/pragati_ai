<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Profile - UCB Bank</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --bg-page: linear-gradient(135deg, #0bd696 0%, #0d5540 100%);
            --sidebar-bg: #0a3d2a;
            --accent-green: #0bd696;
            --accent-dark-green: #0d5540;
            --text-muted: #a8e6cf;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-page);
            background-attachment: fixed;
            min-height: 100vh;
            color: #fff;
            margin: 0;
        }

        .navbar-custom {
            background: rgba(10, 61, 42, 0.9) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(11, 214, 150, 0.3);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 800;
            color: #fff !important;
            font-size: 1.3rem;
        }

        .navbar-brand i {
            color: var(--accent-green);
        }

        .user-name {
            color: var(--accent-green);
            font-weight: 600;
        }

        .btn-logout {
            background: transparent;
            border: 1px solid rgba(11, 214, 150, 0.5);
            color: var(--accent-green);
            border-radius: 100px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background: var(--accent-green);
            color: #0d5540;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(35px);
            -webkit-backdrop-filter: blur(35px);
            border: 1px solid rgba(11, 214, 150, 0.3);
            border-radius: 24px;
            box-shadow: 0 30px 100px rgba(0, 0, 0, 0.6), inset 0 0 20px rgba(11, 214, 150, 0.1);
        }

        .profile-card .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(11, 214, 150, 0.2);
            padding: 1.5rem 2rem;
        }

        .profile-card .card-header h4 {
            font-weight: 700;
            margin: 0;
        }

        .profile-card .card-body {
            padding: 2rem;
        }

        .profile-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .profile-icon {
            background: linear-gradient(135deg, var(--accent-green), var(--accent-dark-green));
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 10px 30px rgba(11, 214, 150, 0.4);
        }

        .profile-label {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 0.25rem;
        }

        .profile-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
        }

        .divider {
            height: 1px;
            background: rgba(11, 214, 150, 0.2);
            margin: 1.5rem 0;
        }

        .btn-action {
            background: linear-gradient(135deg, var(--accent-green), var(--accent-dark-green));
            border: none;
            border-radius: 100px;
            padding: 12px 24px;
            color: white;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.3s;
            box-shadow: 0 0 20px rgba(11, 214, 150, 0.4);
        }

        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(11, 214, 150, 0.5);
            filter: brightness(1.1);
            color: white;
        }

        .btn-action-outline {
            background: transparent;
            border: 2px solid var(--accent-green);
            color: var(--accent-green);
            border-radius: 100px;
            padding: 10px 22px;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .btn-action-outline:hover {
            background: var(--accent-green);
            color: #0d5540;
        }

        .alert-custom {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-radius: 16px;
            padding: 1rem 1.2rem;
            color: #4ade80;
            backdrop-filter: blur(10px);
        }

        .alert-custom i {
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            .profile-card {
                margin: 1rem;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-university me-2"></i>UCB Bank VMS
            </a>

            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="user-name">
                    <i class="fas fa-user-circle me-2"></i>
                    {{ auth()->user()->name }}
                </span>

                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button class="btn btn-logout btn-sm" type="submit">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="profile-card">
                    <div class="card-header">
                        <h4>
                            <i class="fas fa-user-circle me-2"></i>
                            User Profile
                        </h4>
                    </div>

                    <div class="card-body">
                        <!-- Name -->
                        <div class="profile-info">
                            <div class="profile-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <div class="profile-label">Full Name</div>
                                <div class="profile-value">{{ auth()->user()->name }}</div>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="profile-info">
                            <div class="profile-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <div class="profile-label">Email Address</div>
                                <div class="profile-value">{{ auth()->user()->email }}</div>
                            </div>
                        </div>

                        <div class="divider"></div>

                        <!-- Actions -->
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="#" class="btn btn-action">
                                <i class="fas fa-edit me-2"></i> Edit Profile
                            </a>

                            <!-- Reset Password Form -->
                            <form method="POST" action="{{ route('profile.send-reset-email') }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="email" value="{{ auth()->user()->email }}">
                                <button type="submit" class="btn btn-action-outline">
                                    <i class="fas fa-key me-2"></i> Reset Password
                                </button>
                            </form>
                        </div>

                        @if (session('status'))
                            <div class="alert alert-custom mt-4">
                                <i class="fas fa-check-circle"></i>
                                {{ session('status') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
