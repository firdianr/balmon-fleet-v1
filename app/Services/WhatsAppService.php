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
        // Jika parameter $phone berupa array (misal dari hasil User::pluck('phone')),
        // alihkan ke metode sendBulkMessage
        if (is_array($phone)) {
            self::sendBulkMessage($phone, $message);
            return true;
        }

        // Sanitasi dasar: bersihkan karakter selain angka
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        if (empty($cleanPhone)) {
            Log::warning('WhatsAppService: Nomor HP tujuan kosong atau tidak valid.');
            return false;
        }

        try {
            // Eksekusi HTTP POST Request ke Node.js Gateway
            $response = Http::timeout(10)->post(self::$gatewayUrl, [
                'phone'   => $cleanPhone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                Log::info("WhatsAppService: Pesan berhasil dikirim ke {$cleanPhone}");
                return true;
            }

            Log::error("WhatsAppService: Gagal kirim WA ke {$cleanPhone}. Response: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error('WhatsAppService Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim pesan WhatsApp ke banyak nomor (Bulk) secara berurutan dengan jeda waktu
     *
     * @param array $phones Array berisi daftar nomor HP
     * @param string $message Pesan teks
     * @param int $delaySeconds Jeda antar pengiriman (default: 2 detik)
     * @return void
     */
    public static function sendBulkMessage(array $phones, string $message, int $delaySeconds = 2): void
    {
        // Hilangkan elemen duplikat dan nilai null/kosong
        $uniquePhones = array_unique(array_filter($phones));

        if (empty($uniquePhones)) {
            return;
        }

        foreach ($uniquePhones as $index => $phone) {
            self::sendMessage((string) $phone, $message);

            // Beri jeda antar pesan kecuali untuk nomor terakhir
            if ($index < count($uniquePhones) - 1 && $delaySeconds > 0) {
                sleep($delaySeconds);
            }
        }
    }
}