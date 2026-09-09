<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $message = ContactMessage::create(array_merge($data, [
            'is_read' => false,
            'created_at' => now(),
        ]));

        return response()->json([
            'message' => 'Thank you — your message has been received.',
            'id' => $message->id,
        ], 201);
    }
}
