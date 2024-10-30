<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UserHoursManagement;
use App\Models\TaskTimer;
use Carbon\Carbon;

class UserHoursController extends BaseController
{

    // Set or adjust total hours for a specific user and month (skipping user_id = 1)
    public function setTotalHours(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'month' => 'required|date_format:Y-m',
            'total_hours' => 'required|integer|min:0',
        ]);

        // Skip user_id = 1
        if ($request->user_id == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot set hours for user_id = 1',
            ], 403);
        }

        $totalHours = UserHoursManagement::updateOrCreate(
            ['user_id' => $request->user_id, 'month' => $request->month],
            ['total_hours' => $request->total_hours]
        );

        return response()->json([
            'success' => true,
            'message' => 'User total hours set successfully',
            'data' => $totalHours,
        ], 200);
    }

    public function getAllUsersHours(Request $request)
    {
        try {
            $currentMonth = Carbon::now()->format('Y-m');
            $users = User::where('id', '!=', 1)->get(); // Skip user_id = 1
            $allUsersHours = [];

            foreach ($users as $user) {
                // Get the user hours management record for the current month
                $userHoursManagement = UserHoursManagement::where('user_id', $user->id)
                    ->where('month', $currentMonth)
                    ->first();

                // Get total hours (default to 160:00 if not found)
                $totalAvailableHours = isset($userHoursManagement) ? $userHoursManagement->total_hours : '160:00';
                // Get consumed hours (default to 0:00 if not found)
                $consumedHours = isset($userHoursManagement) ? $userHoursManagement->consumed_hours : '0:00';

                // Convert HH:MM to total minutes for calculations
                $totalAvailableMinutes = $this->convertToMinutes($totalAvailableHours);
                $consumedMinutes = $this->convertToMinutes($consumedHours);

                // Calculate remaining and overtime hours in minutes
                $remainingMinutes = max(0, $totalAvailableMinutes - $consumedMinutes);
                $overtimeMinutes = max(0, $consumedMinutes - $totalAvailableMinutes);

                // Convert back to HH:MM format
                $remainingHours = $this->convertToHHMM($remainingMinutes);
                $overtimeHours = $this->convertToHHMM($overtimeMinutes);

                // Add user data to the response array
                $allUsersHours[] = [
                    'user_id' => $user->id,
                    'name' => $user->first_name,
                    'total_hours' => $totalAvailableHours,
                    'consumed_hours' => $consumedHours,
                    'remaining_hours' => $remainingHours,
                    'overtime_hours' => $overtimeHours
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'All users hours retrieved successfully',
                'data' => $allUsersHours,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users hours',
                'error' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    // Convert HH:MM to total minutes
    private function convertToMinutes($time)
    {

        // Check if the time is in HH:MM format
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
            list($hours, $minutes) = array_slice($matches, 1);
            return ($hours * 60) + $minutes;
        }

        // Check if the time is in H:MM format
        if (preg_match('/^(\d{1}):(\d{2})$/', $time, $matches)) {
            list($hours, $minutes) = array_slice($matches, 1);
            return ($hours * 60) + $minutes;
        }

        // If the format is not correct, return 0 minutes
        return 0;
    }

    // Convert total minutes back to HH:MM format
    private function convertToHHMM($minutes)
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        return sprintf('%02d:%02d', $hours, $remainingMinutes);
    }

    public function getUserHours($userId)
    {
        try {
            // Skip user with ID 1
            if ($userId == 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'status' => 404,
                ], 404);
            }

            $currentMonth = Carbon::now()->format('Y-m');

            // Get the user
            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'status' => 404,
                ], 404);
            }

            // Get the total available hours for the current month (default to 160 if not found)
            $userHoursManagement = UserHoursManagement::where('user_id', $userId)
                ->where('month', $currentMonth)
                ->first();

            $totalAvailableHours = $userHoursManagement->total_hours ?? 160;

            // Calculate consumed hours from TaskTimer table
            $consumedHours = TaskTimer::whereJsonContains('assignees', (string) $userId)
                ->whereMonth('started_at', Carbon::now()->month)
                ->sum('total_hours');

            // Calculate remaining and overtime hours
            $remainingHours = max(0, $totalAvailableHours - $consumedHours);
            $overtimeHours = max(0, $consumedHours - $totalAvailableHours);

            // Update or create user hours management entry
            if ($userHoursManagement) {
                $userHoursManagement->update([
                    'consumed_hours' => $consumedHours,
                ]);
            } else {
                UserHoursManagement::create([
                    'user_id' => $userId,
                    'month' => $currentMonth,
                    'total_hours' => $totalAvailableHours,
                    'consumed_hours' => $consumedHours,
                ]);
            }

            // Return the response in the required format
            return response()->json([
                'success' => true,
                'message' => 'User hours retrieved successfully',
                'data' => [
                    'user_id' => $user->id,
                    'name' => $user->first_name,
                    'total_hours' => $totalAvailableHours,
                    'consumed_hours' => $consumedHours,
                    'remaining_hours' => $remainingHours,
                    'overtime_hours' => $overtimeHours,
                ],
                'status' => 200,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user hours',
                'error' => $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }
}
