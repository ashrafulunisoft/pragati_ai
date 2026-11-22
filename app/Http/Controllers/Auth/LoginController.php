<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SecurityStore;
use App\Services\SecurityInputSanitizer;

class LoginController extends Controller
{
    /**
     * Display the login form.
     */
    public function show()
    {
        return view('auth_custom.login');
    }

    /**
     * Handle the login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $ip    = $request->ip();
        $email = SecurityInputSanitizer::email($request->input('email')) ?? $request->input('email');

        // Hard block check - blocked IP or blocked account (M9 shared-NAT scoped
        // blocks); Redis with emergency cache fallback
        if (SecurityStore::get("blocked:ip:$ip") || SecurityStore::get("blocked:email:$email")) {
            return back()->withErrors([
                'email' => 'Access temporarily blocked due to suspicious activity.'
            ]);
        }

        // Configurable window (M8: ATTEMPT_WINDOW_SECONDS)
        $window = (int) config('services.minimax.window_seconds', 900);

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {

            // Increment counters for failed attempts (atomic Lua INCR+EXPIRE,
            // TTL armed on first failure of the window - a true fixed window)
            SecurityStore::incrWithTtl("attack:login:ip:$ip", $window);
            SecurityStore::incrWithTtl("attack:login:email:$email", $window);

            return back()->withErrors(['email' => 'Invalid credentials']);
        }

        /*
         * ✅ Success - reset counters and record the distinct successful
         * account for this IP (M9 shared-NAT evidence: once several distinct
         * accounts succeed from one IP, blocks there become account-scoped).
         */
        SecurityStore::sAddWithTtl("success:emails:ip:$ip", $email, $window);
        SecurityStore::del(["attack:login:email:$email"]);

        // Problem 8 fix: Only unblock the IP if the shared-IP circuit breaker is NOT tripped
        $maxBlocked = (int) config('services.minimax.shared_ip_max_blocked_accounts', 5);
        if (SecurityStore::scard("blocked:accounts:ip:$ip") < $maxBlocked) {
            SecurityStore::del(["attack:login:ip:$ip", "blocked:ip:$ip"]);
        }

        $request->session()->regenerate();

        // Check user role and redirect accordingly
        $user = Auth::user();
        if ($user->hasRole('admin')) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
