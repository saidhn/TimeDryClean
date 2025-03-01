<?php

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $twilio;
    protected $from;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.whatsapp_from');

        if ($sid && $token && $from) {
            $this->twilio = new Client($sid, $token);
            $this->from = "whatsapp:{$from}";
        } else {
            Log::error("Twilio configuration is missing. Check your .env file.");
            $this->twilio = null;
        }
    }

    public function sendMessage($to, $messageBody)
    {
        if (!$this->twilio) {
            return false;
        }

        try {
            $message = $this->twilio->messages->create(
                "whatsapp:{$to}",
                [
                    "from" => $this->from,
                    "body" => $messageBody,
                ]
            );

            Log::info("WhatsApp message sent successfully. SID: " . $message->sid);
            return true;
        } catch (\Exception $e) {
            Log::error("Error sending WhatsApp message: " . $e->getMessage());
            return false;
        }
    }
}
