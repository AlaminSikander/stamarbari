<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * @param  array<string, scalar|null>  $variables
     */
    public function send(string $templateKey, string $channel, string $recipient, array $variables, ?int $bookingId = null): NotificationLog
    {
        $template = NotificationTemplate::query()
            ->where('key', $templateKey)
            ->where('channel', $channel)
            ->where('is_active', true)
            ->first();

        $body = $template?->body ?? $this->fallback($templateKey);
        $rendered = $this->render($body, $variables);

        $log = NotificationLog::query()->create([
            'booking_id' => $bookingId,
            'channel' => $channel,
            'template_key' => $templateKey,
            'recipient' => $recipient,
            'status' => 'queued',
            'payload' => $rendered,
        ]);

        try {
            match ($channel) {
                'email' => $this->sendEmail($recipient, $template?->subject ?? 'S T Amar Bari Resort', $rendered),
                'sms' => $this->sendSms($recipient, $rendered),
                default => $this->sendWhatsApp($recipient, $rendered),
            };
            $log->status = 'sent';
            $log->sent_at = now();
            $log->save();
        } catch (\Throwable $e) {
            $log->status = 'failed';
            $log->error = $e->getMessage();
            $log->save();
        }

        return $log;
    }

    public function notifyBooking(Booking $booking, string $templateKey): void
    {
        $guest = $booking->guests()->where('is_primary', true)->first();
        $variables = [
            'guest_name' => $guest?->full_name ?? 'Guest',
            'booking_number' => $booking->booking_number,
            'check_in' => $booking->check_in?->toDateString(),
            'check_out' => $booking->check_out?->toDateString(),
            'room_name' => $booking->category?->name_en,
            'amount_paid' => $booking->amount_paid,
            'balance' => $booking->balance_amount,
            'resort_phone' => Setting::getValue('phone', '+880 1839-425157'),
        ];

        $whatsapp = $guest?->whatsapp ?: $guest?->phone;
        if ($whatsapp) {
            $this->send($templateKey, 'whatsapp', $whatsapp, $variables, $booking->id);
        }
        if ($guest?->email) {
            $this->send($templateKey, 'email', $guest->email, $variables, $booking->id);
        }
    }

    /**
     * @param  array<string, scalar|null>  $variables
     */
    public function render(string $body, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $body = str_replace('{{'.$key.'}}', (string) $value, $body);
        }

        return $body;
    }

    private function sendWhatsApp(string $to, string $body): void
    {
        $token = config('services.whatsapp.token');
        $phoneId = config('services.whatsapp.phone_number_id');
        if (! $token || ! $phoneId) {
            return;
        }

        Http::withToken($token)->post(
            'https://graph.facebook.com/v20.0/'.$phoneId.'/messages',
            [
                'messaging_product' => 'whatsapp',
                'to' => preg_replace('/\D+/', '', $to),
                'type' => 'text',
                'text' => ['body' => $body],
            ],
        )->throw();
    }

    private function sendSms(string $to, string $body): void
    {
        $url = config('services.sms.endpoint');
        if (! $url) {
            return;
        }
        Http::post($url, [
            'to' => $to,
            'message' => $body,
            'api_key' => config('services.sms.key'),
        ])->throw();
    }

    private function sendEmail(string $to, string $subject, string $body): void
    {
        Mail::raw($body, function ($message) use ($to, $subject) {
            $message->to($to)->subject($subject);
        });
    }

    private function fallback(string $key): string
    {
        return match ($key) {
            'abandoned_booking' => 'Your booking at S T Amar Bari Resort is still incomplete. Your selected room may be released soon.',
            default => 'Thank you, {{guest_name}}. Booking {{booking_number}} at S T Amar Bari Resort. Check-in {{check_in}}. Advance paid ৳{{amount_paid}}. Remaining ৳{{balance}}. Call {{resort_phone}}.',
        };
    }
}
