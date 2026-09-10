<?php

namespace App\Console\Commands;

use App\Services\MigrationLedgerReconciler;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;

class ReconcileMigrationLedger extends Command
{
    protected $signature = 'database:reconcile-migration-ledger
                            {--database= : Database connection to inspect}
                            {--apply : Record verified migrations in the ledger}';

    protected $description = 'Audit and reconcile migration records whose schema changes already exist';

    public function handle(
        ConnectionResolverInterface $connections,
        MigrationLedgerReconciler $reconciler,
    ): int {
        if (! config('migrations.frozen', true)) {
            $this->components->error('Rekonsiliasi hanya boleh dijalankan saat MIGRATIONS_FROZEN=true.');

            return self::FAILURE;
        }

        $connection = $connections->connection($this->option('database'));
        $audit = $reconciler->audit($connection);

        $this->table(
            ['Migration', 'Status', 'Catatan'],
            collect($audit)->map(
                fn (array $result, string $migration): array => [
                    $migration,
                    strtoupper($result['status']),
                    implode(' ', $result['issues']) ?: '-',
                ],
            )->values()->all(),
        );

        if (collect($audit)->contains(fn (array $result): bool => $result['status'] === 'blocked')) {
            $this->components->error('Rekonsiliasi dibatalkan karena ada signature yang tidak cocok.');

            return self::FAILURE;
        }

        if (! $this->option('apply')) {
            $this->components->info('Dry-run selesai. Ledger belum diubah; gunakan --apply setelah hasil ditinjau.');

            return self::SUCCESS;
        }

        $result = $reconciler->recordVerified($connection, $audit);

        if ($result['recorded'] === []) {
            $this->components->info('Tidak ada perubahan; seluruh migrasi target sudah tercatat.');

            return self::SUCCESS;
        }

        $this->components->info(
            count($result['recorded'])." migrasi dicatat pada batch {$result['batch']} tanpa menjalankan up()."
        );

        return self::SUCCESS;
    }
}
