<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('saturation_deductions'))
        {
            Schema::create('saturation_deductions', function (Blueprint $table) {
                $table->id();
                $table->integer('employee_id');
                $table->integer('deduction_option');
                $table->string('title');
                $table->string('type')->nullable();
                $table->integer('amount');
                $table->integer('workspace')->nullable();
                $table->integer('created_by');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('saturation_deductions');
    }
};
