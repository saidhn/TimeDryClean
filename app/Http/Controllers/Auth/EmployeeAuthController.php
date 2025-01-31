<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmployeeAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.employee.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::guard('employee')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended('/employee/dashboard');
        }

        return back()->withInput($request->only('email'))->withErrors([
            'email' => 'These credentials do not match our records.',
        ]);
    }

    public function showRegistrationForm()
    {
        return view('auth.employee.register');
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
            return back()->withErrors($validator)->withInput();
        }

        Employee::create([
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'password' => Hash::make($request->password),
            'address_id' => $request->address_id,
            'user_type' => 'employee',
        ]);

        return redirect()->route('employee.login')->with('success', 'Registration successful!');
    }


    public function logout(Request $request)
    {
        Auth::guard('employee')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
