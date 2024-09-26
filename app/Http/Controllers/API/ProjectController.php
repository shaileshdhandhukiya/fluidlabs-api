<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class ProjectController extends BaseController
{
    public function __construct()
    {
        $this->middleware('permission:project-list|project-create|project-edit|project-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:project-create', ['only' => ['store']]);
        $this->middleware('permission:project-edit', ['only' => ['update']]);
        $this->middleware('permission:project-delete', ['only' => ['destroy']]);
    }

    public function index(): JsonResponse
    {
        $projects = Project::latest()->paginate(5);
        
        return response()->json([
            'success' => true,
            'data' => $projects,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required',
            'detail' => 'required',
        ]);

        $project = Project::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully.',
            'data' => $project,
        ], 201);
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $project,
        ]);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $request->validate([
            'name' => 'required',
            'detail' => 'required',
        ]);

        $project->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully.',
            'data' => $project,
        ]);
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully.',
        ]);
    }
}
