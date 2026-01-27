<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">My App</a>

        <div class="ms-auto d-flex align-items-center">
            <span class="text-white me-3">
                {{ auth()->user()->name }}
            </span>

            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button class="btn btn-outline-light btn-sm">Logout</button>
            </form>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            Profile
        </div>

        <div class="card-body">
            <p><strong>Name:</strong> {{ auth()->user()->name }}</p>
            <p><strong>Email:</strong> {{ auth()->user()->email }}</p>

            <hr>

            <div class="d-flex gap-2">
                <a href="#" class="btn btn-success">
                    Edit Profile
                </a>

                <!-- Reset Password Form -->
                <form method="POST" action="{{ route('profile.send-reset-email') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="email" value="{{ auth()->user()->email }}">
                    <button type="submit" class="btn btn-warning">
                        Reset Password
                    </button>
                </form>
            </div>

            @if (session('status'))
                <div class="alert alert-info mt-3">
                    {{ session('status') }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
