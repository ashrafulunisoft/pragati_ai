<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{

public function user()
{
    return view("user");
}


 public function create()
    {
        return view('user.create');
    }
 public function store(Request $request)
    {
        user_infos::create([
            'name'  => $request->name,
            'email' => $request->email
        ]);

        return redirect()->back()->with('success', 'User Created Successfully');
    }



}
