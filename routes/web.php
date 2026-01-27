<?php

use App\Models\User;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\InsurancePackageController;

//for create role and permission :
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\VisitorExportController;






/*
|--------------------------------------------------------------------------
| Those use for role base redirection .
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', function () {
    "This is the dashboard page for no roles";
    return view('dashboard'); // dummy view (never actually shown)
})->middleware(['auth', 'verified', 'role.redirect'])
  ->name('dashboard');




/*
|--------------------------------------------------------------------------
| Role-wise Dashboards
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Admin Dashboard
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    // Admin Profile
    Route::get('/admin/profile', [AdminController::class, 'profile'])->name('admin.profile');

    // Admin Live Dashboard
    Route::get('/admin/live-dashboard', [AdminController::class, 'liveDashboard'])->name('admin.live.dashboard');
    Route::get('/api/admin/visitors/live', [AdminController::class, 'liveVisitsApi'])->name('api.admin.visitors.live');

    // Admin Visitor Management Routes
    // Static routes MUST come before dynamic routes
    Route::get('/admin/visitor/pending', [AdminController::class, 'pendingVisits'])->name('admin.visitor.pending');
    Route::get('/admin/visitor/rejected', [AdminController::class, 'rejectedVisits'])->name('admin.visitor.rejected');
    Route::get('/admin/visitor/approved', [AdminController::class, 'approvedVisits'])->name('admin.visitor.approved');
    Route::get('/admin/visitor/history', [AdminController::class, 'visitHistory'])->name('admin.visitor.history');
    Route::get('/admin/visitor/active', [AdminController::class, 'activeVisits'])->name('admin.visitor.active');
    Route::get('/admin/visitor/checkin-checkout', [AdminController::class, 'checkinCheckout'])->name('admin.visitor.checkin-checkout');

    // API routes for admin
    Route::get('/admin/visitor/autofill', [AdminController::class, 'autofill'])->name('admin.visitor.autofill');
    Route::get('/admin/visitor/check-email', [AdminController::class, 'checkVisitorByEmail'])->name('admin.visitor.check-email');
    Route::get('/admin/visitor/check-email', [AdminController::class, 'checkVisitorByEmail'])->name('admin.visitor.registration.check-visitor');
    Route::get('/admin/visitor/check-phone', [AdminController::class, 'checkVisitorByPhone'])->name('admin.visitor.check-phone');
    Route::get('/admin/visitor/check-phone', [AdminController::class, 'checkVisitorByPhone'])->name('admin.visitor.registration.check-visitor-phone');
    Route::get('/admin/visitor/search-host', [AdminController::class, 'searchHost'])->name('admin.visitor.search-host');
    Route::get('/admin/visitor/search-host', [AdminController::class, 'searchHost'])->name('admin.visitor.registration.search-host');
    Route::get('/admin/visitor/statistics', [AdminController::class, 'statistics'])->name('admin.visitor.statistics');

    // CRUD routes (dynamic routes MUST come last)
    Route::get('/admin/visitor', [AdminController::class, 'visitorList'])->name('admin.visitor.index');
    Route::get('/admin/visitor', [AdminController::class, 'visitorList'])->name('admin.visitor.list');
    Route::get('/admin/visitor/create', [AdminController::class, 'createVisitorRegistration'])->name('admin.visitor.create');
    Route::get('/admin/visitor/create', [AdminController::class, 'createVisitorRegistration'])->name('admin.visitor.registration.create');
    Route::post('/admin/visitor', [AdminController::class, 'storeVisitorRegistration'])->name('admin.visitor.store');
    Route::post('/admin/visitor', [AdminController::class, 'storeVisitorRegistration'])->name('admin.visitor.registration.store');
    Route::get('/admin/visitor/{id}', [AdminController::class, 'showVisitor'])->name('admin.visitor.show');
    Route::get('/admin/visitor/{id}/edit', [AdminController::class, 'editVisitor'])->name('admin.visitor.edit');
    Route::post('/admin/visitor/{id}/update', [AdminController::class, 'updateVisitor'])->name('admin.visitor.update');
    Route::delete('/admin/visitor/{id}', [AdminController::class, 'deleteVisitor'])->name('admin.visitor.destroy');

    // OTP Verification Routes
    Route::get('/admin/visitor/{id}/verify-otp', [AdminController::class, 'showVerifyOtp'])->name('admin.visitor.verify.otp.view');
    Route::post('/admin/visitor/verify-otp/{id}', [AdminController::class, 'verifyOtp'])->name('admin.visitor.verify.otp');

    // Host Approval Routes
    Route::post('/admin/visits/{id}/approve', [AdminController::class, 'approveVisit'])->name('admin.visit.approve');
    Route::post('/admin/visits/{id}/reject', [AdminController::class, 'rejectVisit'])->name('admin.visit.reject');

    // Check-in/Check-out Routes
    Route::post('/admin/visits/{id}/check-in', [AdminController::class, 'checkIn'])->name('admin.visit.checkin');
    Route::post('/admin/visits/{id}/check-out', [AdminController::class, 'checkOut'])->name('admin.visit.checkout');

    // Admin Role Management Routes
    Route::get('/admin/role/create', [AdminController::class, 'createRole'])->name('admin.role.create');
    Route::post('/admin/role/store', [AdminController::class, 'storeRole'])->name('admin.role.store');
    Route::get('/admin/role/assign/create', [AdminController::class, 'createAssignRole'])->name('admin.role.assign.create');
    Route::post('/admin/role/assign/store', [AdminController::class, 'storeAssignRole'])->name('admin.role.assign.store');
    Route::post('/admin/role/assign/remove', [AdminController::class, 'removeUserRole'])->name('admin.role.assign.remove');

    // Insurance Package CRUD Routes
    Route::resource('admin/insurance-packages', InsurancePackageController::class);
    Route::post('admin/insurance-packages/{insurancePackage}/toggle-status', [InsurancePackageController::class, 'toggleStatus'])->name('admin.insurance-packages.toggle-status');
});

/*
|--------------------------------------------------------------------------
| Role-wise Dashboards (Receptionist, Staff, Visitor all use same controller)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:receptionist|staff|visitor'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Visitor\VisitorController::class, 'dashboard'])
        ->name('dashboard');
});



/*
|--------------------------------------------------------------------------
| Guest pages (guest only)
|--------------------------------------------------------------------------
*/


