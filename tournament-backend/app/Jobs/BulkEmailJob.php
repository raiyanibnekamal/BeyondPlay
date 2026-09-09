<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class BulkEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  int[]  $userIds
     */
    public function __construct(
        public array $userIds,
        public string $subject,
        public string $body
    ) {
    }

    public function handle(): void
    {
        User::whereIn('id', $this->userIds)
            ->whereNotNull('email')
            ->each(function (User $user) {
                Mail::raw($this->body, function ($message) use ($user) {
                    $message->to($user->email)->subject($this->subject);
                });
            });
    }
}
