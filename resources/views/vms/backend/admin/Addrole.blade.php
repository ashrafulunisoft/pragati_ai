@extends('layouts.admin')

@section('content')
<div class="role-container">
    <div class="glass-card glass-card-dark">
        <div style="text-align: center; margin-bottom: 3rem;">
            <h1 style="font-weight: 800; font-size: 2.25rem; letter-spacing: -1px; margin-bottom: 0.5rem; color: white; text-shadow: 0 0 15px rgba(255, 255, 255, 0.2);">Add New Role</h1>
            <p class="permission-title" style="justify-content: center; margin-bottom: 0; text-shadow: 0 0 10px rgba(59, 130, 246, 0.5);">Define permissions and access levels</p>
        </div>

        <form action="{{ route('admin.role.store') }}" method="POST">
            @csrf

            <!-- Role Name -->
            <div style="position: relative; margin-bottom: 2rem;">
                <label class="form-label">
                    Role Name <span style="color: #ef4444;">*</span>
                </label>
                <input type="text"
                       name="role_name"
                       class="input-dark input-custom @error('role_name') is-invalid @enderror"
                       value="{{ old('role_name') }}"
                       placeholder="e.g. System Administrator"
                       required>
            </div>

            <!-- Role Description -->
            <div style="position: relative; margin-bottom: 2rem;">
                <label class="form-label">Role Description</label>
                <textarea name="description"
                          class="input-dark input-custom"
                          rows="3"
                          placeholder="Briefly describe the responsibilities..."
                          style="resize: none;">{{ old('description') }}</textarea>
            </div>

            <!-- Status -->
            <div style="position: relative; margin-bottom: 2rem;">
                <label class="form-label">Status</label>
                <select name="status" class="input-dark input-custom" style="appearance: none; background-image: url('data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='white'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E'); background-repeat: no-repeat; background-position: right 1.25rem center; background-size: 1rem; cursor: pointer;">
                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="restricted" {{ old('status') == 'restricted' ? 'selected' : '' }}>Restricted Access</option>
                </select>
            </div>

            <!-- Permissions -->
            <span class="permission-title">Granular Permissions</span>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 3rem;">
                <label class="checkbox-label">
                    <input type="checkbox"
                           name="permissions[]"
                           value="dashboard"
                           class="checkbox-custom"
                           {{ in_array('dashboard', old('permissions', [])) ? 'checked' : '' }}>
                    <span style="font-size: 0.9rem; font-weight: 600; cursor: pointer; margin: 0; color: rgba(255, 255, 255, 0.9);">View Dashboard</span>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox"
                           name="permissions[]"
                           value="users"
                           class="checkbox-custom"
                           {{ in_array('users', old('permissions', [])) ? 'checked' : '' }}>
                    <span style="font-size: 0.9rem; font-weight: 600; cursor: pointer; margin: 0; color: rgba(255, 255, 255, 0.9);">Manage Users</span>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox"
                           name="permissions[]"
                           value="roles"
                           class="checkbox-custom"
                           {{ in_array('roles', old('permissions', [])) ? 'checked' : '' }}>
                    <span style="font-size: 0.9rem; font-weight: 600; cursor: pointer; margin: 0; color: rgba(255, 255, 255, 0.9);">Manage Roles</span>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox"
                           name="permissions[]"
                           value="reports"
                           class="checkbox-custom"
                           {{ in_array('reports', old('permissions', [])) ? 'checked' : '' }}>
                    <span style="font-size: 0.9rem; font-weight: 600; cursor: pointer; margin: 0; color: rgba(255, 255, 255, 0.9);">View Reports</span>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox"
                           name="permissions[]"
                           value="audit"
                           class="checkbox-custom"
                           {{ in_array('audit', old('permissions', [])) ? 'checked' : '' }}>
                    <span style="font-size: 0.9rem; font-weight: 600; cursor: pointer; margin: 0; color: rgba(255, 255, 255, 0.9);">Audit Logs</span>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox"
                           name="permissions[]"
                           value="settings"
                           class="checkbox-custom"
                           {{ in_array('settings', old('permissions', [])) ? 'checked' : '' }}>
                    <span style="font-size: 0.9rem; font-weight: 600; cursor: pointer; margin: 0; color: rgba(255, 255, 255, 0.9);">System Settings</span>
                </label>
            </div>

            <!-- Actions -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                <button type="submit" class="btn-gradient btn-create">
                    <i class="fas fa-plus-circle me-2"></i> Create Role
                </button>
                <a href="{{ route('admin.dashboard') }}" class="btn-outline btn-reset">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <script>
        @if(session('success'))
            Swal.fire({
                title: 'Success!',
                text: "{{ session('success') }}",
                icon: 'success',
                confirmButtonText: 'OK',
                confirmButtonColor: '#3b82f6',
                timer: 2000,
                timerProgressBar: true,
                showCloseButton: true,
                closeButtonAriaLabel: 'Close this alert'
            });
        @endif

        @if($errors->any())
            Swal.fire({
                title: 'Error!',
                text: "{{ $errors->first() }}",
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#ef4444',
                showCloseButton: true,
                closeButtonAriaLabel: 'Close this alert'
            });
        @endif
    </script>
@endpush
@endsection
