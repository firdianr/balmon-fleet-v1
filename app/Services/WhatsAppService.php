<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Kirim pesan WhatsApp ke satu atau beberapa nomor
     *
     * @param string|array $phone Nomor HP tunggal atau Array nomor HP
     * @param string $message Pesan teks (mendukung format WA seperti *bold*, _italic_)
     * @return bool
     */
    public static function sendMessage(string|array $phone, string $message): bool
    {
        if (empty($phone)) {
            return false;
        }

        // Ambil host dan port dari config/services.php
        $host = rtrim(config('services.wa_gateway.host', 'http://localhost'), '/');
        $port = config('services.wa_gateway.port', '3000');

        // Susun endpoint URL secara dinamis
        $gatewayUrl = "{$host}:{$port}/send-message";

        try {
            // Tembak request ke Node.js Gateway
            $response = Http::timeout(5)->post($gatewayUrl, [
                'phone'   => $phone,
                'message' => $message,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WhatsAppService Exception: ' . $e->getMessage());
            return false;
        }
    }

    public static function sendBulkMessage(array $phones, string $message): void
    {
        self::sendMessage($phones, $message);
    }
}