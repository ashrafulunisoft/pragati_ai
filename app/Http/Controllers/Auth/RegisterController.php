<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RegisterController extends Controller
{
    public function show()
    {

        // return "This is the Register page ";
        return view('auth_custom.register'); // Bootstrap 5
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Assign visitor role to the new user by default
        $visitorRole = Role::where('name', 'visitor')->first();
        if ($visitorRole) {
            $user->assignRole('visitor');
        }

        return redirect()->route('login');
    }


}
