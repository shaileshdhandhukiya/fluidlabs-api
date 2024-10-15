<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

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

    public function show($id)
    {
        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found',
                'status' => 404
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $comment,
            'message' => 'Comment retrieved successfully',
            'status' => 200
        ], 200);
    }
    
    /**
     * Store a newly created comment.
     */
    public function store(Request $request, $task_id)
    {
      
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string',
            'attachment' => 'nullable|file|max:10240',
        ]);

        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'status' => 400,
            ], 400);
        }
       
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('attachments');
        }
       // Create the comment
        $comment = Comment::create([
            'task_id' => $task_id,
            'user_id' => auth()->id(),
            'comment' => $request->comment,
            'attachment' => $attachmentPath,
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
            ], 403); // HTTP 403 Forbidden
        }

        $validator = Validator::make($request->all(), [
            'comment' => 'required|string',
            'attachment' => 'nullable|file|max:10240', // 10MB max file size
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'status' => 400,
            ], 400); // HTTP 400 Bad Request
        }

        // Handle file upload
        if ($request->hasFile('attachment')) {
            // Delete old attachment if it exists
            if ($comment->attachment) {
                Storage::delete($comment->attachment);
            }

            // Store new file and update the attachment field
            $attachmentPath = $request->file('attachment')->store('attachments');
            $comment->attachment = $attachmentPath;
        }

        // Update the comment's content
        $comment->comment = $request->comment;
        $comment->save();

        return response()->json([
            'success' => true,
            'data' => $comment,
            'message' => 'Comment updated successfully',
            'status' => 200,
        ], 200); // HTTP 200 OK
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
