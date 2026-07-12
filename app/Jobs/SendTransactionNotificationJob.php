<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTransactionNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public int $userId,
        public string $messageKey,
        public array $replace = []
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            return;
        }

        $notificationService->sendTransactionNotification($user, $this->messageKey, $this->replace);
    }
}
