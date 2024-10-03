<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CustomerController extends BaseController
{
    // Get all customers
    public function index(): JsonResponse
    {
        $customers = Customer::latest()->paginate(10);
        return response()->json([
            'success' => true,
            'data' => $customers,
            'status' => 200
        ],200);
    }

    // Create a new customer
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company' => 'required|string|max:255',
            'customer_name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'currency' => 'required|string|max:10',
            'email' => 'required|string|email|max:255|unique:customers',
            'website' => 'nullable|string|max:255',
            'office_address' => 'required|string',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'zip_code' => 'required|string|max:20',
            'description' => 'nullable|string',
            'subscription_package' => 'required|string',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'status' => 422
            ], 422);
        }

        $customer = Customer::create($request->all());
        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Customer created successfully.',
            'status' => 201,
        ], 201);
    }

    // Get a single customer by ID
    public function show($id): JsonResponse
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found.',
                'status' => 404,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $customer,
            'status' => 200
        ],200);
    }

    // Update a customer by ID
    public function update(Request $request, $id): JsonResponse
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found.',
                'status' => 404,
            ], 404);
        }

        // Define validation rules
        $validator = Validator::make($request->all(), [
            'company' => 'required|string|max:255',
            'customer_name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'currency' => 'required|string|max:10',
            'email' => 'required|string|email|max:255|unique:customers,email,' . $customer->id,
            'website' => 'nullable|string|max:255',
            'office_address' => 'required|string',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'zip_code' => 'required|string|max:20',
            'description' => 'nullable|string',
            'subscription_package' => 'required|string',
            'status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
                'status' => 422,
            ], 422);
        }

        // Update the customer record with valid data
        $customer->update($request->only([
            'company',
            'customer_name',
            'phone',
            'currency',
            'email',
            'website',
            'office_address',
            'city',
            'state',
            'country',
            'zip_code',
            'description',
            'subscription_package',
            'status'
        ]));

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'Customer updated successfully.',
            'status' => 200,
        ],200);
    }

    // Delete a customer by ID
    public function destroy($id): JsonResponse
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found.',
                'status' => 404,
            ], 404);
        }

        $customer->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully.',
            'status' => 200,
        ],200);
    }
}
