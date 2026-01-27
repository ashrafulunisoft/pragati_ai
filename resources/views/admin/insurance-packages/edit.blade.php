@extends('layouts.admin')

@section('title', 'Edit Insurance Package')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Insurance Package</h3>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('insurance-packages.update', $insurancePackage->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="name">Package Name *</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $insurancePackage->name) }}" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $insurancePackage->description) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label for="price">Price ($) *</label>
                            <input type="number" name="price" id="price" class="form-control" value="{{ old('price', $insurancePackage->price) }}" step="0.01" min="0" required>
                        </div>

                        <div class="form-group">
                            <label for="coverage_amount">Coverage Amount ($) *</label>
                            <input type="number" name="coverage_amount" id="coverage_amount" class="form-control" value="{{ old('coverage_amount', $insurancePackage->coverage_amount) }}" step="0.01" min="0" required>
                        </div>

                        <div class="form-group">
                            <label for="duration_months">Duration (Months) *</label>
                            <input type="number" name="duration_months" id="duration_months" class="form-control" value="{{ old('duration_months', $insurancePackage->duration_months) }}" min="1" required>
                            <small class="text-muted">Enter 6, 12, 24, etc.</small>
                        </div>

                        <div class="form-group">
                            <label for="is_active">
                                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $insurancePackage->is_active) ? 'checked' : '' }}>
                                Active
                            </label>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Update Package</button>
                            <a href="{{ route('insurance-packages.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
