<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\Project;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class ProjectController extends BaseController
{

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('permission:project-list|project-create|project-edit|project-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:project-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:project-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:project-delete', ['only' => ['destroy']]);
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = Project::with('customer', 'tasks')->get();

        return response()->json([
            'success' => true,
            'data' => $projects,
            'message' => 'Projects retrieved successfully',
            'status' => 200,
        ], 200); // HTTP 200 OK
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (is_string($request->members)) {
            $request->merge([
                'members' => explode(',', $request->members)
            ]);
        }

        $validator = Validator::make($request->all(), [
            'project_name' => 'required|string',
            'customer_id' => 'required|exists:customers,id',
            'status' => 'required|in:not started,in progress,on hold,cancelled,delivered',
            'progress' => 'nullable|integer',
            'members' => 'required|array',
            'estimated_hours' => 'nullable|integer',
            'start_date' => 'required|date',
            'deadline' => 'nullable|date',
            'description' => 'nullable|string',
            'send_project_created_email' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'status' => 400,
            ], 400); // HTTP 400 Bad Request
        }

        $project = Project::create($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $project,
            'message' => 'Project created successfully',
            'status' => 201,
        ], 201); // HTTP 201 Created

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $project = Project::with('customer', 'tasks')->find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
                'status' => 404,
            ], 404); // HTTP 404 Not Found
        }

        return response()->json([
            'success' => true,
            'data' => $project,
            'message' => 'Project retrieved successfully',
            'status' => 200,
        ], 200); // HTTP 200 OK
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $project = Project::find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
                'status' => 404,
            ], 404); // HTTP 404 Not Found
        }

        if (is_string($request->members)) {
            $request->merge([
                'members' => explode(',', $request->members)
            ]);
        }

        $validator = Validator::make($request->all(), [
            'project_name' => 'required|string',
            'customer_id' => 'required|exists:customers,id',
            'status' => 'required|in:not started,in progress,on hold,cancelled,delivered',
            'progress' => 'nullable|integer',
            'members' => 'required|array',
            'estimated_hours' => 'nullable|integer',
            'start_date' => 'required|date',
            'deadline' => 'nullable|date',
            'description' => 'nullable|string',
            'send_project_created_email' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'status' => 400,
            ], 400); // HTTP 400 Bad Request
        }

        $project->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $project,
            'message' => 'Project updated successfully',
            'status' => 200,
        ], 200); // HTTP 200 OK
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $project = Project::find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
                'status' => 404,
            ], 404); // HTTP 404 Not Found
        }

        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully',
            'status' => 200,
        ], 200); // HTTP 200 OK
    }

     /**
     * Get all projects and linked tasks for a specific user.
     */
    public function getUserProjectsWithTasks($user_id)
    {

        try {
            // Fetch projects where the user is a member (assuming 'members' field contains user IDs in an array)
            $projects = Project::whereJsonContains('members', $user_id)
                ->with('tasks') // Assuming a Project has a 'tasks' relationship
                ->get();
            
            if ($projects->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No projects found for the given user',
                    'status' => 404,
                ], 404); // HTTP 404 Not Found
            }

            // Return the projects along with their tasks
            return response()->json([
                'success' => true,
                'data' => $projects,
                'message' => 'Projects and linked tasks retrieved successfully',
                'status' => 200,
            ], 200); // HTTP 200 OK

        } catch (Exception $e) {
            // Log the error for debugging
            Log::error('Error retrieving projects and tasks: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve projects and tasks',
                'error' => $e->getMessage(),
                'status' => 500,
            ], 500); // HTTP 500 Internal Server Error
        }
    }
}
