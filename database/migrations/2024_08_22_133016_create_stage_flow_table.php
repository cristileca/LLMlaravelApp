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
        Schema::create('stage_flow', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')
                  ->constrained('flows')
                  ->onDelete('cascade');
            $table->string('principal_person');
            $table->string('principal_person_email');
            $table->string('second_person_name')->nullable();
            $table->string('second_person_email')->nullable();
            $table->string('stage_number');
            $table->string('maximum_step_time');
            $table->string('status_stage'); //->default('Ongoing'); // "Acceptat", "Refuzat" // "Inprogres"
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stage_flow');
    }
};
