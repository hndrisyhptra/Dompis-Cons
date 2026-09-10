<?php

namespace Tests\Unit;

use App\Console\PreventFrozenMigrations;
use Illuminate\Console\Events\CommandStarting;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class PreventFrozenMigrationsTest extends TestCase
{
    #[DataProvider('blockedCommands')]
    public function test_it_blocks_schema_changing_commands_while_frozen(string $command): void
    {
        config()->set('migrations.frozen', true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Perintah \"{$command}\" diblokir");

        $this->guard()->handle($this->event($command));
    }

    public function test_it_allows_migration_status_while_frozen(): void
    {
        config()->set('migrations.frozen', true);

        $this->guard()->handle($this->event('migrate:status'));

        $this->addToAssertionCount(1);
    }

    public function test_it_blocks_migration_pretend_while_frozen(): void
    {
        config()->set('migrations.frozen', true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Perintah "migrate" diblokir');

        $this->guard()->handle($this->event('migrate', ['--pretend' => true]));
    }

    public function test_it_allows_schema_commands_when_freeze_is_disabled(): void
    {
        config()->set('migrations.frozen', false);

        $this->guard()->handle($this->event('migrate'));

        $this->addToAssertionCount(1);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function blockedCommands(): array
    {
        return [
            'db wipe' => ['db:wipe'],
            'migrate' => ['migrate'],
            'migrate fresh' => ['migrate:fresh'],
            'migrate install' => ['migrate:install'],
            'migrate refresh' => ['migrate:refresh'],
            'migrate reset' => ['migrate:reset'],
            'migrate rollback' => ['migrate:rollback'],
            'schema dump' => ['schema:dump'],
        ];
    }

    private function guard(): PreventFrozenMigrations
    {
        return app(PreventFrozenMigrations::class);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function event(string $command, array $parameters = []): CommandStarting
    {
        return new CommandStarting(
            $command,
            new ArrayInput($parameters),
            new BufferedOutput,
        );
    }
}
