<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * URL Server Node.js WhatsApp Gateway (Baileys / Express)
     */
    protected static string $gatewayUrl = 'http://localhost:3000/send-message';

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

        try {
            // Tembak request ke Node.js Gateway
            $response = Http::timeout(5)->post(self::$gatewayUrl, [
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