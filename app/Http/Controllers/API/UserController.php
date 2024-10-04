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

class UserController extends BaseController
{

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('permission:user-list|user-create|user-edit|user-delete', ['only' => ['index','create','store']]);
        $this->middleware('permission:user-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:user-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:user-delete', ['only' => ['destroy']]);
    }


    /**
     * Display a listing of users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // $users = User::latest()->paginate(10);

        $users = User::where('id', '!=', 1)->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $users,
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

        $roles = Role::pluck('name','name')->all();

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
            $input['profile_photo'] = 'uploads/profile_photos/' . $filename;
        }

        $input['original_password'] = $input['password'];

        $input['password'] = Hash::make($input['password']);

        $user = User::create($input);
        $user->assignRole($request->input('roles'));

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
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'type' => 'nullable|string',
            'phone' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'designation' => 'nullable|string',
            'date_of_join' => 'nullable|date',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'required|string|min:12',
            'roles' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error.',
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422); // HTTP 422 Unprocessable Entity
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
                'status' => 404,
            ], 404); // HTTP 404 Not Found
        }

        // Handle file upload for profile photo
        if ($request->hasFile('profile_photo')) {
            // Delete old profile photo if necessary
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            // Store the new profile photo
            $path = $request->file('profile_photo')->store('profile_photos', 'public');
            $user->profile_photo = $path;
        }

        $input = $request->all();

        if (!empty($input['password'])) {
            $input['password'] = Hash::make($input['password']);
            $input['original_password'] = $input['password'];
        } else {
            $input = Arr::except($input, ['password']);
        }

        $user->update($input);

        DB::table('model_has_roles')->where('model_id', $id)->delete();

        if ($request->input('roles')) {
            $user->assignRole($request->input('roles'));
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => $user,
            'status' => 200,
        ], 200); // HTTP 200 OK
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
