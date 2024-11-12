<?php

use App\Models\Annex;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('annexes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->onDelete('cascade');
            $table->string('files')->nullable();
            $table->date('annex_date')->nullable();
            $table->text('description')->nullable();
            $table->longText('summary')->nullable();
            $table->string('name')->nullable();
            $table->string('status')->default(Annex::STATUS_IN_PROGRESS);
            $table->text('last_status_message')->nullable();
            $table->longText('raw_text')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('annexes');
    }
};
