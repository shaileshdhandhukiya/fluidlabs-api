<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Subtask;
use App\Models\Task;

class SubTaskController extends BaseController
{
    public function index($taskId)
    {
        $subtasks = Subtask::with('task')->where('task_id', $taskId)->get();

        return response()->json([
            'success' => true,
            'data' => $subtasks,
            'message' => 'Subtasks retrieved successfully',
            'status' => 200,
        ], 200);
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,id',
            'subject' => 'required|string',
            'start_date' => 'required|date',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high,urgent',
            'project_id' => 'required|exists:projects,id',
            'assignees' => 'required|array',
            'task_description' => 'nullable|string',
            'status' => 'required|in:not started,in progress,testing,awaiting feedback,completed',
            'attach_file' => 'nullable|file|mimes:jpg,png,pdf,doc,docx',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Validation errors',
                'status' => 422
            ], 422);
        }

        $data = $request->all();

        if ($request->hasFile('attach_file')) {
            $file = $request->file('attach_file');
            $filePath = $file->store('attachments', 'public');
            $data['attach_file'] = $filePath;
        }

        $subTask = Subtask::create([
            'task_id' => $data['task_id'],
            'subject' => $data['subject'],
            'start_date' => $data['start_date'],
            'due_date' => $data['due_date'],
            'priority' => $data['priority'],
            'project_id' => $data['project_id'],
            'assignees' => $data['assignees'],
            'task_description' => $data['task_description'],
            'status' => $data['status'],
            'attach_file' => $data['attach_file'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $subTask,
            'message' => 'Subtask created successfully',
            'status' => 201
        ], 201);
    }


    public function show($taskId, $subtaskId)
    {
    
        $subtask = Subtask::where('task_id', $taskId)->find($subtaskId);

        if (!$subtask) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'No subtasks found for this task',
                'status' => 404,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $subtask,
            'message' => 'Subtask retrieved successfully',
            'status' => 200,
        ], 200);
    }


    public function update(Request $request, $id)
    {
        // Define validation rules
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string',
            'start_date' => 'required|date',
            'due_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high,urgent',
            'project_id' => 'required|exists:projects,id',
            'assignees' => 'required|array',
            'task_description' => 'nullable|string',
            'status' => 'required|in:not started,in progress,testing,awaiting feedback,completed',
            'attach_file' => 'nullable|file|mimes:jpg,png,pdf,doc,docx'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'message' => 'Validation errors',
                'status' => 422
            ], 422);
        }

        // Find the subtask by ID
        $subTask = SubTask::findOrFail($id);

        // Update data
        $data = $request->all();

        // Handle file upload if provided
        if ($request->hasFile('attach_file')) {
            // Delete the old file if exists
            if ($subTask->attach_file && Storage::disk('public')->exists($subTask->attach_file)) {
                Storage::disk('public')->delete($subTask->attach_file);
            }

            // Store new file
            $file = $request->file('attach_file');
            $filePath = $file->store('attachments', 'public');
            $data['attach_file'] = $filePath;
        }

        // Update subtask with new data
        $subTask->update($data);

        return response()->json([
            'success' => true,
            'data' => $subTask,
            'message' => 'Subtask updated successfully',
            'status' => 200
        ], 200);
    }

    public function destroy($taskId, $subtaskId)
    {
        $subtask = Subtask::where('task_id', $taskId)->findOrFail($subtaskId);

        // Delete the attached file if it exists
        if ($subtask->attach_file) {
            Storage::disk('public')->delete($subtask->attach_file);
        }

        $subtask->delete();

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Subtask deleted successfully',
            'status' => 200,
        ], 200);
    }
}
