<?php

namespace App\Services;

use App\Models\NotificationTemplate;
use App\Models\User;

class NotificationService
{
    public function __construct(
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * Get notification message in user's preferred language.
     * Uses admin-defined templates if available, otherwise falls back to lang files.
     */
    public function getMessage(string $key, string $lang, array $replace = []): string
    {
        $template = NotificationTemplate::where('key', $key)->first();
        $message = null;

        if ($template) {
            $message = $lang === 'ar' ? $template->message_ar : $template->message_en;
        }

        if (empty($message)) {
            $previousLocale = app()->getLocale();
            app()->setLocale($lang);
            $message = __('messages.' . $key, $replace);
            app()->setLocale($previousLocale);
        }

        foreach ($replace as $search => $value) {
            $message = str_replace(':' . $search, (string) $value, $message);
        }

        return $message;
    }

    /**
     * Send WhatsApp notification to user with their preferred language.
     */
    public function sendTransactionNotification(User $user, string $messageKey, array $replace = []): bool
    {
        if (empty($user->mobile)) {
            return false;
        }

        $lang = $user->notification_language ?? 'en';
        if (!in_array($lang, ['ar', 'en'])) {
            $lang = 'en';
        }

        $message = $this->getMessage($messageKey, $lang, $replace);

        return $this->whatsAppService->sendMessage($user->mobile, $message);
    }
}
