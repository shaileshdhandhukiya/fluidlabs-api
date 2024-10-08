<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('permission:task-list|task-create|task-edit|task-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:task-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:task-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:task-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tasks = Task::with('project')->get();

        return response()->json([
            'success' => true,
            'data' => $tasks,
            'message' => 'Tasks retrieved successfully',
            'status' => 200,
        ], 200); // HTTP 200 OK
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        if (is_string($request->assignees)) {
            $request->merge([
                'assignees' => explode(',', $request->assignees)
            ]);
        }

        $validator = Validator::make($request->all(), [
            'subject' => 'required|string',
            'start_date' => 'required|date',
            'due_date' => 'required|date',
            'priority' => 'required|in:low,medium,high,urgent',
            'project_id' => 'required|exists:projects,id',
            'assignees' => 'required|array',
            'task_description' => 'nullable|string',
            'status' => 'required|in:not started,in progress,testing,awaiting feedback,completed',
            'attach_file' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'status' => 400,
            ], 400); // HTTP 400 Bad Request
        }

        $task = Task::create($validator->validated());
        return response()->json([
            'success' => true,
            'data' => $task,
            'message' => 'Task created successfully',
            'status' => 201,
        ], 201); // HTTP 201 Created
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $task = Task::with('project')->find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
                'status' => 404,
            ], 404); // HTTP 404 Not Found
        }

        return response()->json([
            'success' => true,
            'data' => $task,
            'message' => 'Task retrieved successfully',
            'status' => 200,
        ], 200); // HTTP 200 OK
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
                'status' => 404,
            ], 404); // HTTP 404 Not Found
        }

        $validator = Validator::make($request->all(), [
            'subject' => 'required|string',
            'start_date' => 'required|date',
            'due_date' => 'required|date',
            'priority' => 'required|in:low,medium,high,urgent',
            'project_id' => 'required|exists:projects,id',
            'assignees' => 'required|array',
            'task_description' => 'nullable|string',
            'status' => 'required|in:not started,in progress,testing,awaiting feedback,completed',
            'attach_file' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'status' => 400,
            ], 400); // HTTP 400 Bad Request
        }

        $task->update($validator->validated());

        return response()->json([
            'success' => true,
            'data' => $task,
            'message' => 'Task updated successfully',
            'status' => 200,
        ], 200); // HTTP 200 OK
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $task = Task::find($id);

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
                'status' => 404,
            ], 404); // HTTP 404 Not Found
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully',
            'status' => 200,
        ], 200); // HTTP 200 OK
    }
}
