<?php

use App\Models\Contract;
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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('files')->nullable();
            $table->string('contract_number')->nullable();
            $table->string('cui')->nullable();
            $table->date('contract_date')->nullable();
            $table->string('contract_starting_date')->nullable();
            $table->string('supplier_name')->unique()->nullable();
            $table->string('status')->default(Contract::STATUS_IN_PROGRESS);
            $table->text('last_status_message')->nullable();
            $table->longText('summary')->nullable();
            $table->longText('raw_text')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
