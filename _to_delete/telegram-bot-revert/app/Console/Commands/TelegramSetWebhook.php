<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

/**
 * php artisan telegram:webhook set   -> daftarkan webhook ke Telegram (APP_URL + secret dari .env)
 * php artisan telegram:webhook info  -> cek status webhook saat ini
 * php artisan telegram:webhook delete -> hapus webhook (mis. sebelum pindah ke polling/testing lokal)
 */
class TelegramSetWebhook extends Command
{
    protected $signature = 'telegram:webhook {action=set : set|delete|info}';

    protected $description = 'Kelola webhook Telegram Bot (set/delete/info)';

    public function handle(): int
    {
        $action = $this->argument('action');

        if (! TelegramService::isConfigured() && $action !== 'info') {
            $this->error('TELEGRAM_BOT_TOKEN belum diisi di .env. Isi dulu sebelum mengelola webhook.');

            return self::FAILURE;
        }

        if ($action === 'delete') {
            $result = TelegramService::deleteWebhook();
            $this->line(json_encode($result, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        if ($action === 'info') {
            $result = TelegramService::getWebhookInfo();
            $this->line(json_encode($result, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $secret = config('services.telegram.webhook_secret');

        if (! $secret) {
            $this->error('TELEGRAM_WEBHOOK_SECRET belum diisi di .env. Isi dengan string acak (mis. hasil `php artisan tinker` -> Str::random(32)) lalu coba lagi.');

            return self::FAILURE;
        }

        $url = rtrim(config('app.url'), '/') . '/webhook/telegram/' . $secret;

        $result = TelegramService::setWebhook($url, $secret);

        if (($result['ok'] ?? false) === true) {
            $this->info("Webhook berhasil didaftarkan ke: {$url}");
        } else {
            $this->error('Gagal mendaftarkan webhook: ' . json_encode($result));
        }

        return self::SUCCESS;
    }
}
