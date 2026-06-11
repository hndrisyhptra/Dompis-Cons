<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lops', function (Blueprint $table) {
            $table->string('batch')->nullable()->after('branch');
            $table->string('no_sp')->nullable()->after('batch');
            $table->date('tgl_sp')->nullable()->after('no_sp');
            $table->date('tgl_toc')->nullable()->after('tgl_sp');
        });
    }

    public function down(): void
    {
        Schema::table('lops', function (Blueprint $table) {
            $table->dropColumn([
                'batch',
                'no_sp',
                'tgl_sp',
                'tgl_toc',
            ]);
        });
    }
};