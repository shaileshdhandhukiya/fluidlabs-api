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
        ], 200);
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
            'project_files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:500000000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'status' => 400,
            ], 400); // HTTP 400 Bad Request
        }

        $projectData = $validator->validated();
        $project = Project::create($projectData);

        $filePaths = [];

        if ($request->hasFile('project_files')) {
            foreach ($request->file('project_files') as $file) {
                $originalName = $file->getClientOriginalName(); // Get original file name
                $path = $file->storeAs('uploads/projects', $originalName, 'public');
                $filePaths[] = $path; // Add the path to the array
            }
        }

        // Save file paths as a JSON array in the database
        $project->project_files = $filePaths;

        $project->save();

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
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $project,
            'message' => 'Project retrieved successfully',
            'status' => 200,
        ], 200);
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

        // Handle members as before
        if (is_string($request->members)) {
            $request->merge([
                'members' => explode(',', $request->members)
            ]);
        }

        // Validate the request
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
            'project_files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:500000000', // Validate multiple attachments
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'status' => 400,
            ], 400); // HTTP 400 Bad Request
        }

        // Update the project first
        $projectData = $validator->validated();
        $project->update($projectData);

        // Calculate the project progress based on task completion
        $tasks = $project->tasks;
        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('status', 'completed')->count(); // Adjust based on your status naming

        // Calculate progress percentage
        if ($totalTasks > 0) {
            $progressPercentage = ($completedTasks / $totalTasks) * 100;
            $project->progress = round($progressPercentage); // Round to the nearest integer
        } else {
            $project->progress = 0; // If there are no tasks, set progress to 0
        }

        // Handle project files upload
        $filePaths = [];
        if ($request->hasFile('project_files')) {
            foreach ($request->file('project_files') as $file) {
                $originalName = $file->getClientOriginalName(); // Get original file name
                $path = $file->storeAs('uploads/projects', $originalName, 'public'); // Store with original name
                $filePaths[] = $path; // Add to file paths array
            }
        }

        // If new files were uploaded, update the project files field
        if (!empty($filePaths)) {
            $project->project_files = $filePaths; // Save file paths as an array
        }

        $project->save();

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
            ], 404);
        }

        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully',
            'status' => 200,
        ], 200);
    }

    /**
     * Get all projects and linked tasks for a specific user.
     */
    public function getUserProjectsWithTasks($user_id)
    {
        try {

            $projects = Project::whereJsonContains('members', $user_id)
                ->with('tasks')
                ->get();

            if ($projects->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No projects found for the given user',
                    'status' => 404,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $projects,
                'message' => 'Projects and linked tasks retrieved successfully',
                'status' => 200,
            ], 200);
        } catch (Exception $e) {
            Log::error('Error retrieving projects and tasks: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve projects and tasks',
                'error' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }
}
