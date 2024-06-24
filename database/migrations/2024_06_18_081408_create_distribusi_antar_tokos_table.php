<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDistribusiAntarTokosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('distribusi_antar_tokos', function (Blueprint $table) {
            $table->id();
            $table->string('nodistribusi')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->double('qty', 20, 2)->default(0);
            $table->double('harga', 20, 2)->default(0);
            $table->double('subtotal', 20, 2)->default(0);
            $table->date('expired')->nullable();
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
        Schema::dropIfExists('distribusi_antar_tokos');
    }
}
