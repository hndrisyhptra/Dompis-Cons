<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! DB::table('customers')->where('customer_code', 'TIF')->exists()) {
            DB::table('customers')->insert([
                'customer_code' => 'TIF',
                'customer_name' => 'Telkom Infrastructure',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasColumn('projects', 'customer_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('id_project');
            });
        }

        $tifCustomerId = DB::table('customers')
            ->where('customer_code', 'TIF')
            ->value('id_customer');

        if ($tifCustomerId) {
            DB::table('projects')
                ->whereNull('customer_id')
                ->update(['customer_id' => $tifCustomerId]);
        }

        if (! $this->hasIndex('projects', 'projects_customer_id_index')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->index('customer_id', 'projects_customer_id_index');
            });
        }

        if (! $this->hasForeignKey('projects', 'projects_customer_id_foreign')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->foreign('customer_id', 'projects_customer_id_foreign')
                    ->references('id_customer')
                    ->on('customers')
                    ->restrictOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('projects', 'customer_id')) {
            return;
        }

        if ($this->hasForeignKey('projects', 'projects_customer_id_foreign')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropForeign('projects_customer_id_foreign');
            });
        }

        if ($this->hasIndex('projects', 'projects_customer_id_index')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropIndex('projects_customer_id_index');
            });
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('customer_id');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(DB::select(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             AND INDEX_NAME = ?',
            [$table, $index]
        ))->isNotEmpty();
    }

    private function hasForeignKey(string $table, string $constraint): bool
    {
        return collect(DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = ?
             AND CONSTRAINT_NAME = ?
             AND CONSTRAINT_TYPE = ?',
            [$table, $constraint, 'FOREIGN KEY']
        ))->isNotEmpty();
    }
};
