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
                'total_hours' => '0:00', // Default value
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

        // Calculate total time worked in hours and minutes
        $startedAt = Carbon::parse($taskTimer->started_at);
        $stoppedAt = Carbon::parse($request->stopped_at);
        $totalMinutes = $stoppedAt->diffInMinutes($startedAt);

        // Convert minutes to hours and minutes format
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        $formattedTime = sprintf('%02d:%02d', $hours, $minutes);

        $taskTimer->update([
            'stopped_at' => $request->stopped_at,
            'total_hours' => $formattedTime,  // Store formatted time as "HH:MM"
        ]);

        // Update consumed time for each assignee
        foreach ($taskTimer->assignees as $assigneeId) {
            $this->updateConsumedHoursForAssignee($assigneeId, $totalMinutes);
        }

        return response()->json([
            'success' => true,
            'message' => 'Task timer stopped and hours updated successfully',
            'data' => $taskTimer,
        ], 200);
    }

    // Helper function to update consumed hours for each assignee
    protected function updateConsumedHoursForAssignee($assigneeId, $minutesToAdd)
    {
        $currentMonth = Carbon::now()->format('Y-m');

        // Find or create user hours management entry for the current month
        $userHours = UserHoursManagement::firstOrCreate(
            ['user_id' => $assigneeId, 'month' => $currentMonth],
            ['total_hours' => '160:00', 'consumed_hours' => '00:00'] // Default to 160 hours and 0 consumed
        );

        // Convert consumed hours to minutes
        if (strpos($userHours->consumed_hours, ':') !== false) {
            [$hours, $minutes] = explode(':', $userHours->consumed_hours);
            $currentConsumedMinutes = ($hours * 60) + $minutes;
        } else {
            $currentConsumedMinutes = 0; // Set to zero if no valid time is found
        }

        // Calculate the new total consumed minutes
        $newTotalMinutes = $currentConsumedMinutes + $minutesToAdd;

        // Convert back to "HH:MM" format
        $updatedHours = intdiv($newTotalMinutes, 60);
        $updatedMinutes = $newTotalMinutes % 60;
        $formattedConsumedTime = sprintf('%02d:%02d', $updatedHours, $updatedMinutes);

        // Update the consumed_hours field with the new value
        $userHours->consumed_hours = $formattedConsumedTime;
        $userHours->save();
    }

    public function getAllTotalHours()
    {
        try {
            // Fetch all task timers
            $taskTimers = TaskTimer::all();

            // Prepare an empty collection to store total minutes per assignee
            $totalMinutesPerAssignee = collect();

            foreach ($taskTimers as $timer) {
                foreach ($timer->assignees as $assignee) {
                    // Check if total_hours is set and in the correct "HH:MM" format
                    if ($timer->total_hours && strpos($timer->total_hours, ':') !== false) {
                        [$hours, $minutes] = explode(':', $timer->total_hours);
                        $timerMinutes = ($hours * 60) + $minutes;
                    } else {
                        // If total_hours is invalid, treat it as 0 minutes
                        $timerMinutes = 0;
                    }

                    // If the assignee already exists, add their minutes; otherwise, initialize with current minutes
                    if ($totalMinutesPerAssignee->has($assignee)) {
                        $totalMinutesPerAssignee[$assignee] += $timerMinutes;
                    } else {
                        $totalMinutesPerAssignee[$assignee] = $timerMinutes;
                    }
                }
            }

            // Convert total minutes back to "HH:MM" format
            $totalHoursPerAssignee = $totalMinutesPerAssignee->map(function ($totalMinutes) {
                $hours = intdiv($totalMinutes, 60);
                $minutes = $totalMinutes % 60;
                return sprintf('%02d:%02d', $hours, $minutes);
            });

            return response()->json([
                'success' => true,
                'message' => 'Total hours retrieved successfully',
                'data' => $totalHoursPerAssignee,
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
            'total_time' => 'nullable|regex:/^\d{2}:\d{2}$/',  // Accept "HH:MM" format
        ]);

        $taskTimer = TaskTimer::findOrFail($id);

        if ($request->has('started_at')) {
            $taskTimer->started_at = Carbon::parse($request->started_at);
        }

        if ($request->has('stopped_at')) {
            $taskTimer->stopped_at = Carbon::parse($request->stopped_at);
        }

        if ($request->has('total_time')) {
            $taskTimer->total_hours = $request->total_time;
        } else if ($taskTimer->started_at && $taskTimer->stopped_at) {
            // Recalculate if times are provided
            $totalMinutes = $taskTimer->stopped_at->diffInMinutes($taskTimer->started_at);
            $hours = intdiv($totalMinutes, 60);
            $minutes = $totalMinutes % 60;
            $taskTimer->total_hours = sprintf('%02d:%02d', $hours, $minutes);
        }

        $taskTimer->save();

        return response()->json([
            'success' => true,
            'message' => 'Timer updated manually',
            'data' => $taskTimer,
        ], 200);
    }
}
