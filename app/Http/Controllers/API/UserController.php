<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Mail;

class UserController extends BaseController
{

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('permission:user-list|user-create|user-edit|user-delete', ['only' => ['index', 'create', 'store']]);
        $this->middleware('permission:user-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:user-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:user-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of users with optional search query.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Build query to fetch all users except the one with id = 1
        $query = User::where('id', '!=', 1)->latest();

        // Apply search filter if it exists
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        // Retrieve all users without pagination
        $users = $query->get();

        // Transform the collection to append roles to each user
        $users->transform(function ($user) {
            $user->roles = $user->getRoleNames(); // Get roles for each user
            return $user;
        });

        // Return the users without pagination metadata
        return response()->json([
            'success' => true,
            'data' => $users,  // No pagination data here, just the user list
            'status' => 200,
        ], 200); // HTTP 200 OK
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(): JsonResponse
    {

        $roles = Role::pluck('name', 'name')->all();

        return response()->json([
            'success' => true,
            'data' => $roles,
            'status' => 200,
        ], 200); // HTTP 200 OK
    }

    /**
     * Store a newly created user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'type' => 'nullable|string',
            'phone' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'designation' => 'nullable|string',
            'date_of_join' => 'nullable|date',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:12',
            'send_welcome_email' => 'boolean', //0 or 1
            'roles' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error.',
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422); // HTTP 422 Unprocessable Entity
        }

        $input = $request->all();

        // Handle file upload
        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('uploads/profile_photos', $filename, 'public');
            $input['profile_photo'] = 'profile_photos/' . $filename;
        }

        $input['original_password'] = $input['password'];

        $input['password'] = Hash::make($input['password']);

        $user = User::create($input);
        $user->assignRole($request->input('roles'));

        // Send welcome email if checkbox is checked
        if ($request->input('send_welcome_email', false)) {
            Mail::to($user->email)->send(new WelcomeEmail($user, $input['original_password']));
        }

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => $user,
            'status' => 201,
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
                'message' => 'User not found.',
                'status' => 404,
            ], 404);
        }

        // Generate the full URL for the profile photo
        $user->profile_photo_url = $user->profile_photo
            ? URL::to('/storage/' . $user->profile_photo)
            : null; // Or a default image URL if no profile photo is set

        $user['roles'] .= $user->getRoleNames();

        return response()->json([
            'success' => true,
            'data' => $user,
            'status' => 200,
        ], 200);
    }

    /**
     * Update the specified user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // Find the user by ID
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
                'status' => 404,
            ], 404);
        }

        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $id,
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Limit file size to 2MB
            'type' => 'nullable|string',
            'phone' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'designation' => 'nullable|string',
            'date_of_join' => 'nullable|date',
            'password' => 'nullable|string|min:12',
            'send_welcome_email' => 'boolean',
            'roles' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error.',
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422);
        }

        // Prepare input data, excluding specific fields
        $input = $request->except(['profile_photo', 'password']);        

        // Handle file upload for profile photo
        if ($request->hasFile('profile_photo')) {
            // Delete old profile photo if it exists
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            // Store the new profile photo
            $originalName = $request->file('profile_photo')->getClientOriginalName();
            $filePath = $request->file('profile_photo')->storeAs('uploads/profile_photos', $originalName, 'public');
            $input['profile_photo'] = $filePath;
        }

        // Hash password if provided
        if (!empty($request->input('password'))) {
            $originalPassword = $request->input('password');
            $input['password'] = Hash::make($originalPassword);
            $input['original_password'] = $originalPassword; // Optional: Store plain password temporarily
        }

        // Update the user data
        $user->update($input);

        // Remove existing roles and assign new roles
        DB::table('model_has_roles')->where('model_id', $id)->delete();

        if (!empty($request->input('roles'))) {
            $user->assignRole($request->input('roles'));
        }

        // Send welcome email if checkbox is checked
        if ($request->input('send_welcome_email', false)) {
            $passwordToSend = $originalPassword ?? '(Set by user)';
            Mail::to($user->email)->send(new WelcomeEmail($user, $passwordToSend));
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => $user,
            'status' => 200,
        ], 200);
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
                'message' => 'User not found.',
                'status' => 404,
            ], 404); // HTTP 404 Not Found
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
            'status' => 200,
        ], 200); // HTTP 200 OK
    }
}
