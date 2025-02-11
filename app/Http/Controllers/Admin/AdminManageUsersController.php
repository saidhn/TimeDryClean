<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\City;
use App\Models\Province;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;


class AdminManageUsersController extends Controller
{
    // Define user types as a constant for better reusability
    public const USER_TYPES = ["client", "driver", "employee", "admin"];

    /**
     * Display a paginated list of users.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $userTypes = $request->get('user_type', []); // Get selected user types as an array

        $users = User::query();

        if ($search) {
            $users->where('name', 'LIKE', "%$search%")
                ->orWhere('id', $search)
                ->orWhere('mobile', 'LIKE', "%$search%")
                ->orWhere('email', 'LIKE', "%$search%");
        }

        if (!empty($userTypes)) { // Check if any user types are selected
            $users->whereIn('user_type', $userTypes); // Filter by selected user types
        }

        $users = $users->paginate(10);
        return view('admin.users.index', ["users" => $users]);
    }

    /**
     * Show the form for editing a user.
     *
     * @param int $user_id
     * @return \Illuminate\Contracts\View\View
     */
    public function edit($user_id)
    {
        $user = User::findOrFail($user_id); // Use findOrFail to handle missing users
        $provinces = Province::all();
        $cities = City::all();
        return view('admin.users.edit', [
            "user" => $user,
            'cities' => $cities,
            'provinces' => $provinces,
            'user_types' => self::USER_TYPES, // Use the constant
        ]);
    }

    /**
     * Update the specified user in storage.
     *
     * @param int $user_id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update($user_id)
    {
        $user = User::findOrFail($user_id);

        // Validate the request data
        $validator = Validator::make(request()->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $user->id,
            'mobile' => [
                'required',
                'string',
                'max:15',
                Rule::unique('users', 'mobile')->ignore($user->id),  // Ignore current user's mobile
                'regex:/^(\+?\d{1,4}[\s-]?){0,1}(\(\d{1,4}\)[\s-]?){0,1}(\d{1,14}[\s-]?){0,1}$/', // Improved regex
            ],
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
            'user_type' => 'required|string|in:' . implode(',', self::USER_TYPES),
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Update user data
        $user->name = request('name');
        $user->email = request('email');
        $user->user_type = request('user_type');
        $user->mobile = request('mobile'); // Update mobile directly

        // Update address if provided
        if ($user->address) {
            $user->address->update([
                'province_id' => request('province_id'),
                'city_id' => request('city_id'),
            ]);
        } else {
            // Create a new address if it doesn't exist
            $address = Address::create([
                'province_id' => request('province_id'),
                'city_id' => request('city_id'),
            ]);
            $user->address_id = $address->id;
        }

        $user->save();

        return redirect()->back()->with('success', __('messages.updated_successfully'));
    }


    public function destroy($user_id, $forceDelete = false)
    {
        $user = User::withTrashed()->findOrFail($user_id);

        // Check if deleting this user will leave no admins
        $adminCount = User::where('user_type', 'admin')->count();

        if ($user->user_type === 'admin' && $adminCount <= 1) {
            return redirect()->back()->with('error', __('messages.cannot_delete_last_admin'));
        }

        // Use a database transaction for data integrity
        DB::beginTransaction();

        try {
            if ($forceDelete) {
                $user->forceDelete(); // Permanently delete the user
            } else {
                $user->delete(); // Soft delete the user
            }

            DB::commit();
            return redirect()->route('admin.users.index')->with('success', __('messages.deleted_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', __('messages.delete_failed'));
        }
    }

    /**
     * Show the form for creating a new user.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        $provinces = Province::all();
        $cities = City::all();
        return view('admin.users.create', [
            'provinces' => $provinces,
            'cities' => $cities,
            'user_types' => self::USER_TYPES, // Use the constant
        ]);
    }

    /**
     * Store a newly created user in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'mobile' => [
                'required',
                'string',
                'max:15',
                'unique:users,mobile',
                'regex:/^(\+?\d{1,4}[\s-]?){0,1}(\(\d{1,4}\)[\s-]?){0,1}(\d{1,14}[\s-]?){0,1}$/', // Improved regex
            ],
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
            'user_type' => 'required|string|in:' . implode(',', self::USER_TYPES),
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Use a database transaction for data integrity
        DB::beginTransaction();

        try {
            // Create the user
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'mobile' => $request->input('mobile'),
                'user_type' => $request->input('user_type'),
                'password' => Hash::make($request->input('password')),
            ]);

            // Create the address
            $address = Address::create([
                'province_id' => $request->input('province_id'),
                'city_id' => $request->input('city_id'),
            ]);

            // Associate the address with the user
            $user->address_id = $address->id;
            $user->save();

            DB::commit();
            return redirect()->route('admin.users.index')->with('success', __('messages.registration_successful_please_login'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', __('messages.registration_failed')); // More specific error message
        }
    }
}
