<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Validator;
use DB;
use Hash;
use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class UserController extends BaseController
{
    /**
     * Display a listing of users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::latest()->paginate(5);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Store a newly created user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'nullable|string',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',  // Validate as image
            'type' => 'nullable|string',
            'phone' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'role' => 'required|string',
            'designation' => 'nullable|string',
            'date_of_join' => 'nullable|date',
            'email' => 'required|email|unique:users,email',  // Required, must be a valid email and unique
            'password' => 'required',  // Required, must match the confirmation password (password_confirmation field in request)
        ]);

        if ($validator->fails()) {
            return $this->sendError("Validation Error.", $validator->errors());
        }

        $input = $request->all();

        // Handle file upload
        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            $filename = time() . '.' . $file->getClientOriginalExtension(); // Create a unique file name
            $file->storeAs('uploads/profile_photos', $filename, 'public'); // Store the file in public/uploads/profile_photos
            $input['profile_photo'] = 'uploads/profile_photos/' . $filename; // Set the file path to input
        }

        $input['password'] = bcrypt($input['password']); // Hash the password

        $user = User::create($input); // Create user
        $user->assignRole($request->input('roles')); // Assign role to user

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => $user,
        ], 201);
    }


    /**
     * Display the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Update the specified user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'nullable|string',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',  // Validate the profile photo
            'type' => 'nullable|string',
            'phone' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'role' => 'required|string',
            'designation' => 'nullable|string',
            'date_of_join' => 'nullable|date',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',  // Make password optional
            'roles' => 'nullable|string',  // Adjust roles validation if needed
        ]);

        if ($validator->fails()) {
            return $this->sendError("Validation Error.", $validator->errors());
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        // Handle file upload for profile photo
        if ($request->hasFile('profile_photo')) {
            // Delete old profile photo if necessary
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            // Store the new profile photo
            $path = $request->file('profile_photo')->store('profile_photos', 'public');
            $user->profile_photo = $path; // Save the path to the user's profile_photo field
        }

        // Update the user information
        $input = $request->all();

        // Handle password hashing if password is provided
        if (!empty($input['password'])) {
            $input['password'] = bcrypt($input['password']);
        } else {
            $input = Arr::except($input, ['password']); // Remove password if it's not provided
        }

        // Update user data
        $user->update($input);

        // Update roles
        DB::table('model_has_roles')->where('model_id', $id)->delete();
        if ($request->input('roles')) {
            $user->assignRole($request->input('roles'));
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => $user,
        ]);
    }


    /**
     * Remove the specified user from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.'
        ]);
    }
}
