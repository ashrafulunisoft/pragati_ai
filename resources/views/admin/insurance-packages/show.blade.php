@extends('layouts.admin')

@section('title', 'Insurance Package Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Insurance Package Details</h3>
                    <div class="card-tools">
                        <a href="{{ route('insurance-packages.edit', $insurancePackage->id) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('insurance-packages.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">ID</th>
                            <td>{{ $insurancePackage->id }}</td>
                        </tr>
                        <tr>
                            <th>Name</th>
                            <td>{{ $insurancePackage->name }}</td>
                        </tr>
                        <tr>
                            <th>Description</th>
                            <td>{{ $insurancePackage->description ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Price</th>
                            <td>${{ number_format($insurancePackage->price, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Coverage Amount</th>
                            <td>${{ number_format($insurancePackage->coverage_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Duration</th>
                            <td>{{ $insurancePackage->duration_months }} Months</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                @if($insurancePackage->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Created At</th>
                            <td>{{ $insurancePackage->created_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                        <tr>
                            <th>Updated At</th>
                            <td>{{ $insurancePackage->updated_at->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
