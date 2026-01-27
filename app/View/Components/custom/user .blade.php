<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">

    <!-- 🔷 PAGE TITLE -->
    <div class="row mb-4">
        <div class="col text-center">
            <h2 class="fw-bold">User Management System</h2>
            <p class="text-muted">Route → Controller → View Diagram</p>
        </div>
    </div>

    <!-- 🔷 DIAGRAM STYLE CARD -->
    <div class="row mb-4 text-center">
        <div class="col-md-4">
            <div class="card shadow-sm border-primary">
                <div class="card-body">
                    <h5 class="text-primary">Route</h5>
                    <p class="mb-0">/users</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-success">
                <div class="card-body">
                    <h5 class="text-success">Controller</h5>
                    <p class="mb-0">UserController@index</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-warning">
                <div class="card-body">
                    <h5 class="text-warning">View</h5>
                    <p class="mb-0">user.blade.php</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 🔷 USER LIST CARD -->
    <div class="card shadow">
        <div class="card-header bg-dark text-white d-flex justify-content-between">
            <span>User List</span>
            <a href="#" class="btn btn-sm btn-primary">+ Add User</a>
        </div>

        <div class="card-body">
            <table class="table table-bordered table-hover text-center">
                <thead class="table-secondary">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <!-- Demo Static Data -->
                    <tr>
                        <td>1</td>
                        <td>Sharna Rani Das</td>
                        <td>sharna@email.com</td>
                        <td>
                            <button class="btn btn-sm btn-warning">Edit</button>
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </td>
                    </tr>

                    <tr>
                        <td>2</td>
                        <td>Test User</td>
                        <td>test@email.com</td>
                        <td>
                            <button class="btn btn-sm btn-warning">Edit</button>
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>
