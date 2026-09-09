<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\BulkEmailJob;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class BulkEmailController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'audience' => ['required', Rule::in(['all', 'active', 'newsletter'])],
        ]);

        if ($data['audience'] === 'newsletter') {
            $emails = NewsletterSubscriber::where('status', 'active')->pluck('email');
            $count = 0;

            foreach ($emails as $email) {
                Mail::raw($data['body'], function ($message) use ($email, $data) {
                    $message->to($email)->subject($data['subject']);
                });
                $count++;
            }

            return response()->json([
                'message' => 'Bulk email queued for newsletter subscribers.',
                'recipient_count' => $count,
            ]);
        }

        $query = User::query()->whereNotNull('email');

        if ($data['audience'] === 'active') {
            $query->where('status', 'active');
        }

        $userIds = $query->pluck('id')->all();

        if ($userIds === []) {
            return response()->json(['message' => 'No recipients found for this audience.'], 422);
        }

        BulkEmailJob::dispatch($userIds, $data['subject'], $data['body']);

        return response()->json([
            'message' => 'Bulk email queued successfully.',
            'recipient_count' => count($userIds),
        ]);
    }
}
