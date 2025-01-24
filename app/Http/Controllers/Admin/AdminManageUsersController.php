<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Province;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class AdminManageUsersController extends Controller
{
    public function index()
    {
        $users = User::paginate(10);
        return view('admin.users.index', ["users" => $users]);
    }
    /**
     * view edit user page
     * @param mixed $user_id
     * @return \Illuminate\Contracts\View\View
     */
    public function edit($user_id)
    {
        $user = User::find($user_id);
        $provinces = Province::all();
        $cities = City::all();
        return view('admin.users.edit', ["user" => $user, 'cities' => $cities, 'provinces' => $provinces]);
    }
    public function update($user_id)
    {
        $user = User::findOrFail($user_id);

        $validator = Validator::make(request()->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $user->id, // Exclude current user's email from uniqueness check
            'mobile' => 'required|string|max:15|unique:users,mobile,' . $user->id, // Exclude current user's mobile from uniqueness check
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Update user data
        $user->name = request('name');
        $user->email = request('email');
        // Update mobile number if provided
        if (request('mobile') != $user->mobile) {
            $user->mobile = request('mobile');
        }

        // Update address if provided
        if (request('province_id') || request('city_id')) {
            $address = $user->address;
            $address->province_id = request('province_id');
            $address->city_id = request('city_id');
            $address->save();
        }

        $user->save();

        return redirect()->back()->with('success', __('messages.updated_successfully'));
    }
}
