<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    //

     // Show forgot password page
    public function request()
    {

        return view('auth_custom.forget_password');
        // return view('auth.forgot-password');
    }

    // Send reset email
    public function email(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    // Show reset password form
    public function reset(string $token, Request $request)
    {

         return view('auth_custom.reset_password', [
            'token' => $token,
            'email' => $request->query('email'),
            ]);
        // return view('auth.reset-password', [
        //     'token' => $token,
        //     'email' => $request->email,
        // ]);

        return view('auth_custom/reset_password.blade.php',['token' => $token, 'email' => $request->email]);
    }

    // Update password
    public function update(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }


}