Route::get('/', function(){


// Role::create(['name' => 'admin']);
// Role::create(['name' => 'staff']);
// Role::create(['name' => 'receptionist']);
// Role::create(['name' => 'visitor']);

// dd(Role::all());
//---------- add role to any user --------------------------
    // $user = User::where('name','Staff')->first();
    // // dd($user);
    // $user->assignRole('staff');
    // dd($user->getRoleNames());
    // $user->removeRole('staff');

    // dd($user->getRoleNames());

//---------- add permission to any user ------------------------
// use Spatie\Permission\Models\Permission;
    // $user = User::latest()->first();
    // $user->givePermissionTo('create users');

    return view('home');
})->name('home');



/*
|--------------------------------------------------------------------------
| Auth pages (guest only)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);


});

// Public Live Dashboard Routes (No authentication required)
Route::get('/public/live-dashboard', [App\Http\Controllers\Visitor\VisitorController::class, 'liveDashboardPublic'])
    ->name('visitor.live.public');

Route::get('/api/visitors/live-public', [App\Http\Controllers\Visitor\VisitorController::class, 'liveVisitorsApiPublic'])
    ->name('api.visitors.live.public');


/*
|--------------------------------------------------------------------------
| Password Reset (ALLOW AUTH + GUEST)
|--------------------------------------------------------------------------
*/
Route::get('/forgot-password', [PasswordResetController::class, 'request'])
    ->name('password.request');

Route::post('/forgot-password-email', [PasswordResetController::class, 'email'])
    ->name('password.email');

Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])
    ->name('password.reset');

Route::post('/reset-password', [PasswordResetController::class, 'update'])
    ->name('password.update');

// Send reset email to currently authenticated user
Route::post('/profile/send-reset-email', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
    ]);

    $status = Password::sendResetLink($request->only('email'));

    return back()->with('status', $status === Password::RESET_LINK_SENT
        ? __($status)
        : __('Failed to send reset link. ' . __($status)));
})->name('profile.send-reset-email');


