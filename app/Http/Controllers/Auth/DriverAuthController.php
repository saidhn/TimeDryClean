<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Driver; // Use the Driver model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DriverAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.driver.login'); // Make sure this view exists
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('driver')->attempt($credentials, $request->boolean('remember'))) { // Use the 'driver' guard
            $request->session()->regenerate();

            return redirect()->intended('/driver/dashboard'); // Redirect to driver dashboard
        }

        return back()->withInput($request->only('email'))->withErrors([ // Preserve the email input
            'email' => 'These credentials do not match our records.',
        ]);
    }

    public function showRegistrationForm()
    {
        return view('auth.driver.register'); // Make sure this view exists
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'mobile' => [
                'required',
                'string',
                'max:15',
                'unique:admins,mobile',  // Unique for admins table
                'regex:/^(\+?\d{1,4}[\s-]?){0,1}(\(\d{1,4}\)[\s-]?){0,1}(\d{1,14}[\s-]?){0,1}$/', // Improved regex
            ],
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput(); // Use back() for better UX
        }

        Driver::create([ // Use the Driver model
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'password' => Hash::make($request->password),
            'address_id' => $request->address_id,
            'user_type' => 'driver', // Set the user type
        ]);

        return redirect()->route('driver.login')->with('success', 'Registration successful!');
    }

    public function logout(Request $request)
    {
        Auth::guard('driver')->logout(); // Use the 'driver' guard

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
