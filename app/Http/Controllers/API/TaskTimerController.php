<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\TaskTimer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaskTimerController extends BaseController
{
    // Start Task Timer
    public function startTimer(Request $request)
    {
        try {
            // Validate the request input
            $request->validate([
                'task_id' => 'required|integer',
                'assignees' => 'required|array',
                'started_at' => 'required|date_format:Y-m-d H:i:s'
            ]);

            // Create a new TaskTimer
            $taskTimer = TaskTimer::create([
                'task_id' => $request->task_id,
                'assignees' => $request->assignees,
                'started_at' => Carbon::parse($request->started_at),
                'total_hours' => 0, // Default value
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Task timer started successfully',
                'data' => $taskTimer,
                'status' => 200
            ], 200);
            
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to start task timer',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    // Stop Task Timer
    public function stopTimer($id, Request $request)
    {
        try {
            // Validate the stop time
            $request->validate([
                'stopped_at' => 'required|date_format:Y-m-d H:i:s'
            ]);

            // Find the task timer by ID
            $taskTimer = TaskTimer::findOrFail($id);

            // Calculate total hours
            $startTime = Carbon::parse($taskTimer->started_at);
            $stopTime = Carbon::parse($request->stopped_at);
            $totalHours = $startTime->diffInHours($stopTime);

            // Update the task timer
            $taskTimer->update([
                'stopped_at' => $stopTime,
                'total_hours' => $totalHours
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Task timer stopped successfully',
                'data' => $taskTimer,
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop task timer',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    // get total time spend on tasks
    public function getAllTotalHours()
    {
        try {
            // Fetch all task timers
            $taskTimers = TaskTimer::all();

            // Prepare an empty collection to store total hours per assignee
            $totalHours = collect();

            // Loop through each timer and its assignees
            foreach ($taskTimers as $timer) {
                foreach ($timer->assignees as $assignee) {
                    // If the assignee already exists, sum their hours, otherwise set their initial hours
                    if ($totalHours->has($assignee)) {
                        $totalHours[$assignee] += $timer->total_hours;
                    } else {
                        $totalHours[$assignee] = $timer->total_hours;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Total hours retrieved successfully',
                'data' => $totalHours,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve total hours',
                'error' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    // Get Task Timer Details by ID
    public function getTaskTimer($id)
    {
        try {
            // Fetch task timer by ID
            $taskTimer = TaskTimer::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Task timer retrieved successfully',
                'data' => $taskTimer,
                'status' => 200
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Task timer not found',
                'status' => 404
            ], 404);
        }
    }

    // Timer status
    public function isTaskTimerRunning($taskId)
    {
        try {
            // Check if there is a running timer for the given task
            $timer = TaskTimer::where('task_id', $taskId)
                ->whereNotNull('started_at')   // Timer has started
                ->whereNull('stopped_at')      // Timer has not stopped
                ->first();

            if ($timer) {

                return response()->json([
                    'success' => true,
                    'message' => 'Task timer is running',
                    'data' => [
                        'task_id' => $taskId,
                        'started_at' => $timer->started_at
                    ],
                    'status' => 200,
                ], 200);

            } else {

                return response()->json([
                    'success' => false,
                    'message' => 'Task timer is not running',
                    'status' => 200,
                ], 200);

            }
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to check task timer status',
                'error' => $e->getMessage(),
                'status' => 500,
            ], 500);

        }
    }
}
