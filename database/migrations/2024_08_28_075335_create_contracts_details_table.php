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
        Schema::create('contract_details', function (Blueprint $table) {
            $table->id();
            $table->string('client')->nullable();
            $table->text('objective')->nullable();
            $table->text('price')->nullable();
            $table->text('delivery_conditions')->nullable();
            $table->text('penalties')->nullable();
            $table->text('payment_conditions')->nullable();
            $table->text('contract_term')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts_details');
    }
};