/*
|--------------------------------------------------------------------------
| Authenticated pages
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

// Test email route
Route::get('/test-mail', function() {
    try {
        Mail::raw('This is a test email from Laravel', function($message) {
            $message->to('ashrafulunisoft@gmail.com')
                    ->subject('Test Email');
        });
        return 'Email sent successfully! Check your inbox.';
    } catch (\Exception $e) {
        return 'Error sending email: ' . $e->getMessage();
    }
});

// Visitor CSV Export Routes (Public for testing)
Route::get('/visitors/export/preview', [VisitorExportController::class, 'previewVisitorData'])
    ->name('visitors.export.preview');

Route::get('/visitors/export/send', [VisitorExportController::class, 'sendVisitorCsv'])
    ->name('visitors.export.send');


//---------------------------------------------------------------------------

Route::get('/logout', function () {
    Auth::logout();
    return redirect('/login');
});




// Test notification route
Route::get('/test-notification', function () {
    $visitor = \App\Models\Visitor::first();

    if (!$visitor) {
        return 'No visitor found in database. Create a visitor first.';
    }

    // Test email
    try {
        $visitor->notify(new \App\Notifications\VisitorRegistered($visitor, $visitor->visits()->first()));
        return 'Email notification sent successfully! Check your inbox.';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
})->name('test.notification');

// Test visitor registration email with EmailNotificationService
Route::get('/test-visitor-email', function () {
    $emailService = new \App\Services\EmailNotificationService();

    $emailData = [
        'visitor_name' => 'Test Visitor',
        'visitor_email' => 'ashrafulunisoft@gmail.com',
        'visitor_phone' => '+8801234567890',
        'visitor_company' => 'Test Company',
        'visit_date' => 'January 25, 2026 - 2:30 PM',
        'visit_type' => 'Business Meeting',
        'purpose' => 'Testing email notification service',
        'host_name' => 'Test Host',
        'status' => 'approved',
    ];

    try {
        $result = $emailService->sendVisitorRegistrationEmail($emailData);
        return $result
            ? '✅ Visitor registration email sent successfully! Check ashrafulunisoft@gmail.com'
            : '❌ Failed to send email. Check logs for details.';
    } catch (\Exception $e) {
        return '❌ Error: ' . $e->getMessage();
    }
})->name('test.visitor.email');

// -------------------------------------------------------------------------
// Visitor Management Routes (with permission middleware)
Route::middleware(['auth'])->group(function () {
    // Specific static routes MUST come before dynamic routes
    Route::get('/visitor/pending', [App\Http\Controllers\Visitor\VisitorController::class, 'pendingVisits'])->name('visitor.pending');
    Route::get('/visitor/rejected', [App\Http\Controllers\Visitor\VisitorController::class, 'rejectedVisits'])->name('visitor.rejected');
    Route::get('/visitor/approved', [App\Http\Controllers\Visitor\VisitorController::class, 'approvedVisits'])->name('visitor.approved');
    Route::get('/visitor/history', [App\Http\Controllers\Visitor\VisitorController::class, 'visitHistory'])->name('visitor.history');
    Route::get('/visitor/active', [App\Http\Controllers\Visitor\VisitorController::class, 'activeVisits'])->name('visitor.active');
    Route::get('/visitor/checkin-checkout', [App\Http\Controllers\Visitor\VisitorController::class, 'checkinCheckout'])->name('visitor.checkin-checkout');

    // API routes
    Route::get('/visitor/autofill', [App\Http\Controllers\Visitor\VisitorController::class, 'autofill'])->name('visitor.autofill');
    Route::get('/visitor/check-email', [App\Http\Controllers\Visitor\VisitorController::class, 'checkVisitorByEmail'])->name('visitor.check-email');
    Route::get('/visitor/check-phone', [App\Http\Controllers\Visitor\VisitorController::class, 'checkVisitorByPhone'])->name('visitor.check-phone');
    Route::get('/visitor/search-host', [App\Http\Controllers\Visitor\VisitorController::class, 'searchHost'])->name('visitor.search-host');
    Route::get('/visitor/search-phone', [App\Http\Controllers\Visitor\VisitorController::class, 'searchVisitorByPhone'])->name('visitor.search-phone');
    Route::get('/visitor/statistics', [App\Http\Controllers\Visitor\VisitorController::class, 'statistics'])->name('visitor.statistics');
    Route::get('/visitor/report', [App\Http\Controllers\Visitor\VisitorController::class, 'report'])->name('visitor.report');
    Route::get('/visitor/report/export-csv', [App\Http\Controllers\Visitor\VisitorController::class, 'exportReportCsv'])->name('visitor.report.export-csv');

    // CRUD routes (dynamic routes MUST come last)
    Route::get('/visitor', [App\Http\Controllers\Visitor\VisitorController::class, 'index'])->name('visitor.index');
    Route::get('/visitor/create', [App\Http\Controllers\Visitor\VisitorController::class, 'create'])->name('visitor.create');
    Route::post('/visitor', [App\Http\Controllers\Visitor\VisitorController::class, 'store'])->name('visitor.store');
    Route::get('/visitor/{id}', [App\Http\Controllers\Visitor\VisitorController::class, 'show'])->name('visitor.show');
    Route::get('/visitor/{id}/edit', [App\Http\Controllers\Visitor\VisitorController::class, 'edit'])->name('visitor.edit');
    Route::put('/visitor/{id}', [App\Http\Controllers\Visitor\VisitorController::class, 'update'])->name('visitor.update');
    Route::delete('/visitor/{id}', [App\Http\Controllers\Visitor\VisitorController::class, 'destroy'])->name('visitor.destroy');

    // OTP Verification Routes
    Route::middleware('permission:verify visit otp')->group(function () {
        Route::get('/visitor/{id}/verify-otp', [App\Http\Controllers\Visitor\VisitorController::class, 'showVerifyOtp'])->name('visitor.verify.otp.view');
        Route::post('/visitor/verify-otp/{id}', [App\Http\Controllers\Visitor\VisitorController::class, 'verifyOtp'])->name('visitor.verify.otp');
    });

    // Host Approval Routes
    Route::post('/visits/{id}/approve', [App\Http\Controllers\Visitor\VisitorController::class, 'approveVisit'])
        ->name('visit.approve')
        ->middleware(['auth', 'permission:approve visit']);

    Route::post('/visits/{id}/reject', [App\Http\Controllers\Visitor\VisitorController::class, 'rejectVisit'])
        ->name('visit.reject')
        ->middleware(['auth', 'permission:reject visit']);

    // Check-in/Check-out Routes
    Route::middleware('permission:checkin visit')->group(function () {
        Route::post('/visits/{id}/check-in', [App\Http\Controllers\Visitor\VisitorController::class, 'checkIn'])->name('visit.checkin');
    });

    Route::middleware('permission:checkout visit')->group(function () {
        Route::post('/visits/{id}/check-out', [App\Http\Controllers\Visitor\VisitorController::class, 'checkOut'])->name('visit.checkout');
    });

    // Live Dashboard Routes
    Route::middleware('permission:view live dashboard')->group(function () {
        Route::get('/visitors/live-dashboard', [App\Http\Controllers\Visitor\VisitorController::class, 'liveDashboard'])->name('visitor.live');
    });

    // Insurance Packages Routes (Authenticated users only)
    Route::prefix('packages')->name('packages.')->group(function () {
        Route::get('/', [InsurancePackageController::class, 'publicIndex'])->name('index');
        Route::get('/{id}', [InsurancePackageController::class, 'publicShow'])->name('show');
        Route::post('/{id}/purchase', [InsurancePackageController::class, 'purchase'])->name('purchase');
    });

    // Orders/Policy Routes (Authenticated users only)
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [InsurancePackageController::class, 'orderList'])->name('index');
        Route::get('/{order}', [InsurancePackageController::class, 'showOrder'])->name('show');
        Route::get('/{order}/claim/create', [InsurancePackageController::class, 'createClaim'])->name('claim.create');
        Route::post('/{order}/claim/store', [InsurancePackageController::class, 'storeClaim'])->name('claim.store');
    });

    // Claims Routes (Authenticated users only)
    Route::prefix('claims')->name('claims.')->group(function () {
        Route::get('/', [InsurancePackageController::class, 'claimList'])->name('index');
        Route::get('/{claim}', [InsurancePackageController::class, 'showClaim'])->name('show');
    });

    // API Routes (no authentication for public access if needed)
    Route::get('/api/visitors/live', [App\Http\Controllers\Visitor\VisitorController::class, 'liveVisitorsApi'])->name('api.visitors.live');
});

// -------------------------------------------------------------------------
// Test SMS route
Route::get('/test-sms', function () {
    $smsService = new \App\Services\SmsNotificationService();

    $phone = '8801859385787'; // Test phone number (format: 880XXXXXXXXXX)
    $message = 'This is a test SMS from VMS UCBL system. If you receive this, SMS is working!';

    try {
        $result = $smsService->send($phone, $message);

        if ($result['success']) {
            return '✅ SMS sent successfully to ' . $phone . '! Check your phone.';
        } else {
            return '❌ Failed to send SMS: ' . $result['message'];
        }
    } catch (\Exception $e) {
        return '❌ Error: ' . $e->getMessage();
    }
})->name('test.sms');

    // API Routes (no authentication for public access if needed)
    Route::get('/api/visitors/live', [App\Http\Controllers\Visitor\VisitorController::class, 'liveVisitorsApi'])->name('api.visitors.live');

    // API Routes for host pending visits (with permission check)
    Route::middleware(['auth', 'permission:approve visit'])->group(function () {
        Route::get('/api/host-pending-visits', [App\Http\Controllers\Visitor\VisitorController::class, 'hostPendingVisitsApi'])->name('api.host.pending.visits');
    });

    // -------------------------------------------------------------------------
    // Route::middleware([
    //     'auth:sanctum',
    //     config('jetstream.auth_session'),
    //     'verified',
    // ])->group(function () {
    //     Route::get('/dashboard', function () {
    //         return view('dashboard');
    //     })->name('dashboard');
    // });
