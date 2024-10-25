<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\TaskTimer;
use App\Models\UserHoursManagement;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaskTimerController extends BaseController
{

    public function startTimer(Request $request)
    {

        try {

            $request->validate([
                'task_id' => 'required|exists:tasks,id',
                'assignees' => 'required|array',
                'started_at' => 'required|date_format:Y-m-d H:i:s',
            ]);

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
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to start task timer',
                'error' => $e->getMessage(),
                'status' => 500
            ], 500);
        }
    }

    // Stop a timer for a task and update consumed hours
    public function stopTimer(Request $request, $id)
    {
        $request->validate([
            'stopped_at' => 'required|date_format:Y-m-d H:i:s',
        ]);

        $taskTimer = TaskTimer::findOrFail($id);

        if (!$taskTimer->started_at) {
            return response()->json([
                'success' => false,
                'message' => 'Timer has not started yet.',
            ], 400);
        }

        // Calculate total hours worked
        $startedAt = Carbon::parse($taskTimer->started_at);
        $stoppedAt = Carbon::parse($request->stopped_at);
        $totalHours = $stoppedAt->diffInHours($startedAt);

        $taskTimer->update([
            'stopped_at' => $request->stopped_at,
            'total_hours' => $totalHours,
        ]);

        // Update consumed hours for each assignee
        foreach ($taskTimer->assignees as $assigneeId) {
            $this->updateConsumedHoursForAssignee($assigneeId, $totalHours);
        }

        return response()->json([
            'success' => true,
            'message' => 'Task timer stopped and hours updated successfully',
            'data' => $taskTimer,
        ], 200);
    }

    // Helper function to update consumed hours for each assignee
    protected function updateConsumedHoursForAssignee($assigneeId, $hoursToAdd)
    {
        $currentMonth = Carbon::now()->format('Y-m');

        // Find or create user hours management entry for the current month
        $userHours = UserHoursManagement::firstOrCreate(
            ['user_id' => $assigneeId, 'month' => $currentMonth],
            ['total_hours' => 160, 'consumed_hours' => 0] // Default to 160 total hours per month
        );

        // Add the hours to consumed hours
        $userHours->consumed_hours += $hoursToAdd;
        $userHours->save();
    }


    // get total time spend on tasks
    public function getAllTotalHours()
    {
        try {
            // Fetch all task timers
            $taskTimers = TaskTimer::all();

            // Prepare an empty collection to store total hours per assignee
            $totalHours = collect();

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
                        'timer_id' => $timer->id,  // Include the timer ID
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

    public function updateTimerManually(Request $request, $id)
    {
        $request->validate([
            'started_at' => 'nullable|date_format:Y-m-d H:i:s',
            'stopped_at' => 'nullable|date_format:Y-m-d H:i:s',
            'total_hours' => 'nullable|numeric|min:0'
        ]);

        $taskTimer = TaskTimer::findOrFail($id);

        if ($request->has('started_at')) {
            $taskTimer->started_at = Carbon::parse($request->started_at);
        }

        if ($request->has('stopped_at')) {
            $taskTimer->stopped_at = Carbon::parse($request->stopped_at);
            if ($taskTimer->started_at) {
                $taskTimer->total_hours = Carbon::parse($taskTimer->stopped_at)->diffInHours($taskTimer->started_at);
            }
        }

        if ($request->has('total_hours')) {
            $taskTimer->total_hours = $request->total_hours;
        }

        $taskTimer->save();

        return response()->json([
            'success' => true,
            'message' => 'Timer updated manually',
            'data' => $taskTimer,
        ], 200);
    }
}
