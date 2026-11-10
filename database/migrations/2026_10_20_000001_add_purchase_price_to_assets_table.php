<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assets') && !Schema::hasColumn('assets', 'purchase_price')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->decimal('purchase_price', 14, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assets') && Schema::hasColumn('assets', 'purchase_price')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->dropColumn('purchase_price');
            });
        }
    }
};
