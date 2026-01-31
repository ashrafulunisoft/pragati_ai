<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

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
        $email = $request->input('email');

        // Hard block check - if IP is blocked
        if (Redis::get("blocked:ip:$ip")) {
            return back()->withErrors([
                'email' => 'Access temporarily blocked due to suspicious activity.'
            ]);
        }

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {

            // Increment Redis counters for failed attempts
            $ipKey    = "attack:login:ip:$ip";
            $emailKey = "attack:login:email:$email";

            Redis::incr($ipKey);
            Redis::expire($ipKey, 900); // 15 minutes

            Redis::incr($emailKey);
            Redis::expire($emailKey, 900); // 15 minutes

            return back()->withErrors(['email' => 'Invalid credentials']);
        }

        // ✅ Success - reset counters
        Redis::del("attack:login:ip:$ip");
        Redis::del("attack:login:email:$email");
        Redis::del("blocked:ip:$ip");

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
