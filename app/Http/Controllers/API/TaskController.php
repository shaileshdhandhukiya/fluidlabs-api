<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Task::with('project')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string',
            'start_date' => 'required|date',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high,urgent',
            'project_id' => 'required|exists:projects,id',
            'assignees' => 'required|array',
            'task_description' => 'nullable|string',
            'status' => 'required|in:not started,in progress,testing,awaiting feedback,completed',
            'attach_file' => 'nullable|string',
        ]);

        $task = Task::create($data);
        return response()->json($task, 201);
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
            'due_date' => 'nullable|date',
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
