<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Customer;
use App\Models\User;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Log;
use Exception;

class DashboardController extends BaseController
{
    /**
     * Display the dashboard analytics with project breakdown (completed vs pending).
     */
    public function index()
    {
        try {
            // Fetch total counts from the respective models
            $totalCustomers = Customer::count();
            $totalEmployees = User::where('id', '!=', 1)->count(); // Assuming 'role' defines employees
            $totalProjects  = Project::count();
            $totalTasks     = Task::count();

            // Fetch project analytics (completed vs pending)
            $completedProjects  = Project::where('status', 'delivered')->count();
            $pendingProjects    = Project::whereIn('status', ['not started', 'in progress', 'on hold', 'cancelled'])->count();

            // Fetch the most recent 5 projects
            $recentProjects = Project::orderBy('created_at', 'desc')->take(5)->get(['id', 'project_name', 'status', 'customer_id', 'created_at']);

            // Fetch the most recent 5 Tasks
            $recentTasks    = Task::orderBy('created_at', 'desc')->take(5)->get(['id', 'subject', 'status', 'created_at', 'project_id', 'assignees']);


            $teamMembers = User::all();
            
            // Return the data in a JSON response
            return response()->json([
                'success' => true,
                'data' => [
                    'total_customers' => $totalCustomers,
                    'total_employees' => $totalEmployees,
                    'total_projects' => $totalProjects,
                    'total_tasks' => $totalTasks,
                    'project_analytics' => [
                        'completed_projects' => $completedProjects,
                        'pending_projects' => $pendingProjects
                    ],
                    'recent_projects' => $recentProjects,
                    'recent_tasks' => $recentTasks,
                    'team_members' => $teamMembers,
                ],
                'message' => 'Dashboard analytics retrieved successfully',
                'status' => 200,
            ], 200); // HTTP 200 OK

        } catch (Exception $e) {
            // Log the error for debugging
            Log::error('Dashboard Analytics Error: ' . $e->getMessage());

            // Return a response with a 500 error code (Internal Server Error)
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard analytics',
                'error' => $e->getMessage(),
                'status' => 500,
            ], 500); // HTTP 500 Internal Server Error
        }
    }
}
