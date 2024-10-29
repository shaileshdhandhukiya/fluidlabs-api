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
                'total_hours' => '00:00', // Default value in HH:MM format
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

        $startedAt = Carbon::parse($taskTimer->started_at);
        $stoppedAt = Carbon::parse($request->stopped_at);
        $totalMinutes = $stoppedAt->diffInMinutes($startedAt);

        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        $formattedTime = sprintf('%02d:%02d', $hours, $minutes);

        $taskTimer->update([
            'stopped_at' => $request->stopped_at,
            'total_hours' => $formattedTime,
        ]);

        foreach ($taskTimer->assignees as $assigneeId) {
            $this->updateConsumedHoursForAssignee($assigneeId, $totalMinutes);
        }

        return response()->json([
            'success' => true,
            'message' => 'Task timer stopped and hours updated successfully',
            'data' => $taskTimer,
        ], 200);
    }

    protected function updateConsumedHoursForAssignee($assigneeId, $minutesToAdd)
    {
        $currentMonth = Carbon::now()->format('Y-m');

        $userHours = UserHoursManagement::firstOrCreate(
            ['user_id' => $assigneeId, 'month' => $currentMonth],
            ['total_hours' => '160:00', 'consumed_hours' => '00:00']
        );

        if (strpos($userHours->consumed_hours, ':') !== false) {
            [$hours, $minutes] = explode(':', $userHours->consumed_hours);
            $currentConsumedMinutes = ($hours * 60) + $minutes;
        } else {
            $currentConsumedMinutes = 0;
        }

        $newTotalMinutes = $currentConsumedMinutes + $minutesToAdd;

        $updatedHours = intdiv($newTotalMinutes, 60);
        $updatedMinutes = $newTotalMinutes % 60;
        $formattedConsumedTime = sprintf('%02d:%02d', $updatedHours, $updatedMinutes);

        $userHours->consumed_hours = $formattedConsumedTime;
        $userHours->save();
    }

    public function getAllTotalHours()
    {
        try {
            $taskTimers = TaskTimer::all();
            $totalMinutesPerAssignee = collect();

            foreach ($taskTimers as $timer) {
                foreach ($timer->assignees as $assignee) {
                    if ($timer->total_hours && strpos($timer->total_hours, ':') !== false) {
                        [$hours, $minutes] = explode(':', $timer->total_hours);
                        $timerMinutes = ($hours * 60) + $minutes;
                    } else {
                        $timerMinutes = 0;
                    }

                    if ($totalMinutesPerAssignee->has($assignee)) {
                        $totalMinutesPerAssignee[$assignee] += $timerMinutes;
                    } else {
                        $totalMinutesPerAssignee[$assignee] = $timerMinutes;
                    }
                }
            }

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

    public function getTaskTimer($id)
    {
        try {
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

    public function isTaskTimerRunning($taskId)
    {
        try {
            $timer = TaskTimer::where('task_id', $taskId)
                ->whereNotNull('started_at')
                ->whereNull('stopped_at')
                ->first();

            if ($timer) {
                return response()->json([
                    'success' => true,
                    'message' => 'Task timer is running',
                    'data' => [
                        'timer_id' => $timer->id,
                        'task_id' => $taskId,
                        'started_at' => $timer->started_at,
                        'nexttimer_id' => $timer->id + 1,
                    ],
                    'status' => 200,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Task timer is not running',
                    'nexttimer_id' => null,
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

    /**
     * Update or create a task timer manually.
     *
     * This method handles the creation of a new task timer or the update of an existing one.
     * It validates the input data, processes the timer information, and updates the associated task and assignees.
     *
     * @param \Illuminate\Http\Request $request The HTTP request containing the timer data
     * @param int|null $id The ID of the timer to update (null for creating a new timer)
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the operation
     *
     * @throws \Illuminate\Validation\ValidationException If the input data fails validation
     * @throws \Exception If an error occurs during the timer creation/update process
     */
    public function updateOrCreateTimerManually(Request $request, ?int $id = null)
    {
        $validatedData = $this->validate($request, [
            'task_id' => 'required|exists:tasks,id',
            'assignees' => 'required|array',
            'started_at' => 'nullable|date_format:Y-m-d H:i:s',
            'stopped_at' => 'nullable|date_format:Y-m-d H:i:s',
            'total_time' => 'nullable|regex:/^\d{2}:\d{2}$/', // HH:MM
        ]);

        try {
            $taskTimer = TaskTimer::findOrNew($id);
            $taskTimer->task_id = $validatedData['task_id'];
            $taskTimer->started_at = $validatedData['started_at'] ? Carbon::parse($validatedData['started_at']) : null;
            $taskTimer->stopped_at = $validatedData['stopped_at'] ? Carbon::parse($validatedData['stopped_at']) : null;
            $taskTimer->assignees = $validatedData['assignees'];

            if (isset($validatedData['total_time'])) {
                $taskTimer->total_hours = $validatedData['total_time'];
            } elseif ($taskTimer->started_at && $taskTimer->stopped_at) {
                $totalMinutes = $taskTimer->stopped_at->diffInMinutes($taskTimer->started_at);
                $taskTimer->total_hours = sprintf('%02d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60);
            }

            $taskTimer->save();

            if ($taskTimer->started_at && $taskTimer->stopped_at) {
                $this->updateConsumedHoursForAssignees(
                    $taskTimer->assignees,
                    $taskTimer->started_at,
                    $taskTimer->stopped_at
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Task timer manually updated/created successfully',
                'data' => $taskTimer,
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to update/create task timer',
                'error' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    protected function updateConsumedHoursForAssignees(array $assignees, Carbon $startedAt, Carbon $stoppedAt)
    {
        // Calculate total minutes worked
        $totalMinutes = $stoppedAt->diffInMinutes($startedAt);

        foreach ($assignees as $assigneeId) {
            $this->updateConsumedHoursForAssignee($assigneeId, $totalMinutes);
        }
    }
}
