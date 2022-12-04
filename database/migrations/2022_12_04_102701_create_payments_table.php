<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unsigned();
            $table->integer('check_out_id')->unsigned();
            $table->foreign('check_out_id')->references('id')->on('check_outs');
            $table->string('tracking_code')->nullable();
            $table->string('transaction_id');
            $table->string('card_number')->nullable();
            $table->integer('is_verify')->nullable();
            $table->integer('success');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
