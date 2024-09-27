<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class RoleController extends BaseController
{
    public function __construct()
    {
        // $this->middleware('permission:role-list|role-create|role-edit|role-delete', ['only' => ['index', 'store']]);
        // $this->middleware('permission:role-create', ['only' => ['create', 'store']]);
        // $this->middleware('permission:role-edit', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:role-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): JsonResponse
    {

        $roles = Role::orderBy('id', 'DESC')->paginate(5);

        return response()->json([
            'success' => true,
            'data' => ['roles' => $roles],
            'status' => 200,
        ], 200); // HTTP 200 OK
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permission' => 'required',
        ]);

        $role = Role::create(['name' => $request->input('name')]);
        $role->syncPermissions($request->input('permission'));

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully.',
            'data' => $role,
            'status' => 201,
        ], 201); // HTTP 201 Created
    }

    public function show($id): JsonResponse
    {
        $role = Role::find($id);
        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Role not found.'], 404);
        }

        $rolePermissions = Permission::join("role_has_permissions", "role_has_permissions.permission_id", "=", "permissions.id")
            ->where("role_has_permissions.role_id", $id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => ['role' => $role, 'permissions' => $rolePermissions],
            'status' => 200
        ], 200); // HTTP 200 OK
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate(['name' => 'required', 'permission' => 'required']);

        $role = Role::find($id);
        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Role not found.', 'status' => 404], 404);
        }

        $role->name = $request->input('name');
        $role->save();
        $role->syncPermissions($request->input('permission'));

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully.',
            'data' => $role,
            'status' => 200,
        ], 200); // HTTP 200 OK
    }

    public function destroy($id): JsonResponse
    {
        $role = Role::find($id);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found.',
                'status' => 500
            ], 404);
        }

        DB::table("roles")->where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully.',
            'status' => 200
        ], 200); // HTTP 200 OK
    }
}
