<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Admin;
use App\Models\City;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::guard('admin')->check()) { // Check if the admin is already authenticated
            return redirect()->route('admin.dashboard'); // Redirect to admin dashboard
        }
        return view('auth.admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'mobile' => 'required|numeric',
            'password' => 'required',
        ]);


        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended('/admin/dashboard'); // Redirect to admin dashboard
        }

        return back()->withErrors([
            'mobile' => __('messages.the_provided_credentials_do_not_match_our_records'),
        ]);
    }

    public function showRegistrationForm()
    {
        $provinces = Province::all();
        $cities = City::all();
        return view('auth.admin.register', ['cities' => $cities, 'provinces' => $provinces]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:admins,email', // Unique for admins table
            'password' => 'required|string|min:8|confirmed',
            'mobile' => [
                'required',
                'string',
                'max:15',
                'unique:admins,mobile',  // Unique for admins table
                'regex:/^(\+?\d{1,4}[\s-]?){0,1}(\(\d{1,4}\)[\s-]?){0,1}(\d{1,14}[\s-]?){0,1}$/', // Improved regex
            ],
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
        ]);

        if ($validator->fails()) {
            return redirect('admin/register')
                ->withErrors($validator)
                ->withInput();
        }

        // Use a database transaction for data integrity (important!)
        DB::beginTransaction();

        try {
            $address = Address::create([
                'province_id' => $request->input('province_id'),
                'city_id' => $request->input('city_id'),
            ]);

            Admin::create([
                'name' => $request['name'],
                'email' => $request['email'],
                'password' => Hash::make($request['password']),
                'mobile' => $request['mobile'],
                'address_id' => $address->id,
                'user_type' => 'admin',
            ]);

            DB::commit();
            return redirect()->route('admin.login')->with('success', __('messages.registration_successful_please_login'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', __('messages.registration_failed')); // More specific error message
        }
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
