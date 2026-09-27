<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;

class DatabaseSchemaInspector
{
    /** @var array<string, bool> */
    private array $tables = [];

    /** @var array<string, array<string, true>> */
    private array $columns = [];

    public function hasTable(string $table): bool
    {
        return $this->tables[$table] ??= Schema::hasTable($table);
    }

    /** @param array<int, string> $tables */
    public function hasTables(array $tables): bool
    {
        foreach ($tables as $table) {
            if (! $this->hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    public function hasColumn(string $table, string $column): bool
    {
        if (! $this->hasTable($table)) {
            return false;
        }

        $this->columns[$table] ??= array_fill_keys(Schema::getColumnListing($table), true);

        return isset($this->columns[$table][$column]);
    }
}
