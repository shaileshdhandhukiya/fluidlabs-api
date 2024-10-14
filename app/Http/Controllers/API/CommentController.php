<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends BaseController
{
    /**
     * Display comments for a specific task.
     */
    public function index($task_id)
    {
        $comments = Comment::where('task_id', $task_id)->with('user')->get();

        if ($comments->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No comments found for this task',
                'status' => 404,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $comments,
            'message' => 'Comments retrieved successfully',
            'status' => 200,
        ], 200);
    }

    /**
     * Store a newly created comment.
     */
    public function store(Request $request, $task_id)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'status' => 400,
            ], 400);
        }

        $comment = Comment::create([
            'task_id' => $task_id,
            'user_id' => auth()->id(),
            'comment' => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'data' => $comment,
            'message' => 'Comment created successfully',
            'status' => 201,
        ], 201);
    }

    /**
     * Update the specified comment.
     */
    public function update(Request $request, $id)
    {
        $comment = Comment::find($id);

        if (!$comment || $comment->user_id != auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found or unauthorized',
                'status' => 403,
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'status' => 400,
            ], 400);
        }

        $comment->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $comment,
            'message' => 'Comment updated successfully',
            'status' => 200,
        ], 200);
    }

    /**
     * Remove the specified comment.
     */
    public function destroy($id)
    {
        $comment = Comment::find($id);

        if (!$comment || $comment->user_id != auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found or unauthorized',
                'status' => 403,
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully',
            'status' => 200,
        ], 200);
    }
}
