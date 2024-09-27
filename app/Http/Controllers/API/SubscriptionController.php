<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class SubscriptionController extends Controller
{
    // Get all subscriptions
    public function index()
    {
        try {
            $subscriptions = Subscription::latest()->paginate(10);
            return response()->json(['success' => true, 'data' => $subscriptions], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve subscriptions'], 500); // 500 Internal Server Error
        }

    }

    // Create a new subscription
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'description' => 'nullable',
            'billing_duration' => 'required',
            'status' => 'required',
            'price' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400); // 400 Bad Request
        }

        try {
            $subscription = Subscription::create($request->all());
            return response()->json(['success' => true, 'data' => $subscription], 201);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create subscription'], 500); // 500 Internal Server Error
        }
    }

    // Show a specific subscription
    public function show($id)
    {
        try {
            $subscription = Subscription::find($id);

            if (!$subscription) {
                return response()->json(['error' => 'Subscription not found'], 404); // 404 Not Found
            }

            return response()->json($subscription, 200); // 200 OK
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve subscription'], 500); // 500 Internal Server Error
        }
    }

    // Update subscription method
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'billing_duration' => 'required|in:monthly,quarterly,yearly',
            'status' => 'required|in:active,inactive',
            'price' => 'required|numeric|min:0',
        ]);

        // If validation fails, return a 422 Unprocessable Entity response with error details
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subscription = Subscription::find($id);

        if (!$subscription) {
            return response()->json(['error' => 'Subscription not found'], 404);
        }

        $subscription->update($request->all());

        return response()->json([
            'message' => 'Subscription updated successfully',
            'subscription' => $subscription,
        ], 200);
    }

    // Delete a subscription
    public function destroy($id)
    {
        try {
            $subscription = Subscription::find($id);

            if (!$subscription) {
                return response()->json(['error' => 'Subscription not found'], 404); // 404 Not Found
            }

            $subscription->delete();
            return response()->json(['message' => 'Subscription deleted successfully'], 200); // 200 OK
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete subscription'], 500); // 500 Internal Server Error
        }
    }
}
