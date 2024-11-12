<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->onDelete('set null');
            $table->foreignId('client_id')
                ->nullable()
                ->constrained('suppliers')
                ->onDelete('set null');;
            $table->dropColumn('supplier_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('supplier_name')->unique()->nullable();

            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });
    }
};
