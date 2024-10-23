<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\Maintenance;
use Illuminate\Http\JsonResponse;

class MaintenanceController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $maintenances = Maintenance::with('customer')->get();

        return response()->json([
            'success' => true,
            'message' => 'Maintenance records retrieved successfully',
            'data' => $maintenances,
            'status' => 200,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'web_link' => 'required|url',
            'email' => 'required|email',
            'onboard_date' => 'required|date',
            'billing_type' => 'required|in:monthly,quarterly,half-yearly,yearly',
            'currency' => 'required|string|max:3',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string',
        ]);

        $maintenance = Maintenance::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance record created successfully',
            'data' => $maintenance,
            'status' => 201,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $maintenance = Maintenance::with('customer')->find($id);

        if (!$maintenance) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance record not found',
                'status' => 404,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Maintenance record retrieved successfully',
            'data' => $maintenance,
            'status' => 200,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $maintenance = Maintenance::find($id);

        if (!$maintenance) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance record not found',
                'status' => 404,
            ], 404);
        }

        $validatedData = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'web_link' => 'required|url',
            'email' => 'required|email',
            'onboard_date' => 'required|date',
            'billing_type' => 'required|in:monthly,quarterly,half-yearly,yearly',
            'currency' => 'required|string|max:3',
            'status' => 'required|in:active,inactive',
            'description' => 'nullable|string',
        ]);

        $maintenance->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Maintenance record updated successfully',
            'data' => $maintenance,
            'status' => 200,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $maintenance = Maintenance::find($id);

        if (!$maintenance) {
            return response()->json([
                'success' => false,
                'message' => 'Maintenance record not found',
                'status' => 404,
            ], 404);
        }

        $maintenance->delete();

        // Instead of 204 No Content, return a 200 response with a message
        return response()->json([
            'success' => true,
            'message' => 'Maintenance record deleted successfully',
            'status' => 200,
        ], 200);
    }
}
