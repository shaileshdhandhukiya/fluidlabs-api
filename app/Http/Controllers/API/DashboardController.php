<?php

namespace App\Http\Controllers\API;

// use App\Http\Controllers\Controller;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\User;
use App\Models\Project;
use App\Models\Task;

class DashboardController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch total counts from the respective models
        $totalCustomers = Customer::count();
        $totalEmployees = User::where('role', 'employee')->count(); // Assuming 'role' defines employees
        $totalProjects = Project::count();
        $totalTasks = Task::count();

        // Return the data in a JSON response
        return response()->json([
            'success' => true,
            'data' => [
                'total_customers' => $totalCustomers,
                'total_employees' => $totalEmployees,
                'total_projects' => $totalProjects,
                'total_tasks' => $totalTasks,
            ],
            'message' => 'Dashboard analytics retrieved successfully',
            'status' => 200,
        ], 200); // HTTP 200 OK
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
