# Admin User Role Fix - 403 Error Resolution

## Issue
The admin user `amshuvo64@gmail.com` was receiving a **403 Forbidden** error when trying to access admin pages. The error message indicated: "User does not have the right roles."

## Root Cause
The user had the role **"ceo"** assigned instead of **"admin"**. Since the application's routes and middleware are configured to check for the "admin" role, the user was being denied access.

### Before Fix
```
User: Admin (amshuvo64@gmail.com)
Role: ceo ❌
Permissions: view visitors, create visitors, edit visitors, delete visitors, create visit, verify visit otp, approve visit, reject visit, checkin visit, checkout visit, view live dashboard
```

## Solution
Assigned the correct **"admin"** role to the user using the following command:

```bash
php artisan tinker --execute="
\$user = App\Models\User::where('email', 'amshuvo64@gmail.com')->first();
\$user->syncRoles('admin');
"
```

### After Fix
```
User: Admin (amshuvo64@gmail.com)
Role: admin ✅
Direct Permissions: view visitors, create visitors, edit visitors, delete visitors, create visit, verify visit otp, approve visit, reject visit, checkin visit, checkout visit, view live dashboard
Role Permissions: view visitors, create visit, verify visit otp, approve visit, reject visit, checkin visit, checkout visit, view live dashboard
All Permissions: view visitors, create visitors, edit visitors, delete visitors, create visit, verify visit otp, approve visit, reject visit, checkin visit, checkout visit, view live dashboard
```

## Additional Steps
Cleared all caches to ensure the role changes take effect immediately:

```bash
php artisan cache:clear
php artisan config:clear
php artisan permission:cache-reset
```

## Role Configuration Reference

### Available Roles (from PermissionSeeder)
1. **admin** - All permissions
2. **receptionist** - View, Create, Verify OTP, Check-in/out, Live Dashboard
3. **staff** - View, Approve/Reject, Live Dashboard
4. **visitor** - View only

### Middleware Configuration
The application uses Spatie Permission middleware:
- `role:admin` - Checks if user has admin role
- `permission:view visitors` - Checks if user has specific permission
- `role.redirect` - Custom middleware to redirect users based on role

### Route Protection
Admin routes are protected with:
```php
Route::middleware(['auth', 'role:admin'])->group(function () {
    // All admin routes here
});
```

## Verification
To verify the fix worked, the admin user should now be able to:
- Access `/admin/dashboard` without 403 error
- Use all admin functionality (visitor management, approvals, check-in/out, etc.)
- View live dashboard
- Manage roles and permissions

## Prevention
To prevent similar issues in the future:
1. Always use the correct role names as defined in `PermissionSeeder.php`
2. When creating admin users, assign the "admin" role explicitly
3. Test role assignments immediately after creating users
4. Consider adding validation in the user creation process to ensure correct role assignment

## Related Files
- `database/seeders/PermissionSeeder.php` - Role and permission definitions
- `app/Models/User.php` - User model with HasRoles trait
- `app/Http/Middleware/RedirectUserByRole.php` - Role-based redirection logic
- `routes/web.php` - Route definitions with role middleware
- `bootstrap/app.php` - Middleware registration

## Date Fixed
January 25, 2026
