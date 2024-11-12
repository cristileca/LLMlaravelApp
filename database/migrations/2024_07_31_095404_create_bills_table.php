<?php

use App\Models\Bill;
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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('files');
            $table->string('number')->nullable();
            $table->date('date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('fee', 10, 2)->nullable();
            $table->foreignId('contract_id')
                ->nullable()
                ->constrained('contracts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->string('status')->default(Bill::STATUS_IN_PROGRESS);
            $table->text('last_status_message')->nullable();
            $table->string('acceptance_status')->default(Bill::STATUS_CHECK_IF_APPROVED);
            $table->text('acceptance_description')->nullable();
            $table->text('analysis')->nullable();
            $table->text('cost_center')->nullable();
            $table->timestamps();
            $table->string("seller_name")->nullable();
            $table->string("seller_cui")->nullable();
            $table->string("seller_address")->nullable();
            $table->string("seller_IBAN")->nullable();
            $table->string("seller_bank")->nullable();
            $table->string("seller_phone_number")->nullable();
            $table->string("seller_email")->nullable();
            $table->string("customer_name")->nullable();
            $table->string("customer_cui")->nullable();
            $table->string("customer_address")->nullable();
            $table->string("customer_IBAN")->nullable();
            $table->string("customer_bank")->nullable();
            $table->string("customer_phone_number")->nullable();
            $table->string("customer_email")->nullable();
            $table->string("fee_tva")->nullable();
            $table->json('details')->nullable();
            $table->longText('raw_text')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
