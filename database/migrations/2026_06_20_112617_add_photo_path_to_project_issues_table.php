<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('project_issues', 'photo_path')) {
            return;
        }

        Schema::table('project_issues', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('project_issues', 'photo_path')) {
            return;
        }

        Schema::table('project_issues', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
