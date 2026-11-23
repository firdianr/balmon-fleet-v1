<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pembersihan otomatis berkas PDF sementara hasil auto-fill yang tidak dilanjutkan (> 24 jam)
Schedule::call(function () {
    $disk = Storage::disk('local');

    if (!$disk->exists('temp_letters')) {
        return;
    }

    $threshold = now()->subDay()->getTimestamp();

    foreach ($disk->files('temp_letters') as $file) {
        if ($disk->lastModified($file) < $threshold) {
            $disk->delete($file);
        }
    }
})->name('cleanup-temp-letters')->withoutOverlapping()->daily();