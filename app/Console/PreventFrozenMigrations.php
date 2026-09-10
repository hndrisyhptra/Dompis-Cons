<?php

namespace App\Console;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use RuntimeException;

final class PreventFrozenMigrations
{
    /**
     * Artisan commands that can change the schema or migration history.
     *
     * @var list<string>
     */
    private const BLOCKED_COMMANDS = [
        'db:wipe',
        'migrate',
        'migrate:fresh',
        'migrate:install',
        'migrate:refresh',
        'migrate:reset',
        'migrate:rollback',
        'schema:dump',
    ];

    public function __construct(private readonly ConfigRepository $config) {}

    public function handle(CommandStarting $event): void
    {
        if (! $this->config->get('migrations.frozen', true)) {
            return;
        }

        if (! in_array($event->command, self::BLOCKED_COMMANDS, true)) {
            return;
        }

        // Command documentation remains available while execution is frozen.
        if ($event->input->hasParameterOption(['--help', '-h'], true)) {
            return;
        }

        throw new RuntimeException(
            sprintf(
                'Perintah "%s" diblokir: migrasi database sedang dibekukan sementara '
                .'selama rekonsiliasi baseline schema. Gunakan "migrate:status" '
                .'untuk pemeriksaan baca-saja.',
                $event->command,
            )
        );
    }
}
